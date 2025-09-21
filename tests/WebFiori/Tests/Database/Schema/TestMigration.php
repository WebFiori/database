<?php

namespace WebFiori\Tests\Database\Schema;

use WebFiori\Database\Database;
use WebFiori\Database\Schema\AbstractMigration;

class TestMigration extends AbstractMigration {
    
    public function execute(Database $db): void {
        // Create a simple table
        $db->addQuery('CREATE TABLE IF NOT EXISTS user_profiles (id INT PRIMARY KEY AUTO_INCREMENT, name VARCHAR(100))', 'create');
        $db->execute();
    }
    
    public function rollback(Database $db): void {
        // Drop table
        $db->addQuery('DROP TABLE IF EXISTS user_profiles', 'drop');
        $db->execute();
    }
}
