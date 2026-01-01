<?php

/**
 * This file is licensed under MIT License.
 * 
 * Copyright (c) 2026-present WebFiori Framework
 * 
 * For more information on the license, please visit: 
 * https://github.com/WebFiori/.github/blob/main/LICENSE
 * 
 */
namespace WebFiori\Database\Schema;

use WebFiori\Database\Repository\AbstractRepository;
use WebFiori\Database\Database;

/**
 * Repository for managing database schema changes (migrations and seeders).
 * 
 * This repository provides methods to:
 * - Track applied migrations and seeders
 * - Check if a change has been applied
 * - Record new changes
 * - Remove change records
 * - Query changes by type (migration/seeder)
 */
class SchemaChangeRepository extends AbstractRepository {
    
    /**
     * Creates a new schema change repository.
     * 
     * @param Database $database The database instance
     */
    public function __construct(Database $database) {
        parent::__construct($database);
    }
    
    /**
     * Get the table name.
     * 
     * @return string The table name
     */
    public function getTableName(): string {
        return 'schema_changes';
    }
    
    /**
     * Get the ID field name.
     * 
     * @return string The ID field name
     */
    protected function getIdField(): string {
        return 'id';
    }
    
    /**
     * Convert a database record to an entity (not used for schema changes).
     * 
     * @param array $row The database record
     * @return object The entity
     */
    protected function toEntity(array $row): object {
        // Schema changes are not converted to entities, return stdClass
        return (object) $row;
    }
    
    /**
     * Convert an entity to an array (not used for schema changes).
     * 
     * @param object $entity The entity
     * @return array The array representation
     */
    protected function toArray(object $entity): array {
        return (array) $entity;
    }
    
    /**
     * Check if a change has been applied.
     * 
     * @param string $changeName The fully qualified class name of the change
     * @return bool True if the change has been applied, false otherwise
     */
    public function isApplied(string $changeName): bool {
        return $this->count(['change_name' => $changeName]) > 0;
    }
    
    /**
     * Record a change as applied.
     * 
     * @param DatabaseChange $change The change to record
     * @return int The ID of the inserted record
     */
    public function recordChange(DatabaseChange $change): int {
        $this->getDatabase()->table($this->getTableName())
            ->insert([
                'change_name' => $change->getName(),
                'type' => $change->getType(),
                'applied-on' => date('Y-m-d H:i:s'),
                'db-name' => $this->getDatabase()->getConnectionInfo()->getDatabase()
            ])->execute();
        
        return $this->getDatabase()->getLastInsertId();
    }
    
    /**
     * Remove a change record.
     * 
     * @param string $changeName The fully qualified class name of the change
     * @return int Number of records deleted
     */
    public function removeChange(string $changeName): int {
        return $this->getDatabase()->table($this->getTableName())
            ->delete()
            ->where('change_name', $changeName)
            ->execute()
            ->getRowsCount();
    }
    
    /**
     * Get all applied changes.
     * 
     * @return array Array of change records
     */
    public function getAllApplied(): array {
        return $this->getDatabase()->table($this->getTableName())
            ->select()
            ->execute()
            ->getRows();
    }
    
    /**
     * Get applied changes by type.
     * 
     * @param string $type Either 'migration' or 'seeder'
     * @return array Array of change records
     */
    public function getByType(string $type): array {
        return $this->getDatabase()->table($this->getTableName())
            ->select()
            ->where('type', $type)
            ->execute()
            ->getRows();
    }
    
    /**
     * Get all applied migrations.
     * 
     * @return array Array of migration records
     */
    public function getAllMigrations(): array {
        return $this->getByType('migration');
    }
    
    /**
     * Get all applied seeders.
     * 
     * @return array Array of seeder records
     */
    public function getAllSeeders(): array {
        return $this->getByType('seeder');
    }
    
    /**
     * Count applied changes.
     * 
     * @param array $conditions Optional conditions (e.g., ['type' => 'migration'])
     * @return int Number of applied changes
     */
    public function count(array $conditions = []): int {
        $query = $this->getDatabase()->table($this->getTableName())->select();
        
        foreach ($conditions as $col => $val) {
            $query->where($col, $val);
        }
        
        return $query->execute()->getRowsCount();
    }
    
    /**
     * Clear all change records (use with caution).
     * 
     * @return int Number of records deleted
     */
    public function clearAll(): int {
        return $this->deleteAll();
    }
}
