<?php

namespace WebFiori\Tests\Database\Schema;

use WebFiori\Database\Database;
use WebFiori\Database\Schema\AbstractMigration;

class TestMigration extends AbstractMigration {
    
    public function execute(Database $db): void {
        $db->createBlueprint('user_profiles')->addColumns([
            'id' => ['type' => 'int', 'primary' => true, 'auto-inc' => true],
            'name' => ['type' => 'varchar', 'size' => 100]
        ]);
        $db->createTables();
        $db->execute();
    }
    
    public function rollback(Database $db): void {
        $db->table('user_profiles')->drop()->execute();
    }
}
