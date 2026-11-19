<?php

namespace WebFiori\Tests\Database\MsSql;

use PHPUnit\Framework\TestCase;
use WebFiori\Database\ColOption;
use WebFiori\Database\ConnectionInfo;
use WebFiori\Database\ConnectionPool;
use WebFiori\Database\Database;
use WebFiori\Database\DataType;

/**
 * Issue #179: getLastInsertId() on tables without an identity column.
 */
class MSSQLLastInsertIdNoIdentityTest extends TestCase {
    private ?Database $db = null;

    protected function tearDown(): void {
        if ($this->db !== null) {
            foreach (['last_id_other_test', 'last_id_no_identity_test'] as $t) {
                $this->db->raw("IF OBJECT_ID('$t', 'U') IS NOT NULL DROP TABLE $t")->execute();
            }
            $this->db->close();
            $this->db = null;
        }
        ConnectionPool::reset();
    }

    /**
     * #179: a table with no identity column must still insert fine. A raw
     * insert gives 0 (no identity); a builder insert gives the key value.
     *
     * @test
     */
    public function testLastInsertIdOnTableWithoutIdentity() {
        $db = $this->connect();
        $db->raw("CREATE TABLE last_id_no_identity_test (code VARCHAR(20) NOT NULL PRIMARY KEY, name VARCHAR(50) NULL)")->execute();
        $db->addTable($db->createBlueprint('last_id_no_identity_test')->addColumns([
            'code' => [ColOption::TYPE => DataType::VARCHAR, ColOption::SIZE => 20, ColOption::PRIMARY => true],
            'name' => [ColOption::TYPE => DataType::VARCHAR, ColOption::SIZE => 50, ColOption::NULL => true],
        ]));

        $db->raw("INSERT INTO last_id_no_identity_test (code, name) VALUES ('B2', 'second')")->execute();
        $this->assertSame(0, $db->getLastInsertId());

        $db->table('last_id_no_identity_test')->insert(['code' => 'A1', 'name' => 'first'])->execute();
        $this->assertSame('A1', $db->getLastInsertId());

        $rows = $db->raw('SELECT code FROM last_id_no_identity_test ORDER BY code')->execute()->getRows();
        $this->assertCount(2, $rows);
    }

    /**
     * #179: a table with neither identity nor primary key has no id to return.
     *
     * @test
     */
    public function testLastInsertIdOnTableWithoutKey() {
        $db = $this->connect();
        $db->raw("CREATE TABLE last_id_no_identity_test (code VARCHAR(20) NULL, name VARCHAR(50) NULL)")->execute();
        $db->addTable($db->createBlueprint('last_id_no_identity_test')->addColumns([
            'code' => [ColOption::TYPE => DataType::VARCHAR, ColOption::SIZE => 20, ColOption::NULL => true],
            'name' => [ColOption::TYPE => DataType::VARCHAR, ColOption::SIZE => 50, ColOption::NULL => true],
        ]));

        $db->table('last_id_no_identity_test')->insert(['code' => 'A1', 'name' => 'first'])->execute();

        $this->assertSame(0, $db->getLastInsertId());
    }

    /**
     * #179: the id must not carry over from an earlier insert into another table
     * when the later insert has no identity.
     *
     * @test
     */
    public function testLastInsertIdIsNotStaleAcrossTables() {
        $db = $this->connect();
        $db->raw("CREATE TABLE last_id_other_test (id INT IDENTITY(1,1) PRIMARY KEY, a VARCHAR(50) NULL)")->execute();
        $db->raw("CREATE TABLE last_id_no_identity_test (code VARCHAR(20) NOT NULL PRIMARY KEY)")->execute();

        $db->raw("INSERT INTO last_id_other_test (a) VALUES ('x')")->execute();
        $this->assertEquals(1, $db->getLastInsertId());

        $db->raw("INSERT INTO last_id_no_identity_test (code) VALUES ('A1')")->execute();
        $this->assertEquals(0, $db->getLastInsertId());
    }

    private function connect(): Database {
        $this->db = new Database(new ConnectionInfo('mssql', 'sa', getenv('SA_SQL_SERVER_PASSWORD'), 'testing_db', SQL_SERVER_HOST, 1433, [
            'TrustServerCertificate' => 'true'
        ]));
        foreach (['last_id_other_test', 'last_id_no_identity_test'] as $t) {
            $this->db->raw("IF OBJECT_ID('$t', 'U') IS NOT NULL DROP TABLE $t")->execute();
        }

        return $this->db;
    }
}
