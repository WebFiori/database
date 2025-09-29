<?php

namespace WebFiori\Tests\Database\Schema;

use WebFiori\Database\Database;
use WebFiori\Database\Schema\AbstractMigration;

class TestMigration3 extends AbstractMigration {
    
    public function getDependencies(): array {
        return ['WebFiori\Tests\Database\Schema\TestMigration'];
    }
    
    public function up(Database $db): void {
        $db->table('user_profiles')->addCol('status', 'varchar', 50)->execute();
        
    }
    
    public function down(Database $db): void {
                $db->table('user_profiles')->dropColumn('status');
        $db->execute();
    }
}
