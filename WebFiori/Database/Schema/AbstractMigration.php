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
     * Rollback the migration changes from the database.
     * 
     * This method should contain the reverse migration logic to undo
     * all changes made in the up() method such as:
     * - Dropping tables, columns, indexes
     * - Removing constraints and relationships
     * - Restoring previous schema state
     * 
     * @param Database $db The database instance to execute rollback on.
     */
    abstract public function down(Database $db): void;

    /**
     * Execute the database change (apply the migration).
     * 
     * This method contains the logic to apply the database change by calling
     * the up() method implemented by concrete migration classes.
     * 
     * @param Database $db The database instance to execute changes on.
     */
    public function execute(Database $db): void {
        $this->up($db);
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
     * Rollback the database change (undo the migration).
     * 
     * This method contains the logic to rollback the database change by calling
     * the down() method implemented by concrete migration classes.
     * 
     * @param Database $db The database instance to execute rollback on.
     */
    public function rollback(Database $db): void {
        $this->down($db);
    }

    /**
     * Apply the migration changes to the database.
     * 
     * This method should contain the forward migration logic such as:
     * - Creating tables, columns, indexes
     * - Modifying existing schema elements
     * - Adding constraints and relationships
     * 
     * @param Database $db The database instance to execute changes on.
     */
    abstract public function up(Database $db): void;
}
