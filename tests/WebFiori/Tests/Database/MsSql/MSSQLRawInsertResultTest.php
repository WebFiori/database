<?php

namespace WebFiori\Tests\Database\MsSql;

use PHPUnit\Framework\TestCase;
use WebFiori\Database\ConnectionInfo;
use WebFiori\Database\ConnectionPool;
use WebFiori\Database\Database;
use WebFiori\Database\MultiResultSet;
use WebFiori\Database\ResultSet;

/**
 * Issue #185: the result of a raw INSERT must keep the shape it had before
 * getLastInsertId() started to capture the identity of raw inserts.
 */
class MSSQLRawInsertResultTest extends TestCase {
    private ?Database $db = null;

    protected function tearDown(): void {
        if ($this->db !== null) {
            $this->db->raw("IF OBJECT_ID('raw_insert_result_test', 'U') IS NOT NULL DROP TABLE raw_insert_result_test")->execute();
            $this->db->close();
            $this->db = null;
        }
        ConnectionPool::reset();
    }

    /**
     * A raw INSERT ... OUTPUT returns the output rows as a plain ResultSet,
     * and the identity is still available from getLastInsertId().
     *
     * @test
     */
    public function testInsertWithOutputReturnsResultSet() {
        $db = $this->connect();

        $result = $db->raw('INSERT INTO raw_insert_result_test (name) OUTPUT INSERTED.id VALUES (?)', ['a'])->execute();

        $this->assertInstanceOf(ResultSet::class, $result);
        $this->assertNotInstanceOf(MultiResultSet::class, $result);
        $this->assertEquals([['id' => 1]], $result->getRows());
        $this->assertSame(1, $db->getLastInsertId());
    }

    /**
     * Same with a statement that has no parameters, and a second insert.
     *
     * @test
     */
    public function testInsertWithOutputWithoutParams() {
        $db = $this->connect();

        $db->raw("INSERT INTO raw_insert_result_test (name) VALUES ('a')")->execute();
        $result = $db->raw("INSERT INTO raw_insert_result_test (name) OUTPUT INSERTED.id, INSERTED.name VALUES ('b')")->execute();

        $this->assertInstanceOf(ResultSet::class, $result);
        $this->assertEquals([['id' => 2, 'name' => 'b']], $result->getRows());
        $this->assertSame(2, $db->getLastInsertId());
    }

    /**
     * A plain raw INSERT has no rows, but the result must still be a ResultSet.
     *
     * @test
     */
    public function testInsertWithoutOutputReturnsEmptyResultSet() {
        $db = $this->connect();

        $result = $db->raw('INSERT INTO raw_insert_result_test (name) VALUES (?)', ['a'])->execute();

        $this->assertInstanceOf(ResultSet::class, $result);
        $this->assertNotInstanceOf(MultiResultSet::class, $result);
        $this->assertSame(0, $result->getRowsCount());
        $this->assertSame(1, $db->getLastInsertId());
    }

    /**
     * A multi-row insert with OUTPUT returns every output row in one ResultSet.
     *
     * @test
     */
    public function testMultiRowInsertWithOutput() {
        $db = $this->connect();

        $result = $db->raw("INSERT INTO raw_insert_result_test (name) OUTPUT INSERTED.id VALUES ('a'), ('b'), ('c')")->execute();

        $this->assertInstanceOf(ResultSet::class, $result);
        $this->assertEquals([['id' => 1], ['id' => 2], ['id' => 3]], $result->getRows());
    }

    /**
     * Statements written by the caller keep producing the results they
     * produced before: an INSERT followed by a SELECT gives two result sets.
     *
     * @test
     */
    public function testCallerBatchKeepsItsOwnResults() {
        $db = $this->connect();

        $result = $db->raw("INSERT INTO raw_insert_result_test (name) VALUES ('a'); SELECT name FROM raw_insert_result_test")->execute();

        $this->assertInstanceOf(MultiResultSet::class, $result);
        $this->assertCount(2, $result->getResultSets());
        $this->assertEquals([['name' => 'a']], $result->getResultSets()[1]->getRows());
    }

    /**
     * Parameters make the statement run through sp_executesql, which adds an
     * empty row-count result after the output rows of a multi-row insert.
     *
     * @test
     */
    public function testMultiRowInsertWithOutputAndParams() {
        $db = $this->connect();

        $result = $db->raw('INSERT INTO raw_insert_result_test (name) OUTPUT INSERTED.id, INSERTED.name VALUES (?), (?)', ['a', 'b'])->execute();

        $this->assertInstanceOf(ResultSet::class, $result);
        $this->assertNotInstanceOf(MultiResultSet::class, $result);
        $this->assertEquals([['id' => 1, 'name' => 'a'], ['id' => 2, 'name' => 'b']], $result->getRows());
        $this->assertSame(2, $db->getLastInsertId());
    }

    /**
     * A caller batch that uses a parameter keeps its own two result sets.
     *
     * @test
     */
    public function testCallerBatchWithParamsKeepsItsOwnResults() {
        $db = $this->connect();

        $result = $db->raw('INSERT INTO raw_insert_result_test (name) VALUES (?); SELECT name FROM raw_insert_result_test', ['a'])->execute();

        $this->assertInstanceOf(MultiResultSet::class, $result);
        $this->assertCount(2, $result->getResultSets());
        $this->assertEquals([['name' => 'a']], $result->getResultSets()[1]->getRows());
        $this->assertSame(1, $db->getLastInsertId());
    }

    private function connect(): Database {
        $this->db = new Database(new ConnectionInfo('mssql', 'sa', getenv('SA_SQL_SERVER_PASSWORD'), 'testing_db', SQL_SERVER_HOST, 1433, [
            'TrustServerCertificate' => 'true'
        ]));
        $this->db->raw("IF OBJECT_ID('raw_insert_result_test', 'U') IS NOT NULL DROP TABLE raw_insert_result_test")->execute();
        $this->db->raw("CREATE TABLE raw_insert_result_test (id INT IDENTITY(1,1) PRIMARY KEY, name NVARCHAR(50) NULL)")->execute();

        return $this->db;
    }
}
