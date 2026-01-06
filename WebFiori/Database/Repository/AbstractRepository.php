<?php
namespace WebFiori\Database\Repository;

use ReflectionClass;
use WebFiori\Database\AbstractQuery;
use WebFiori\Database\Attributes\ForeignKey;
use WebFiori\Database\Attributes\HasMany;
use WebFiori\Database\Attributes\Table;
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

    private array $eagerLoad = [];
    private array $joinLoad = [];
    private ?array $relationships = null;

    /**
     * Creates a new repository instance.
     * 
     * @param Database $db Database connection to use.
     */
    public function __construct(Database $db) {
        $this->db = $db;
    }

    /**
     * Specify relationships to eager load.
     * 
     * @param string|array $relations Relationship name(s) to load.
     * 
     * @return static Clone with eager loading configured.
     */
    public function with(string|array $relations): static {
        $clone = clone $this;
        $clone->eagerLoad = is_array($relations) ? $relations : [$relations];
        return $clone;
    }

    /**
     * Specify belongsTo relationships to load via JOIN.
     * 
     * Only works with belongsTo relationships (N:1). Throws exception
     * if used with hasMany to prevent cartesian product issues.
     * 
     * @param string|array $relations Relationship name(s) to load via JOIN.
     * 
     * @return static Clone with JOIN loading configured.
     * 
     * @throws RepositoryException If relation is hasMany.
     */
    public function withJoin(string|array $relations): static {
        $relations = is_array($relations) ? $relations : [$relations];
        $relationships = $this->discoverRelationships();

        foreach ($relations as $relation) {
            if (!isset($relationships[$relation])) {
                throw new RepositoryException("Unknown relationship: {$relation}");
            }
            if ($relationships[$relation]['type'] === Relation::HAS_MANY) {
                throw new RepositoryException(
                    "Cannot use withJoin() for hasMany relation '{$relation}'. Use with() instead to avoid cartesian product."
                );
            }
        }

        $clone = clone $this;
        $clone->joinLoad = $relations;
        return $clone;
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
     * @throws RepositoryException If no ID is provided and $this has no ID.
     */
    public function deleteById(mixed $id = null): void {
        $id = $id ?? $this->getEntityId();
        if ($id === null) {
            throw new RepositoryException('Cannot delete: no ID provided');
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
        if (!empty($this->joinLoad)) {
            return $this->findAllWithJoin();
        }

        $result = $this->db->table($this->getTableName())
            ->select()
            ->execute();

        $entities = array_map(fn($row) => $this->toEntity($row), $result->fetchAll());
        return $this->loadRelations($entities);
    }

    /**
     * Finds a single record by its ID.
     * 
     * @param mixed $id The ID to search for, or null to use $this->id.
     * 
     * @return T|null The entity if found, null otherwise.
     * 
     * @throws RepositoryException If no ID is provided and $this has no ID.
     */
    public function findById(mixed $id = null): ?object {
        $id = $id ?? $this->getEntityId();
        if ($id === null) {
            throw new RepositoryException('Cannot find: no ID provided');
        }

        if (!empty($this->joinLoad)) {
            return $this->findByIdWithJoin($id);
        }

        $result = $this->db->table($this->getTableName())
            ->select()
            ->where($this->getIdField(), $id)
            ->execute();

        if ($result->getCount() === 0) {
            return null;
        }

        $entities = [$this->toEntity($result->fetch())];
        $loaded = $this->loadRelations($entities);
        return $loaded[0];
    }

    /**
     * Reloads $this or the given entity from the database.
     * 
     * @param T|null $entity The entity to reload, or null to reload $this.
     * 
     * @return T|null Fresh entity from database, or null if not found.
     * 
     * @throws RepositoryException If no entity provided and $this has no ID.
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
        $entities = array_map(fn($row) => $this->toEntity($row), $result->fetchAll());
        $items = $this->loadRelations($entities);

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

        $entities = array_map(fn($row) => $this->toEntity($row), $rows);
        $items = $this->loadRelations($entities);

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
     * @throws RepositoryException If no entity is provided and $this has no entity properties.
     */
    public function save(?object $entity = null): void {
        if ($entity === null && !property_exists($this, $this->getIdField())) {
            throw new RepositoryException('Cannot save: no entity provided');
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
     * Returns the table definition class for relationship discovery.
     * Override this to use a separate table class (clean architecture).
     * 
     * @return string|null Table class name or null to use $this.
     */
    protected function getTableClass(): ?string {
        return null;
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

    /**
     * Creates a related entity instance from row data using reflection.
     */
    private function createRelatedEntity(array $config, array $row): object {
        $entityClass = $config['entity'] ?? null;

        if ($entityClass === null || !class_exists($entityClass)) {
            return (object) $row;
        }

        $entity = new $entityClass();
        $ref = new ReflectionClass($entity);

        foreach ($row as $key => $value) {
            $propName = $this->toCamelCase($key);
            if ($ref->hasProperty($propName)) {
                $prop = $ref->getProperty($propName);
                $prop->setAccessible(true);
                $prop->setValue($entity, $this->castValue($prop, $value));
            } elseif ($ref->hasProperty($key)) {
                $prop = $ref->getProperty($key);
                $prop->setAccessible(true);
                $prop->setValue($entity, $this->castValue($prop, $value));
            }
        }

        return $entity;
    }

    /**
     * Cast value to property type.
     */
    private function castValue(\ReflectionProperty $prop, mixed $value): mixed {
        if ($value === null) {
            return null;
        }

        $type = $prop->getType();
        if ($type instanceof \ReflectionNamedType) {
            return match ($type->getName()) {
                'int' => (int) $value,
                'float' => (float) $value,
                'bool' => (bool) $value,
                'string' => (string) $value,
                default => $value
            };
        }

        return $value;
    }

    /**
     * Load eager-loaded relationships onto entities.
     */
    private function loadRelations(array $entities): array {
        if (empty($this->eagerLoad) || empty($entities)) {
            return $entities;
        }

        $relationships = $this->discoverRelationships();

        foreach ($this->eagerLoad as $relation) {
            if (!isset($relationships[$relation])) {
                throw new RepositoryException("Unknown relationship: {$relation}");
            }

            $config = $relationships[$relation];

            if ($config['type'] === Relation::HAS_MANY) {
                $this->loadHasMany($entities, $config);
            } elseif ($config['type'] === Relation::BELONGS_TO) {
                $this->loadBelongsTo($entities, $config);
            }
        }

        return $entities;
    }

    /**
     * Execute findAll with JOIN for belongsTo relations (single query).
     */
    private function findAllWithJoin(): array {
        $relationships = $this->discoverRelationships();
        $query = $this->buildJoinQuery($relationships);
        $rows = $query->execute()->fetchAll();

        return $this->hydrateJoinedRows($rows, $this->joinLoad, $relationships);
    }

    /**
     * Execute findById with JOIN for belongsTo relations (single query).
     */
    private function findByIdWithJoin(mixed $id): ?object {
        $relationships = $this->discoverRelationships();
        $query = $this->buildJoinQuery($relationships);
        $query->where($this->getIdField(), $id);
        $rows = $query->execute()->fetchAll();

        if (empty($rows)) {
            return null;
        }

        $entities = $this->hydrateJoinedRows($rows, $this->joinLoad, $relationships);
        return $entities[0] ?? null;
    }

    /**
     * Build query with LEFT JOINs for belongsTo relations.
     */
    private function buildJoinQuery(array $relationships): AbstractQuery {
        $this->db->clear();
        $query = $this->db->table($this->getTableName());

        foreach ($this->joinLoad as $relation) {
            $config = $relationships[$relation];
            $relatedTable = $this->db->getTable($config['table']);

            // Select related columns with aliases to avoid duplicates
            $cols = [];
            if ($relatedTable !== null) {
                foreach ($relatedTable->getColsKeys() as $colKey) {
                    $cols[$colKey] = ['as' => $relation . '_' . $colKey];
                }
            }

            $query = $query->leftJoin(
                $this->db->table($config['table'])->select($cols)
            )->on($config['foreignKey'], $config['ownerKey'] ?? 'id');
        }

        return $query->select();
    }

    /**
     * Hydrate joined rows into entities with related objects.
     */
    private function hydrateJoinedRows(array $rows, array $joinRelations, array $relationships): array {
        $entities = [];

        foreach ($rows as $row) {
            $entity = $this->toEntity($row);

            foreach ($joinRelations as $relation) {
                $config = $relationships[$relation];
                $prefix = $relation . '_';

                // Extract prefixed columns for this relation
                $relatedData = [];
                foreach ($row as $key => $value) {
                    if (str_starts_with($key, $prefix)) {
                        $relatedData[substr($key, strlen($prefix))] = $value;
                    }
                }

                // Query builder renames conflicting 'id' to 'right_id'
                if (isset($row['right_id']) && !isset($relatedData['id'])) {
                    $relatedData['id'] = $row['right_id'];
                }

                $ownerKey = $config['ownerKey'] ?? 'id';
                $hasData = isset($relatedData[$ownerKey]) && $relatedData[$ownerKey] !== null;

                $related = $hasData ? $this->createRelatedEntity($config, $relatedData) : null;
                $this->setPropertyValue($entity, $config['property'], $related);
            }

            $entities[] = $entity;
        }

        return $entities;
    }

    /**
     * Load hasMany relationship (1+1 queries - preload strategy).
     */
    private function loadHasMany(array &$entities, array $config): void {
        $localKey = $config['localKey'] ?? $this->getIdField();
        $ids = [];

        foreach ($entities as $entity) {
            $id = $this->getPropertyValue($entity, $localKey);
            if ($id !== null) {
                $ids[] = $id;
            }
        }

        if (empty($ids)) {
            return;
        }

        $fkColumn = $config['foreignKey'];
        $related = $this->db->table($config['table'])
            ->select()
            ->whereIn($fkColumn, array_unique($ids))
            ->execute()
            ->fetchAll();

        $grouped = [];
        foreach ($related as $row) {
            $fkValue = $row[$fkColumn] ?? $row[str_replace('-', '_', $fkColumn)] ?? null;
            if ($fkValue !== null) {
                $grouped[$fkValue][] = $this->createRelatedEntity($config, $row);
            }
        }

        foreach ($entities as $entity) {
            $id = $this->getPropertyValue($entity, $localKey);
            $this->setPropertyValue($entity, $config['property'], $grouped[$id] ?? []);
        }
    }

    /**
     * Load belongsTo relationship (1+1 queries - smart strategy).
     */
    private function loadBelongsTo(array &$entities, array $config): void {
        $foreignKey = $config['foreignKey'];
        $ownerKey = $config['ownerKey'] ?? 'id';
        $ids = [];

        foreach ($entities as $entity) {
            $id = $this->getPropertyValue($entity, $foreignKey);
            if ($id !== null) {
                $ids[] = $id;
            }
        }

        if (empty($ids)) {
            return;
        }

        $ownerColumn = $ownerKey;
        $related = $this->db->table($config['table'])
            ->select()
            ->whereIn($ownerColumn, array_unique($ids))
            ->execute()
            ->fetchAll();

        $indexed = [];
        foreach ($related as $row) {
            $keyValue = $row[$ownerColumn] ?? $row[str_replace('-', '_', $ownerColumn)] ?? null;
            if ($keyValue !== null) {
                $indexed[$keyValue] = $this->createRelatedEntity($config, $row);
            }
        }

        foreach ($entities as $entity) {
            $fkValue = $this->getPropertyValue($entity, $foreignKey);
            $this->setPropertyValue($entity, $config['property'], $indexed[$fkValue] ?? null);
        }
    }

    /**
     * Discover relationships from table class attributes.
     */
    private function discoverRelationships(): array {
        if ($this->relationships !== null) {
            return $this->relationships;
        }

        $this->relationships = [];
        $tableClass = $this->getTableClass() ?? static::class;

        if (!class_exists($tableClass)) {
            return $this->relationships;
        }

        $reflection = new ReflectionClass($tableClass);

        // Discover HasMany (class-level)
        foreach ($reflection->getAttributes(HasMany::class) as $attr) {
            $hasMany = $attr->newInstance();
            $table = $hasMany->table ?? $this->resolveTableName($hasMany->entity);
            $this->relationships[$hasMany->property] = [
                'type' => Relation::HAS_MANY,
                'table' => $table,
                'foreignKey' => $hasMany->foreignKey,
                'localKey' => $hasMany->localKey,
                'property' => $hasMany->property,
                'entity' => $hasMany->entity
            ];
        }

        // Discover BelongsTo (ForeignKey with property)
        foreach ($reflection->getProperties() as $prop) {
            foreach ($prop->getAttributes(ForeignKey::class) as $attr) {
                $fk = $attr->newInstance();
                if ($fk->property !== null) {
                    $table = $this->resolveTableName($fk->table);
                    $this->relationships[$fk->property] = [
                        'type' => Relation::BELONGS_TO,
                        'table' => $table,
                        'foreignKey' => $this->propertyToKey($prop->getName()),
                        'ownerKey' => $fk->column,
                        'property' => $fk->property,
                        'entity' => $fk->entity
                    ];
                }
            }
        }

        return $this->relationships;
    }

    /**
     * Resolve table name from class (if it has #[Table] attribute).
     */
    private function resolveTableName(string $classOrTable): string {
        if (class_exists($classOrTable)) {
            $ref = new ReflectionClass($classOrTable);
            $attrs = $ref->getAttributes(Table::class);
            if (!empty($attrs)) {
                return $attrs[0]->newInstance()->name;
            }
        }
        return $classOrTable;
    }

    private function propertyToKey(string $name): string {
        return strtolower(preg_replace('/([a-z])([A-Z])/', '$1-$2', $name));
    }

    private function getPropertyValue(object $entity, string $property): mixed {
        $camel = $this->toCamelCase($property);
        if (property_exists($entity, $camel)) {
            return $entity->$camel;
        }
        if (property_exists($entity, $property)) {
            return $entity->$property;
        }
        return null;
    }

    private function setPropertyValue(object $entity, string $property, mixed $value): void {
        $entity->$property = $value;
    }

    private function toCamelCase(string $key): string {
        return lcfirst(str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $key))));
    }
}
