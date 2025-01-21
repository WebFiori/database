<?php
namespace webfiori\database\tests\migrations;

use webfiori\database\Database;
use webfiori\database\migration\AbstractMigration;


class Mig01 extends AbstractMigration {
    public function __construct() {
        parent::__construct('Mig01', 1);
    }

    public function down(Database $schema) {
        $schema->table('m_1')->drop()->execute();
        
    }

    public function up(Database $schema) {
        $schema->createBlueprint('m_1')->addColumns([
            'name' => [
                \webfiori\database\ColOption::TYPE => \webfiori\database\DataType::VARCHAR
            ]
        ]);
        $schema->table('m_1')->createTable()->execute();
    }
}
