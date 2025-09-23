<?php

namespace WebFiori\Tests\Database\Schema;

use WebFiori\Database\Database;
use WebFiori\Database\Schema\AbstractMigration;
use WebFiori\Database\ColOption;

class TestMigration extends AbstractMigration {
    
    public function up(Database $db): void {
        $this->execute($db);
    }
    
    public function down(Database $db): void {
        $this->rollback($db);
    }
    
    public function execute(Database $db): void {
        $db->createBlueprint('user_profiles')->addColumns([
            'id' => [ColOption::TYPE => 'int', ColOption::PRIMARY => true, ColOption::AUTO_INCREMENT => true],
            'name' => [ColOption::TYPE => 'varchar', ColOption::SIZE => 100]
        ]);
        $db->createTables();
        $db->execute();
    }
    
    public function rollback(Database $db): void {
        $db->table('user_profiles')->drop()->execute();
    }
}
