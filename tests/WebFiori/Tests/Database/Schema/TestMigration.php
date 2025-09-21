<?php

namespace WebFiori\Tests\Database\Schema;

use WebFiori\Database\Database;
use WebFiori\Database\Schema\AbstractMigration;

class TestMigration extends AbstractMigration {
    
    public function execute(Database $db): void {
        // Create a test table
        $db->createBlueprint('user_profiles')->addColumns([
            'id' => ['type' => 'int', 'primary' => true, 'auto-inc' => true],
            'name' => ['type' => 'varchar', 'size' => 100]
        ]);
        $db->createTables()->execute();
    }
    
    public function rollback(Database $db): void {
        // Drop the test table
        $db->table('user_profiles')->drop()->execute();
    }
}
