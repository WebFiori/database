<?php

use WebFiori\Database\Database;
use WebFiori\Database\Schema\AbstractMigration;

/**
 * Migration to add a unique index on the email column.
 * 
 * This migration adds a unique constraint to the email column
 * in the users table to ensure email uniqueness across all users.
 * This migration depends on the users table existing.
 */
class AddEmailIndexMigration extends AbstractMigration {
    
    /**
     * Get the list of migration dependencies.
     * 
     * This migration requires the users table to exist before
     * it can add an index to the email column.
     * 
     * @return array Array of migration names this migration depends on.
     */
    public function getDependencies(): array {
        return ['CreateUsersTableMigration'];
    }
    
    /**
     * Apply the migration changes to the database.
     * 
     * Adds a unique index on the email column to enforce
     * email uniqueness and improve query performance.
     * 
     * @param Database $db The database instance to execute changes on.
     */
    public function up(Database $db): void {
        // Add unique index on email column
        $db->setQuery("ALTER TABLE users ADD UNIQUE INDEX idx_users_email (email)")->execute();
    }
    
    /**
     * Rollback the migration changes from the database.
     * 
     * Removes the unique index from the email column,
     * allowing duplicate emails again.
     * 
     * @param Database $db The database instance to execute rollback on.
     */
    public function down(Database $db): void {
        // Drop email index
        $db->setQuery("ALTER TABLE users DROP INDEX idx_users_email")->execute();
    }
}
