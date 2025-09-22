<?php

namespace WebFiori\Database\Schema;

use WebFiori\Database\Database;

/**
 * Abstract base class for all database schema changes.
 * 
 * This class provides the foundation for both migrations and seeders,
 * managing common functionality such as:
 * - Tracking when changes were applied
 * - Providing unique identification for each change
 * - Defining the contract for up/down operations
 * - Managing dependencies between changes
 * - Environment-specific execution control
 * 
 * All database changes must implement the up() method for applying changes
 * and optionally the down() method for rollback operations.
 *
 * @author Ibrahim
 */
abstract class DatabaseChange {
    private $id;
    private $appliedAt;

    /**
     * Initialize a new database change with optional name and order.
     */
    public function __construct() {
        $this->setAppliedAt(date('Y-m-d H:i:s'));
    }
    /**
     * Get the timestamp when this change was applied.
     * 
     * @return string The date and time when this change was applied in Y-m-d H:i:s format.
     */
    public function getAppliedAt(): string {
        return $this->appliedAt;
    }
    
    /**
     * Set the timestamp when this change was applied.
     * 
     * @param string $date The date and time in Y-m-d H:i:s format.
     */
    public function setAppliedAt(string $date) {
        $this->appliedAt = $date;
    }
    /**
     * Set the unique identifier for this database change.
     * 
     * @param int $id The unique identifier assigned by the schema tracking system.
     */
    public function setId(int $id) {
        $this->id = $id;
    }
    
    /**
     * Get the unique identifier for this database change.
     * 
     * @return int The unique identifier assigned by the schema tracking system.
     */
    public function getId(): int {
        return $this->id;
    }
    /**
     * Execute the database change.
     * 
     * @param Database $db The database instance to execute against.
     */
    /**
     * Execute the database change (apply the migration or seeder).
     * 
     * This method contains the logic to apply the database change.
     * For migrations: create/modify tables, columns, indexes, etc.
     * For seeders: insert data into tables.
     * 
     * @param Database $db The database instance to execute changes on.
     */
    abstract public function execute(Database $db): void;

    /**
     * Rollback the database change (undo the migration or seeder).
     * 
     * This method contains the logic to reverse the database change.
     * For migrations: drop tables, remove columns, etc.
     * For seeders: typically not implemented as data rollback is complex.
     * 
     * @param Database $db The database instance to execute rollback on.
     */
    abstract public function rollback(Database $db): void;
    
    /**
     * Get the list of changes this change depends on.
     * 
     * Dependencies ensure changes are executed in the correct order.
     * For example, a migration that adds a foreign key depends on
     * the migration that creates the referenced table.
     * 
     * @return array Array of class names that must be executed before this change.
     */
    public function getDependencies(): array {
        return [];
    }
    
    /**
     * Get the type of database change.
     * 
     * @return string Either 'migration' or 'seeder'.
     */
    abstract public function getType(): string;
    

    /**
     * Get the name of this database change.
     * 
     * The name is derived from the class name and used for tracking
     * and identification purposes in the schema management system.
     * 
     * @return string The class name of this database change.
     */
    public function getName(): string {
        return static::class;
    }
}
