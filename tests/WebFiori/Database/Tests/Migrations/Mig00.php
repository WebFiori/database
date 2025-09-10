<?php
namespace WebFiori\Database\tests\migrations;

use WebFiori\Database\Database;
use WebFiori\Database\Migration\AbstractMigration;


class Mig00 extends AbstractMigration {
    public function __construct() {
        parent::__construct('Mig00', 0);
    }

    public function down(Database $schema) {
        $schema->table('m_0')->drop()->execute();
    }

    public function up(Database $schema) {
        $schema->createBlueprint('m_0')->addColumns([
            'name' => [
                \WebFiori\Database\ColOption::TYPE => \WebFiori\Database\DataType::VARCHAR
            ]
        ]);
        $schema->table('m_0')->createTable()->execute();
        
    }
}
