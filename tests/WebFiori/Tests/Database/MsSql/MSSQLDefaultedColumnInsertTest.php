<?php

namespace WebFiori\Tests\Database\MsSql;

use PHPUnit\Framework\TestCase;
use WebFiori\Database\ColOption;
use WebFiori\Database\ConnectionInfo;
use WebFiori\Database\ConnectionPool;
use WebFiori\Database\Database;
use WebFiori\Database\DataType;

/**
 * Issue #180: a defaulted column given by name must not be added twice.
 */
class MSSQLDefaultedColumnInsertTest extends TestCase {
    private ?Database $db = null;

    protected function tearDown(): void {
        if ($this->db !== null) {
            foreach (['defaulted_col_test'] as $t) {
                $this->db->raw("IF OBJECT_ID('$t', 'U') IS NOT NULL DROP TABLE $t")->execute();
            }
            $this->db->close();
            $this->db = null;
        }
        ConnectionPool::reset();
    }

    public static function dbTypesProvider(): array {
        return ['mssql' => ['mssql'], 'mysql' => ['mysql']];
    }

    /**
     * #180: a defaulted column given by name (underscore) must not be added again.
     * Checks the generated SQL, so no database is needed.
     *
     * @test
     * @dataProvider dbTypesProvider
     */
    public function testDefaultedColumnGivenByNameIsNotDuplicated(string $type) {
        $db = new Database(new ConnectionInfo($type, 'user', 'pass', 'db', 'localhost'));
        $db->addTable($db->createBlueprint('items')->addColumns([
            'id' => [ColOption::TYPE => DataType::INT, ColOption::PRIMARY => true, ColOption::IDENTITY => true],
            'name' => [ColOption::TYPE => DataType::VARCHAR, ColOption::SIZE => 50],
            'qty' => [ColOption::TYPE => DataType::INT, ColOption::DEFAULT => 0],
            'unit_count' => [ColOption::TYPE => DataType::INT, ColOption::DEFAULT => 0],
        ]));

        $byKey = $db->table('items')->insert(['name' => 'k', 'unit-count' => 5])->getQuery();
        $byName = $db->table('items')->insert(['name' => 'n', 'unit_count' => 5])->getQuery();

        $this->assertEquals(1, substr_count($byKey, 'unit_count'), "by key: $byKey");
        $this->assertEquals(1, substr_count($byName, 'unit_count'), "by name: $byName");
    }

    /**
     * #180: end to end on SQL Server.
     *
     * @test
     */
    public function testDefaultedColumnGivenByNameExecutes() {
        $db = $this->connect();
        $db->raw("CREATE TABLE defaulted_col_test (id INT IDENTITY(1,1) PRIMARY KEY, name VARCHAR(50) NOT NULL, qty INT NOT NULL DEFAULT 0, unit_count INT NOT NULL DEFAULT 0)")->execute();
        $db->addTable($db->createBlueprint('defaulted_col_test')->addColumns([
            'id' => [ColOption::TYPE => DataType::INT, ColOption::PRIMARY => true, ColOption::IDENTITY => true],
            'name' => [ColOption::TYPE => DataType::VARCHAR, ColOption::SIZE => 50],
            'qty' => [ColOption::TYPE => DataType::INT, ColOption::DEFAULT => 0],
            'unit_count' => [ColOption::TYPE => DataType::INT, ColOption::DEFAULT => 0],
        ]));

        $db->table('defaulted_col_test')->insert(['name' => 'given by key', 'unit-count' => 5])->execute();
        $db->table('defaulted_col_test')->insert(['name' => 'given by name', 'unit_count' => 7])->execute();

        $rows = $db->raw('SELECT unit_count FROM defaulted_col_test ORDER BY id')->execute()->getRows();
        $this->assertEquals([5, 7], array_map(fn($r) => (int) $r['unit_count'], $rows));
    }

    private function connect(): Database {
        $this->db = new Database(new ConnectionInfo('mssql', 'sa', getenv('SA_SQL_SERVER_PASSWORD'), 'testing_db', SQL_SERVER_HOST, 1433, [
            'TrustServerCertificate' => 'true'
        ]));
        foreach (['defaulted_col_test'] as $t) {
            $this->db->raw("IF OBJECT_ID('$t', 'U') IS NOT NULL DROP TABLE $t")->execute();
        }

        return $this->db;
    }
}
