<?php

namespace WebFiori\Tests\Database\Schema;

use WebFiori\Database\Database;
use WebFiori\Database\Schema\AbstractMigration;
use WebFiori\Database\ColOption;

class TestMigration2 extends AbstractMigration {
    
    public function getDependencies(): array {
        return ['TestMigration'];
    
    public function up(Database $db): void {
        $this->execute($db);
        
    }
    
    public function down(Database $db): void {
        $this->rollback($db);
        
    }    }
    
    public function execute(Database $db): void {
        $db->table('user_profiles')->addColumn('email', [
            ColOption::TYPE => 'varchar',
            ColOption::SIZE => 255
        ]);
        $db->execute();
    }
    
    public function rollback(Database $db): void {
        $db->table('user_profiles')->dropColumn('email');
        $db->execute();
    }
}
