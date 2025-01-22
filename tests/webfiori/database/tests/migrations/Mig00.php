<?php
namespace webfiori\database\tests\migrations;

use webfiori\database\Database;
use webfiori\database\migration\AbstractMigration;


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
                \webfiori\database\ColOption::TYPE => \webfiori\database\DataType::VARCHAR
            ]
        ]);
        $schema->table('m_0')->createTable()->execute();
        
    }
}
