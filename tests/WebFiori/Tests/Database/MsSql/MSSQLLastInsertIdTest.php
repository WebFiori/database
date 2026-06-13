<?php

namespace WebFiori\Tests\Database\MsSql;

use PHPUnit\Framework\TestCase;
use WebFiori\Database\ConnectionInfo;
use WebFiori\Database\Database;
use WebFiori\Database\MsSql\MSSQLConnection;

class MSSQLLastInsertIdTest extends TestCase {
    /**
     * @test
     */
    public function testGetLastInsertIdAfterInsert() {
        if (PHP_MAJOR_VERSION == 5) {
            $this->markTestSkipped('PHP 5 has no MSSQL driver.');
        }
        $connInfo = new ConnectionInfo('mssql', 'sa', getenv('SA_SQL_SERVER_PASSWORD'), 'testing_db', SQL_SERVER_HOST, 1433, [
            'TrustServerCertificate' => 'true'
        ]);
        $db = new Database($connInfo);

        $db->raw("IF OBJECT_ID('last_id_test', 'U') IS NOT NULL DROP TABLE last_id_test")->execute();
        $db->raw("CREATE TABLE last_id_test (id INT IDENTITY(1,1) PRIMARY KEY, name VARCHAR(50))")->execute();

        $db->raw("INSERT INTO last_id_test (name) VALUES ('first')")->execute();
        $id1 = $db->getLastInsertId();
        $this->assertGreaterThan(0, $id1);

        $db->raw("INSERT INTO last_id_test (name) VALUES ('second')")->execute();
        $id2 = $db->getLastInsertId();
        $this->assertGreaterThan($id1, $id2);

        $db->raw("DROP TABLE last_id_test")->execute();
        $db->close();
        \WebFiori\Database\ConnectionPool::reset();
    }

    /**
     * @test
     */
    public function testGetLastInsertIdClosedConnection() {
        if (PHP_MAJOR_VERSION == 5) {
            $this->markTestSkipped('PHP 5 has no MSSQL driver.');
        }
        $connInfo = new ConnectionInfo('mssql', 'sa', getenv('SA_SQL_SERVER_PASSWORD'), 'testing_db', SQL_SERVER_HOST, 1433, [
            'TrustServerCertificate' => 'true'
        ]);
        $conn = new MSSQLConnection($connInfo);
        $conn->close();
        $this->assertEquals(0, $conn->getLastInsertId());
    }
}
