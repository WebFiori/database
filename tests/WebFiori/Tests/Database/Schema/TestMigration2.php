<?php

namespace WebFiori\Tests\Database\Schema;

use WebFiori\Database\Database;
use WebFiori\Database\Schema\AbstractMigration;

class TestMigration2 extends AbstractMigration {
    
    public function getDependencies(): array {
        return ['TestMigration'];
    }
    
    public function execute(Database $db): void {
        // Add column
        $db->addQuery('ALTER TABLE user_profiles ADD COLUMN email VARCHAR(255)', 'alter');
        $db->execute();
    }
    
    public function rollback(Database $db): void {
        // Drop column
        $db->addQuery('ALTER TABLE user_profiles DROP COLUMN email', 'alter');
        $db->execute();
    }
}
