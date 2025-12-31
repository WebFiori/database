<?php

namespace WebFiori\Database\Repository;

use WebFiori\Database\Database;

/**
 * Minimal abstract repository with core CRUD operations.
 * 
 * For complex queries, use createQuery() or getDatabase() in subclasses.
 * 
 * @template T
 */
abstract class AbstractRepository {
    protected Database $db;
    
    public function __construct(Database $db) {
        $this->db = $db;
    }
    
    abstract protected function getTableName(): string;
    abstract protected function toEntity(array $row): object;
    abstract protected function toArray(object $entity): array;
    abstract protected function getIdField(): string;
    
    /** @return T|null */
    public function findById(mixed $id): ?object {
        $result = $this->db->table($this->getTableName())
            ->select()
            ->where($this->getIdField(), $id)
            ->execute();
            
        return $result->getCount() > 0 ? $this->toEntity($result->fetch()) : null;
    }
    
    /** @return T[] */
    public function findAll(): array {
        $result = $this->db->table($this->getTableName())
            ->select()
            ->execute();
            
        return array_map(fn($row) => $this->toEntity($row), $result->fetchAll());
    }
    
    public function count(): int {
        $result = $this->db->table($this->getTableName())
            ->selectCount(null, 'total')
            ->execute();
            
        return (int) $result->fetch()['total'];
    }
    
    /** @return Page<T> */
    public function paginate(int $page = 1, int $perPage = 20, array $orderBy = []): Page {
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;
        
        $total = $this->count();
        
        $query = $this->db->table($this->getTableName())
            ->select()
            ->limit($perPage, $offset);
        
        if (!empty($orderBy)) {
            $query->orderBy($orderBy);
        }
        
        $result = $query->execute();
        $items = array_map(fn($row) => $this->toEntity($row), $result->fetchAll());
        
        return new Page($items, $page, $perPage, $total);
    }
    
    /** @return CursorPage<T> */
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
    
    /** @param T $entity */
    public function save(object $entity): void {
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
    
    public function deleteById(mixed $id): void {
        $this->db->table($this->getTableName())
            ->delete()
            ->where($this->getIdField(), $id)
            ->execute();
    }
    
    public function deleteAll(): void {
        $this->db->table($this->getTableName())
            ->delete()
            ->execute();
    }
    
    protected function createQuery(): \WebFiori\Database\AbstractQuery {
        return $this->db->table($this->getTableName())->select();
    }
    
    protected function getDatabase(): Database {
        return $this->db;
    }
}
