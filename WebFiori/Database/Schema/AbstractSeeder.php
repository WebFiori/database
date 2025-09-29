<?php
namespace WebFiori\Database\Schema;

use WebFiori\Database\Database;

/**
 * Abstract base class for database data seeders.
 * 
 * Seeders are used to populate database tables with initial or test data.
 * Unlike migrations which modify structure, seeders focus on data insertion:
 * - Populating lookup tables with reference data
 * - Creating default user accounts and roles
 * - Inserting sample data for development/testing
 * - Setting up initial application configuration
 * 
 * Seeders can be environment-specific (e.g., test data only in dev/test)
 * and are typically run after migrations to ensure tables exist.
 *
 * @author Ibrahim
 */
abstract class AbstractSeeder extends DatabaseChange {
    /**
     * Execute the database change (run the seeder).
     * 
     * This method contains the logic to execute the seeder by calling
     * the run() method implemented by concrete seeder classes.
     * 
     * @param Database $db The database instance to execute changes on.
     */
    public function execute(Database $db): void {
        $this->run($db);
    }

    /**
     * Get the environments where this seeder should be executed.
     * 
     * Seeders often need environment-specific behavior:
     * - Production seeders: essential reference data only
     * - Development seeders: sample data for testing
     * - Test seeders: specific test fixtures
     * Override this method to control execution environments.
     * 
     * @return array Array of environment names. Empty array means all environments.
     */
    public function getEnvironments(): array {
        return [];
    }

    /**
     * Get the type identifier for this database change.
     * 
     * This method is used by the SchemaRunner to categorize and track
     * different types of database changes. Seeders are distinguished
     * from migrations by this type identifier.
     * 
     * @return string Always returns 'seeder'.
     */
    public function getType(): string {
        return 'seeder';
    }

    /**
     * Rollback the database change (optional for seeders).
     * 
     * Most seeders don't implement rollback functionality as data
     * seeding is typically not reversible. Override this method
     * if your seeder needs rollback capability.
     * 
     * @param Database $db The database instance to execute rollback on.
     */
    public function rollback(Database $db): void {
        // Default implementation does nothing
        // Override in concrete seeders if rollback is needed
    }

    /**
     * Run the seeder to populate the database with data.
     * 
     * This method should contain the data insertion logic such as:
     * - Inserting reference/lookup data
     * - Creating default user accounts
     * - Populating sample data for development
     * - Setting up initial application configuration
     * 
     * @param Database $db The database instance to execute seeding on.
     */
    abstract public function run(Database $db): void;
}
