<?php

use WebFiori\Database\ColOption;
use WebFiori\Database\Database;
use WebFiori\Database\DataType;
use WebFiori\Database\Schema\AbstractMigration;

/**
 * Migration that only runs against the 'reporting-db' connection.
 *
 * In multi-database architectures, some tables belong to specific databases.
 * For example, a reporting database might have aggregation tables that don't
 * belong in the main application database.
 *
 * By overriding getTargetConnections(), this migration will be skipped
 * when running against any connection other than 'reporting-db'.
 */
class CreateReportsTableMigration extends AbstractMigration {
    public function getTargetConnections(): array {
        return ['reporting-db'];
    }

    public function up(Database $db): void {
        $db->createBlueprint('daily_reports')->addColumns([
            'id' => [
                ColOption::TYPE => DataType::INT,
                ColOption::PRIMARY => true,
                ColOption::AUTO_INCREMENT => true
            ],
            'report-date' => [
                ColOption::TYPE => DataType::DATE,
                ColOption::NULL => false
            ],
            'total-orders' => [
                ColOption::TYPE => DataType::INT,
                ColOption::DEFAULT => 0
            ],
            'revenue' => [
                ColOption::TYPE => DataType::DECIMAL,
                ColOption::SIZE => 10
            ]
        ]);

        $db->table('daily_reports')->createTable();
        $db->execute();
    }

    public function down(Database $db): void {
        $db->raw("DROP TABLE IF EXISTS daily_reports")->execute();
    }
}
