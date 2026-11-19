<?php

namespace WebFiori\Tests\Database\MsSql;

use PHPUnit\Framework\TestCase;
use WebFiori\Database\ColOption;
use WebFiori\Database\ConnectionInfo;
use WebFiori\Database\ConnectionPool;
use WebFiori\Database\Database;
use WebFiori\Database\DataType;

/**
 * getLastInsertId() with UUID primary keys (issue #179, string|int return type).
 *
 * Contract under test: after a query-builder insert, getLastInsertId() returns
 * the generated identity as int, or the inserted row's single primary key value
 * as string when the key is not an identity. It returns 0 when neither exists.
 */
class MSSQLLastInsertIdUuidTest extends TestCase {
    const UUID_PATTERN = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i';
    private ?Database $db = null;

    protected function tearDown(): void {
        if ($this->db !== null) {
            $this->db->raw("IF OBJECT_ID('last_id_uuid_test', 'U') IS NOT NULL DROP TABLE last_id_uuid_test")->execute();
            $this->db->close();
            $this->db = null;
        }
        ConnectionPool::reset();
    }

    /**
     * The application generates the UUID and supplies it in the insert.
     *
     * @test
     */
    public function testAppGeneratedUuid() {
        $db = $this->connect("CREATE TABLE last_id_uuid_test (id CHAR(36) NOT NULL PRIMARY KEY, name VARCHAR(50) NULL)");
        $uuid = '6f1c1e0a-3b7d-4c52-9a41-0d2f8e5b7c10';

        $db->table('last_id_uuid_test')->insert(['id' => $uuid, 'name' => 'first'])->execute();

        $this->assertSame($uuid, $db->getLastInsertId());
    }

    /**
     * SQL Server generates the UUID (uniqueidentifier DEFAULT NEWID()). It can
     * only be known by reading it back in the same batch as the insert.
     *
     * @test
     */
    public function testServerGeneratedUuid() {
        $db = $this->connect("CREATE TABLE last_id_uuid_test (id UNIQUEIDENTIFIER NOT NULL DEFAULT NEWID() PRIMARY KEY, name VARCHAR(50) NULL)");

        $db->table('last_id_uuid_test')->insert(['name' => 'first'])->execute();
        $id1 = $db->getLastInsertId();

        $db->table('last_id_uuid_test')->insert(['name' => 'second'])->execute();
        $id2 = $db->getLastInsertId();

        $this->assertIsString($id1);
        $this->assertMatchesRegularExpression(self::UUID_PATTERN, $id1);
        $this->assertNotSame($id1, $id2);

        $rows = $db->raw('SELECT CONVERT(VARCHAR(36), id) AS id, name FROM last_id_uuid_test ORDER BY name')->execute()->getRows();
        $byName = array_column($rows, 'id', 'name');
        $this->assertSame(strtolower($byName['second']), strtolower($id2));
        $this->assertSame(strtolower($byName['first']), strtolower($id1));
    }

    /**
     * An identity key still gives an int, not a numeric string.
     *
     * @test
     */
    public function testIdentityStillReturnsInt() {
        $db = $this->connect("CREATE TABLE last_id_uuid_test (id INT IDENTITY(1,1) PRIMARY KEY, name VARCHAR(50) NULL)", true);

        $db->table('last_id_uuid_test')->insert(['name' => 'first'])->execute();

        $this->assertSame(1, $db->getLastInsertId());
    }

    /**
     * Two inserts in a row: the second id must replace the first.
     *
     * @test
     */
    public function testUuidIsNotStaleBetweenInserts() {
        $db = $this->connect("CREATE TABLE last_id_uuid_test (id CHAR(36) NOT NULL PRIMARY KEY, name VARCHAR(50) NULL)");
        $a = '11111111-1111-4111-8111-111111111111';
        $b = '22222222-2222-4222-8222-222222222222';

        $db->table('last_id_uuid_test')->insert(['id' => $a, 'name' => 'a'])->execute();
        $this->assertSame($a, $db->getLastInsertId());

        $db->table('last_id_uuid_test')->insert(['id' => $b, 'name' => 'b'])->execute();
        $this->assertSame($b, $db->getLastInsertId());
    }

    /**
     * A table with a composite key has no single id to return.
     *
     * @test
     */
    public function testCompositeKeyReturnsZero() {
        $this->db = new Database(new ConnectionInfo('mssql', 'sa', getenv('SA_SQL_SERVER_PASSWORD'), 'testing_db', SQL_SERVER_HOST, 1433, [
            'TrustServerCertificate' => 'true'
        ]));
        $db = $this->db;
        $db->raw("IF OBJECT_ID('last_id_uuid_test', 'U') IS NOT NULL DROP TABLE last_id_uuid_test")->execute();
        $db->raw("CREATE TABLE last_id_uuid_test (a CHAR(36) NOT NULL, b CHAR(36) NOT NULL, PRIMARY KEY (a, b))")->execute();
        $db->addTable($db->createBlueprint('last_id_uuid_test')->addColumns([
            'a' => [ColOption::TYPE => DataType::CHAR, ColOption::SIZE => 36, ColOption::PRIMARY => true],
            'b' => [ColOption::TYPE => DataType::CHAR, ColOption::SIZE => 36, ColOption::PRIMARY => true],
        ]));

        $db->table('last_id_uuid_test')->insert([
            'a' => '11111111-1111-4111-8111-111111111111',
            'b' => '22222222-2222-4222-8222-222222222222'
        ])->execute();

        $this->assertSame(0, $db->getLastInsertId());
    }

    private function connect(string $createSql, bool $identity = false): Database {
        $this->db = new Database(new ConnectionInfo('mssql', 'sa', getenv('SA_SQL_SERVER_PASSWORD'), 'testing_db', SQL_SERVER_HOST, 1433, [
            'TrustServerCertificate' => 'true'
        ]));
        $this->db->raw("IF OBJECT_ID('last_id_uuid_test', 'U') IS NOT NULL DROP TABLE last_id_uuid_test")->execute();
        $this->db->raw($createSql)->execute();

        $idCol = $identity
            ? [ColOption::TYPE => DataType::INT, ColOption::PRIMARY => true, ColOption::IDENTITY => true]
            : [ColOption::TYPE => DataType::CHAR, ColOption::SIZE => 36, ColOption::PRIMARY => true];
        $this->db->addTable($this->db->createBlueprint('last_id_uuid_test')->addColumns([
            'id' => $idCol,
            'name' => [ColOption::TYPE => DataType::VARCHAR, ColOption::SIZE => 50, ColOption::NULL => true],
        ]));

        return $this->db;
    }
}
