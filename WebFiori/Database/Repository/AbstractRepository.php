<?php
namespace WebFiori\Database\Repository;

use WebFiori\Database\AbstractQuery;
use WebFiori\Database\Database;

/**
 * Abstract repository providing core CRUD operations for entities.
 * 
 * Subclasses must implement the abstract methods to define table mapping.
 * For complex queries, use createQuery() or getDatabase().
 * 
 * @template T of object
 */
abstract class AbstractRepository {
    /**
     * @var Database Database instance for executing queries.
     */
    protected Database $db;

    /**
     * Creates a new repository instance.
     * 
     * @param Database $db Database connection to use.
     */
    public function __construct(Database $db) {
        $this->db = $db;
    }

    /**
     * Returns the total number of records in the table.
     * 
     * @return int Total record count.
     */
    public function count(): int {
        $result = $this->db->table($this->getTableName())
            ->selectCount(null, 'total')
            ->execute();

        return (int) $result->fetch()['total'];
    }

    /**
     * Deletes all records from the table.
     */
    public function deleteAll(): void {
        $this->db->table($this->getTableName())
            ->delete()
            ->execute();
    }

    /**
     * Deletes a record by its ID.
     * 
     * If no ID is passed, uses the ID from $this.
     * 
     * @param mixed $id The ID of the record to delete, or null to use $this->id.
     * 
     * @throws \InvalidArgumentException If no ID is provided and $this has no ID.
     */
    public function deleteById(mixed $id = null): void {
        $id = $id ?? $this->getEntityId();
        if ($id === null) {
            throw new \InvalidArgumentException('Cannot delete: no ID provided');
        }
        $this->db->table($this->getTableName())
            ->delete()
            ->where($this->getIdField(), $id)
            ->execute();
    }

    /**
     * Retrieves all records from the table.
     * 
     * @return T[] Array of all entities.
     */
    public function findAll(): array {
        $result = $this->db->table($this->getTableName())
            ->select()
            ->execute();

        return array_map(fn($row) => $this->toEntity($row), $result->fetchAll());
    }

    /**
     * Finds a single record by its ID.
     * 
     * @param mixed $id The ID to search for, or null to use $this->id.
     * 
     * @return T|null The entity if found, null otherwise.
     * 
     * @throws \InvalidArgumentException If no ID is provided and $this has no ID.
     */
    public function findById(mixed $id = null): ?object {
        $id = $id ?? $this->getEntityId();
        if ($id === null) {
            throw new \InvalidArgumentException('Cannot find: no ID provided');
        }
        $result = $this->db->table($this->getTableName())
            ->select()
            ->where($this->getIdField(), $id)
            ->execute();

        return $result->getCount() > 0 ? $this->toEntity($result->fetch()) : null;
    }

    /**
     * Reloads $this or the given entity from the database.
     * 
     * @param T|null $entity The entity to reload, or null to reload $this.
     * 
     * @return T|null Fresh entity from database, or null if not found.
     * 
     * @throws \InvalidArgumentException If no entity provided and $this has no ID.
     */
    public function reload(?object $entity = null): ?object {
        if ($entity === null) {
            return $this->findById();
        }
        $id = $this->toArray($entity)[$this->getIdField()] ?? null;
        return $this->findById($id);
    }

    /**
     * Gets the ID value from $this if it has entity properties.
     * 
     * @return mixed The ID value or null.
     */
    private function getEntityId(): mixed {
        $idField = $this->getIdField();
        if (property_exists($this, $idField)) {
            return $this->$idField;
        }
        return null;
    }

    /**
     * Retrieves paginated records using offset-based pagination.
     * 
     * @param int $page Page number (1-based).
     * @param int $perPage Number of records per page.
     * @param array $orderBy Associative array of column => direction for sorting.
     * 
     * @return Page<T> Page object containing results and pagination metadata.
     */
    public function paginate(int $page = 1, int $perPage = 20, array $orderBy = []): Page {
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;

        $total = $this->count();

        $query = $this->db->table($this->getTableName())
            ->select()
            ->limit($perPage)
            ->offset($offset);

        if (!empty($orderBy)) {
            $query->orderBy($orderBy);
        }

        $result = $query->execute();
        $items = array_map(fn($row) => $this->toEntity($row), $result->fetchAll());

        return new Page($items, $page, $perPage, $total);
    }

    /**
     * Retrieves paginated records using cursor-based pagination.
     * 
     * @param string|null $cursor Base64-encoded cursor value, null for first page.
     * @param int $limit Maximum number of records to return.
     * @param string|null $cursorField Column to use for cursor, defaults to ID field.
     * @param string $direction Sort direction ('ASC' or 'DESC').
     * 
     * @return CursorPage<T> CursorPage object containing results and next cursor.
     */
    public function paginateByCursor(
        ?string $cursor = null,
        int $limit = 20,
        ?string $cursorField = null,
        string $direction = 'ASC'
    ): CursorPage {
        $cursorField = $cursorField ?? $this->getIdField();
        $operator = $direction === 'ASC' ? '>' : '<';

        $query = $this->db->table($this->getTableName())->select();

        if ($cursor !== null) {
            $cursorValue = base64_decode($cursor);
            $query->where($cursorField, $cursorValue, $operator);
        }

        $result = $query->orderBy([$cursorField => $direction])
            ->limit($limit + 1)
            ->execute();

        $rows = $result->fetchAll();
        $hasMore = count($rows) > $limit;

        if ($hasMore) {
            array_pop($rows);
        }

        $items = array_map(fn($row) => $this->toEntity($row), $rows);

        $nextCursor = null;

        if ($hasMore && !empty($rows)) {
            $lastRow = end($rows);
            $nextCursor = base64_encode((string) $lastRow[$cursorField]);
        }

        return new CursorPage($items, $nextCursor, null, $hasMore);
    }

    /**
     * Saves an entity (insert if new, update if existing).
     * 
     * An entity is considered new if its ID field is null.
     * If no entity is passed and $this has entity properties, saves $this.
     * 
     * @param T|null $entity The entity to save, or null to save $this.
     * 
     * @throws \InvalidArgumentException If no entity is provided and $this has no entity properties.
     */
    public function save(?object $entity = null): void {
        if ($entity === null && !property_exists($this, $this->getIdField())) {
            throw new \InvalidArgumentException('Cannot save: no entity provided');
        }
        $entity = $entity ?? $this;
        $data = $this->toArray($entity);
        $id = $data[$this->getIdField()] ?? null;
        unset($data[$this->getIdField()]);

        if ($id === null) {
            $this->db->table($this->getTableName())->insert($data)->execute();
        } else {
            $this->db->table($this->getTableName())
                ->update($data)
                ->where($this->getIdField(), $id)
                ->execute();
        }
    }

    /**
     * Saves multiple entities in a single transaction.
     * 
     * New entities (null ID) are batch inserted in one query.
     * Existing entities are updated individually.
     * 
     * @param T[] $entities Array of entities to save.
     */
    public function saveAll(array $entities): void {
        if (empty($entities)) {
            return;
        }

        $newEntities = [];
        $existingEntities = [];
        $idField = $this->getIdField();

        foreach ($entities as $entity) {
            $data = $this->toArray($entity);
            if (($data[$idField] ?? null) === null) {
                unset($data[$idField]);
                $newEntities[] = $data;
            } else {
                $existingEntities[] = $data;
            }
        }

        $this->db->transaction(function (Database $db) use ($newEntities, $existingEntities, $idField) {
            if (!empty($newEntities)) {
                $db->table($this->getTableName())->insert([
                    'cols' => array_keys($newEntities[0]),
                    'values' => array_map(fn($e) => array_values($e), $newEntities)
                ])->execute();
            }

            foreach ($existingEntities as $data) {
                $id = $data[$idField];
                unset($data[$idField]);
                $db->table($this->getTableName())
                    ->update($data)
                    ->where($idField, $id)
                    ->execute();
            }
        });
    }

    /**
     * Creates a select query for the repository's table.
     * 
     * @return AbstractQuery Query builder instance.
     */
    protected function createQuery(): AbstractQuery {
        return $this->db->table($this->getTableName())->select();
    }

    /**
     * Returns the underlying database instance.
     * 
     * @return Database The database connection.
     */
    protected function getDatabase(): Database {
        return $this->db;
    }

    /**
     * Returns the name of the ID/primary key field.
     * 
     * @return string Column name of the primary key.
     */
    abstract protected function getIdField(): string;

    /**
     * Returns the database table name for this repository.
     * 
     * @return string Table name.
     */
    abstract protected function getTableName(): string;

    /**
     * Converts an entity to an associative array for database operations.
     * 
     * @param T $entity The entity to convert.
     * 
     * @return array Associative array with column names as keys.
     */
    abstract protected function toArray(object $entity): array;

    /**
     * Converts a database row to an entity object.
     * 
     * @param array $row Associative array from database.
     * 
     * @return T The mapped entity.
     */
    abstract protected function toEntity(array $row): object;
}
