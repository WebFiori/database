<?php

namespace WebFiori\Database\Schema;

use WebFiori\Database\Database;

/**
 * Base class for all database changes including migrations and seeders.
 *
 * @author Ibrahim
 */
abstract class DatabaseChange {
    private $id;
    private $appliedAt;

    public function __construct(?string $name = null, ?int $order = null) {
        $this->setAppliedAt(date('Y-m-d H:i:s'));
    }
    public function getAppliedAt(): string {
        return $this->appliedAt;
    }
    
    public function setAppliedAt(string $date) {
        $this->appliedAt = $date;
    }
    public function setId(int $id) {
        $this->id = $id;
    }
    
    public function getId(): int {
        return $this->id;
    }
    /**
     * Execute the database change.
     * 
     * @param Database $db The database instance to execute against.
     */
    abstract public function execute(Database $db): void;
    
    /**
     * Rollback the database change.
     * 
     * @param Database $db The database instance to rollback against.
     */
    abstract public function rollback(Database $db): void;
    
    /**
     * Get the dependencies for this change.
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
     * Get the name of this change.
     * 
     * @return string The name of the change.
     */
    public function getName(): string {
        return static::class;
    }
}
