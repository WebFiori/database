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

use WebFiori\Database\Database;
use WebFiori\Database\Repository\AbstractRepository;

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
     * Clear all change records (use with caution).
     * 
     * @return int Number of records deleted
     */
    public function clearAll(): int {
        $count = $this->count();
        $this->deleteAll();

        return $count;
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
     * Get changes by batch number.
     * 
     * @param int $batch The batch number
     * @return array Array of change records
     */
    public function getByBatch(int $batch): array {
        return $this->getDatabase()->table($this->getTableName())
            ->select()
            ->where('batch', $batch)
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
     * Get change names from the last batch.
     * 
     * @return array Array of change names from the last batch
     */
    public function getLastBatchChangeNames(): array {
        $lastBatch = $this->getLastBatchNumber();

        if ($lastBatch === 0) {
            return [];
        }

        $records = $this->getByBatch($lastBatch);

        return array_column($records, 'change_name');
    }

    /**
     * Get the last batch number.
     * 
     * @return int The last batch number, or 0 if no batches exist
     */
    public function getLastBatchNumber(): int {
        return $this->getNextBatchNumber() - 1;
    }

    /**
     * Get the next batch number.
     * 
     * @return int The next batch number
     */
    public function getNextBatchNumber(): int {
        $result = $this->getDatabase()->table($this->getTableName())
            ->select(['batch'])
            ->orderBy(['batch' => 'd'])
            ->limit(1)
            ->execute();

        if ($result->getRowsCount() === 0) {
            return 1;
        }

        return (int) $result->getRows()[0]['batch'] + 1;
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
     * @param DatabaseChange $change The change to record (must have batch set via setBatch())
     * @return int The ID of the inserted record
     */
    public function recordChange(DatabaseChange $change): int {
        $this->getDatabase()->table($this->getTableName())
            ->insert([
                'change_name' => $change->getName(),
                'type' => $change->getType(),
                'applied_on' => date('Y-m-d H:i:s'),
                'db_name' => $this->getDatabase()->getConnectionInfo()->getDBName(),
                'batch' => $change->getBatch(),
                'status' => 'applied'
            ])->execute();

        return $this->getLastInsertId();
    }

    /**
     * Record a change as skipped (baselined).
     * 
     * @param DatabaseChange $change The change to record
     * @param int $batch The batch number to assign
     * @return int The ID of the inserted record
     */
    public function recordSkipped(DatabaseChange $change, int $batch): int {
        $this->getDatabase()->table($this->getTableName())
            ->insert([
                'change_name' => $change->getName(),
                'type' => $change->getType(),
                'applied_on' => date('Y-m-d H:i:s'),
                'db_name' => $this->getDatabase()->getConnectionInfo()->getDBName(),
                'batch' => $batch,
                'status' => 'skipped'
            ])->execute();

        return $this->getLastInsertId();
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
     * Get the last insert ID from the database connection.
     * 
     * @return int The last insert ID, or 0 if not available
     */
    private function getLastInsertId(): int {
        return (int)$this->getDatabase()
        ->getQueryGenerator()
        ->selectMax($this->getIdField(), 'max')
        ->execute()
        ->getRows()[0]['max'];
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
     * Convert an entity to an array (not used for schema changes).
     * 
     * @param object $entity The entity
     * @return array The array representation
     */
    protected function toArray(object $entity): array {
        return (array) $entity;
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
}
