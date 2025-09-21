<?php

namespace WebFiori\Tests\Database\Schema;

use WebFiori\Database\Database;
use WebFiori\Database\Schema\AbstractMigration;

class TestMigration2 extends AbstractMigration {
    
    public function getDependencies(): array {
        return ['TestMigration'];
    }
    
    public function execute(Database $db): void {
        // Add a column to existing test table
        $db->table('user_profiles')->addColumn('email', [
            'type' => 'varchar',
            'size' => 255
        ])->execute();
    }
    
    public function rollback(Database $db): void {
        // Remove the added column
        $db->table('user_profiles')->dropColumn('email')->execute();
    }
}
