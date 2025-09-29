<?php

use WebFiori\Database\Schema\AbstractMigration;
use WebFiori\Database\Database;
use WebFiori\Database\DataType;
use WebFiori\Database\ColOption;

/**
 * Migration to create the users table.
 * 
 * This migration creates a basic users table with essential columns
 * for user management including auto-incrementing ID, username, email,
 * password hash, and creation timestamp.
 */
class CreateUsersTableMigration extends AbstractMigration {
    
    /**
     * Apply the migration changes to the database.
     * 
     * Creates the users table with columns for user authentication
     * and basic profile information.
     * 
     * @param Database $db The database instance to execute changes on.
     */
    public function up(Database $db): void {
        // Create users table
        $db->createBlueprint('users')->addColumns([
            'id' => [
                ColOption::TYPE => DataType::INT,
                ColOption::SIZE => 11,
                ColOption::PRIMARY => true,
                ColOption::AUTO_INCREMENT => true
            ],
            'username' => [
                ColOption::TYPE => DataType::VARCHAR,
                ColOption::SIZE => 50,
                ColOption::NULL => false
            ],
            'email' => [
                ColOption::TYPE => DataType::VARCHAR,
                ColOption::SIZE => 150,
                ColOption::NULL => false
            ],
            'password_hash' => [
                ColOption::TYPE => DataType::VARCHAR,
                ColOption::SIZE => 255,
                ColOption::NULL => false
            ],
            'created_at' => [
                ColOption::TYPE => DataType::TIMESTAMP,
                ColOption::DEFAULT => 'current_timestamp'
            ]
        ]);
        
        $db->createTables();
        $db->execute();
    }
    
    /**
     * Rollback the migration changes from the database.
     * 
     * Drops the users table and all its data. This operation
     * is irreversible and will result in data loss.
     * 
     * @param Database $db The database instance to execute rollback on.
     */
    public function down(Database $db): void {
        // Drop users table
        $db->setQuery("DROP TABLE IF EXISTS users")->execute();
    }
}
