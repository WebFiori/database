<?php

namespace WebFiori\Tests\Database\MySql;

use PHPUnit\Framework\TestCase;
use WebFiori\Database\ColOption;
use WebFiori\Database\ConnectionInfo;
use WebFiori\Database\ConnectionPool;
use WebFiori\Database\Database;
use WebFiori\Database\DataType;

/**
 * getLastInsertId() with UUID primary keys (issue #179, string|int return type).
 */
class MySQLLastInsertIdUuidTest extends TestCase {
    private ?Database $db = null;

    protected function tearDown(): void {
        if ($this->db !== null) {
            $this->db->table('last_id_uuid_test')->drop(true)->execute();
            $this->db->close();
            $this->db = null;
        }
        ConnectionPool::reset();
    }

    /**
     * @test
     */
    public function testAppGeneratedUuid() {
        $db = $this->connect(false);
        $uuid = '6f1c1e0a-3b7d-4c52-9a41-0d2f8e5b7c10';

        $db->table('last_id_uuid_test')->insert(['id' => $uuid, 'name' => 'first'])->execute();

        $this->assertSame($uuid, $db->getLastInsertId());
    }

    /**
     * @test
     */
    public function testUuidIsNotStaleBetweenInserts() {
        $db = $this->connect(false);
        $a = '11111111-1111-4111-8111-111111111111';
        $b = '22222222-2222-4222-8222-222222222222';

        $db->table('last_id_uuid_test')->insert(['id' => $a, 'name' => 'a'])->execute();
        $this->assertSame($a, $db->getLastInsertId());

        $db->table('last_id_uuid_test')->insert(['id' => $b, 'name' => 'b'])->execute();
        $this->assertSame($b, $db->getLastInsertId());
    }

    /**
     * A raw insert cannot report a UUID, and must not return the previous one.
     *
     * @test
     */
    public function testRawInsertAfterUuidInsertGivesZero() {
        $db = $this->connect(false);

        $db->table('last_id_uuid_test')->insert(['id' => '11111111-1111-4111-8111-111111111111', 'name' => 'a'])->execute();
        $db->raw("INSERT INTO last_id_uuid_test (id, name) VALUES ('22222222-2222-4222-8222-222222222222', 'b')")->execute();

        $this->assertSame(0, $db->getLastInsertId());
    }

    /**
     * An auto-increment key still gives an int.
     *
     * @test
     */
    public function testAutoIncrementStillReturnsInt() {
        $db = $this->connect(true);

        $db->table('last_id_uuid_test')->insert(['name' => 'first'])->execute();

        $this->assertSame(1, $db->getLastInsertId());
    }

    private function connect(bool $autoInc): Database {
        $this->db = new Database(new ConnectionInfo('mysql', 'root', getenv('MYSQL_ROOT_PASSWORD'), 'testing_db', '127.0.0.1', (int) (getenv('MYSQL_PORT') ?: 3306)));
        $idCol = $autoInc
            ? [ColOption::TYPE => DataType::INT, ColOption::PRIMARY => true, ColOption::AUTO_INCREMENT => true]
            : [ColOption::TYPE => DataType::VARCHAR, ColOption::SIZE => 36, ColOption::PRIMARY => true];
        $this->db->createBlueprint('last_id_uuid_test')->addColumns([
            'id' => $idCol,
            'name' => [ColOption::TYPE => DataType::VARCHAR, ColOption::SIZE => 50],
        ]);
        $this->db->table('last_id_uuid_test')->drop(true)->execute();
        $this->db->table('last_id_uuid_test')->createTable()->execute();

        return $this->db;
    }
}
