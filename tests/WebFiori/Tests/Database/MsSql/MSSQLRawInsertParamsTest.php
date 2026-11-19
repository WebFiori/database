<?php

namespace WebFiori\Tests\Database\MsSql;

use PHPUnit\Framework\TestCase;
use WebFiori\Database\ColOption;
use WebFiori\Database\ConnectionInfo;
use WebFiori\Database\ConnectionPool;
use WebFiori\Database\Database;
use WebFiori\Database\DataType;

/**
 * Issue #178: a raw() INSERT must be bound with its own parameters.
 */
class MSSQLRawInsertParamsTest extends TestCase {
    private ?Database $db = null;

    protected function tearDown(): void {
        if ($this->db !== null) {
            foreach (['raw_insert_params_test'] as $t) {
                $this->db->raw("IF OBJECT_ID('$t', 'U') IS NOT NULL DROP TABLE $t")->execute();
            }
            $this->db->close();
            $this->db = null;
        }
        ConnectionPool::reset();
    }

    /**
     * #178: a raw() INSERT must be bound with its own parameters, not with the
     * parameters of the previous query-builder insert.
     *
     * @test
     */
    public function testRawInsertUsesOwnParamsAfterBuilderInsert() {
        $db = $this->connect();
        $db->raw("CREATE TABLE raw_insert_params_test (id INT IDENTITY(1,1) PRIMARY KEY, a VARCHAR(50) NULL, b VARCHAR(50) NULL, c VARCHAR(50) NULL, d VARCHAR(50) NULL)")->execute();
        $opts = [ColOption::TYPE => DataType::VARCHAR, ColOption::SIZE => 50, ColOption::NULL => true];
        $db->addTable($db->createBlueprint('raw_insert_params_test')->addColumns([
            'id' => [ColOption::TYPE => DataType::INT, ColOption::PRIMARY => true, ColOption::IDENTITY => true],
            'a' => $opts, 'b' => $opts, 'c' => $opts, 'd' => $opts,
        ]));

        $db->table('raw_insert_params_test')->insert([
            'a' => 'builder-a', 'b' => 'builder-b', 'c' => 'builder-c', 'd' => 'builder-d'
        ])->execute();
        $db->raw('INSERT INTO raw_insert_params_test (a, b, c, d) VALUES (?, ?, ?, ?)', ['raw-a', 'raw-b', 'raw-c', 'raw-d'])->execute();
        $db->raw('INSERT INTO raw_insert_params_test (a, b) VALUES (?, ?)', ['raw-a2', 'raw-b2'])->execute();

        $rows = $db->raw('SELECT a, b, c, d FROM raw_insert_params_test ORDER BY id')->execute()->getRows();

        $this->assertCount(3, $rows);
        $this->assertEquals(['a' => 'raw-a', 'b' => 'raw-b', 'c' => 'raw-c', 'd' => 'raw-d'], $rows[1]);
        $this->assertEquals(['a' => 'raw-a2', 'b' => 'raw-b2', 'c' => null, 'd' => null], $rows[2]);
    }

    /**
     * #178: a raw INSERT with more placeholders than the previous builder insert
     * had values must not fail.
     *
     * @test
     */
    public function testRawInsertWithMoreParamsThanBuilderInsert() {
        $db = $this->connect();
        $db->raw("CREATE TABLE raw_insert_params_test (id INT IDENTITY(1,1) PRIMARY KEY, a VARCHAR(50) NULL, b VARCHAR(50) NULL, c VARCHAR(50) NULL, d VARCHAR(50) NULL)")->execute();
        $opts = [ColOption::TYPE => DataType::VARCHAR, ColOption::SIZE => 50, ColOption::NULL => true];
        $db->addTable($db->createBlueprint('raw_insert_params_test')->addColumns([
            'id' => [ColOption::TYPE => DataType::INT, ColOption::PRIMARY => true, ColOption::IDENTITY => true],
            'a' => $opts, 'b' => $opts, 'c' => $opts, 'd' => $opts,
        ]));

        $db->table('raw_insert_params_test')->insert(['a' => 'builder-a'])->execute();
        $db->raw('INSERT INTO raw_insert_params_test (a, b, c, d) VALUES (?, ?, ?, ?)', ['raw-a', 'raw-b', 'raw-c', 'raw-d'])->execute();

        $rows = $db->raw('SELECT a, b, c, d FROM raw_insert_params_test ORDER BY id')->execute()->getRows();
        $this->assertCount(2, $rows);
        $this->assertEquals(['a' => 'raw-a', 'b' => 'raw-b', 'c' => 'raw-c', 'd' => 'raw-d'], $rows[1]);
    }

    private function connect(): Database {
        $this->db = new Database(new ConnectionInfo('mssql', 'sa', getenv('SA_SQL_SERVER_PASSWORD'), 'testing_db', SQL_SERVER_HOST, 1433, [
            'TrustServerCertificate' => 'true'
        ]));
        foreach (['raw_insert_params_test'] as $t) {
            $this->db->raw("IF OBJECT_ID('$t', 'U') IS NOT NULL DROP TABLE $t")->execute();
        }

        return $this->db;
    }
}
