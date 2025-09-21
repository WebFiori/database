<?php

namespace WebFiori\Tests\Database\Schema;

use WebFiori\Database\Database;
use WebFiori\Database\Schema\AbstractMigration;

class TestMigration2 extends AbstractMigration {
    
    public function getDependencies(): array {
        return ['TestMigration'];
    }
    
    public function execute(Database $db): void {
        $db->table('user_profiles')->addColumn('email', [
            'type' => 'varchar',
            'size' => 255
        ]);
        $db->execute();
    }
    
    public function rollback(Database $db): void {
        $db->table('user_profiles')->dropColumn('email');
        $db->execute();
    }
}
