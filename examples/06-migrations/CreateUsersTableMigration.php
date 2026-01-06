<?php

use WebFiori\Database\ColOption;
use WebFiori\Database\Database;
use WebFiori\Database\DataType;
use WebFiori\Database\Schema\AbstractMigration;

/**
 * Migration to create the users table.
 */
class CreateUsersTableMigration extends AbstractMigration {
    public function down(Database $db): void {
        $db->raw("DROP TABLE IF EXISTS users")->execute();
    }

    public function up(Database $db): void {
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
            'password-hash' => [
                ColOption::TYPE => DataType::VARCHAR,
                ColOption::SIZE => 255,
                ColOption::NULL => false
            ],
            'created-at' => [
                ColOption::TYPE => DataType::TIMESTAMP,
                ColOption::DEFAULT => 'current_timestamp'
            ]
        ]);

        $db->table('users')->createTable();
        $db->execute();
    }
}
