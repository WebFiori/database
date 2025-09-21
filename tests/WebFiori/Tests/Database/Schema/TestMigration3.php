<?php

namespace WebFiori\Tests\Database\Schema;

use WebFiori\Database\Database;
use WebFiori\Database\Schema\AbstractMigration;

class TestMigration3 extends AbstractMigration {
    
    public function getDependencies(): array {
        return [TestMigration::class];
    }
    
    public function execute(Database $db): void {
        $db->table('user_profiles')->addColumn('status', [
            'type' => 'varchar',
            'size' => 50
        ]);
        $db->execute();
    }
    
    public function rollback(Database $db): void {
        $db->table('user_profiles')->dropColumn('status');
        $db->execute();
    }
}
