<?php

namespace WebFiori\Database\Schema;
use WebFiori\Database\Database;

/**
 * Abstract base class for database schema migrations.
 * 
 * Migrations are used to modify database structure over time in a controlled,
 * versioned manner. Each migration represents a single change to the database
 * schema such as:
 * - Creating or dropping tables
 * - Adding, modifying, or removing columns
 * - Creating or dropping indexes
 * - Modifying constraints and relationships
 * 
 * Migrations are executed in dependency order and can be rolled back if needed.
 * They run in all environments by default unless overridden.
 *
 * @author Ibrahim
 */
abstract class AbstractMigration extends DatabaseChange {
    /**
     * Get the type identifier for this database change.
     * 
     * This method is used by the SchemaRunner to categorize and track
     * different types of database changes. Migrations are distinguished
     * from seeders by this type identifier.
     * 
     * @return string Always returns 'migration'.
     */
    public function getType(): string {
        return 'migration';
    }
    
    /**
     * Get the environments where this migration should be executed.
     * 
     * By default, migrations run in all environments (dev, test, prod).
     * Override this method to restrict execution to specific environments.
     * For example, return ['dev'] to only run in development.
     * Migrations run in all environments by default.
     * 
     * @return array Empty array means all environments.
     */
    public function getEnvironments(): array {
        return [];
    }
}
