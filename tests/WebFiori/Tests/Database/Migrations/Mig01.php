<?php
namespace WebFiori\Tests\Database\Migrations;

use WebFiori\Database\Database;
use WebFiori\Database\Migration\AbstractMigration;


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
                \WebFiori\Database\ColOption::TYPE => \WebFiori\Database\DataType::VARCHAR
            ]
        ]);
        $schema->table('m_1')->createTable()->execute();
    }
}
