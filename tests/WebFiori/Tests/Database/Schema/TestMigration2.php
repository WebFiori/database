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
        $db->table('test_table')->addColumn('email', [
            'type' => 'varchar',
            'size' => 255
        ]);
        $db->execute();
    }
    
    public function rollback(Database $db): void {
        // Remove the added column
        $db->table('test_table')->dropColumn('email');
        $db->execute();
    }
}
