<?php

namespace WebFiori\Tests\Database\Schema;

use WebFiori\Database\Database;
use WebFiori\Database\Schema\AbstractMigration;

class TestMigration3 extends AbstractMigration {
    
    public function getDependencies(): array {
        return [TestMigration::class];
    }
    
    public function execute(Database $db): void {
        // Add another column
        $db->addQuery('ALTER TABLE user_profiles ADD COLUMN status VARCHAR(50)', 'alter');
        $db->execute();
    }
    
    public function rollback(Database $db): void {
        // Drop column
        $db->addQuery('ALTER TABLE user_profiles DROP COLUMN status', 'alter');
        $db->execute();
    }
}
