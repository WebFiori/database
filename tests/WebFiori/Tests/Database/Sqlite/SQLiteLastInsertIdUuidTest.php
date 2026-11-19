<?php
namespace WebFiori\Tests\Database\Sqlite;

use PHPUnit\Framework\TestCase;
use WebFiori\Database\ColOption;
use WebFiori\Database\ConnectionInfo;
use WebFiori\Database\ConnectionPool;
use WebFiori\Database\Database;
use WebFiori\Database\DataType;

/**
 * getLastInsertId() with UUID primary keys (issue #179, string|int return type).
 *
 * SQLite reports the internal rowid for tables whose key is not an integer
 * primary key, so without special handling the UUID is never returned.
 */
class SQLiteLastInsertIdUuidTest extends TestCase {
    private Database $db;

    protected function setUp(): void {
        $this->db = new Database(new ConnectionInfo('sqlite', '', '', ':memory:'));
    }

    protected function tearDown(): void {
        $this->db->close();
        ConnectionPool::reset();
    }

    /**
     * @test
     */
    public function testAppGeneratedUuid() {
        $this->db->createBlueprint('docs')->addColumns([
            'id' => [ColOption::TYPE => DataType::CHAR, ColOption::SIZE => 36, ColOption::PRIMARY => true],
            'title' => [ColOption::TYPE => DataType::VARCHAR, ColOption::SIZE => 50],
        ]);
        $this->db->table('docs')->createTable()->execute();
        $uuid = '6f1c1e0a-3b7d-4c52-9a41-0d2f8e5b7c10';

        $this->db->table('docs')->insert(['id' => $uuid, 'title' => 'first'])->execute();

        $this->assertSame($uuid, $this->db->getLastInsertId());
    }

    /**
     * @test
     */
    public function testUuidIsNotStaleBetweenInserts() {
        $this->db->createBlueprint('docs')->addColumns([
            'id' => [ColOption::TYPE => DataType::CHAR, ColOption::SIZE => 36, ColOption::PRIMARY => true],
            'title' => [ColOption::TYPE => DataType::VARCHAR, ColOption::SIZE => 50],
        ]);
        $this->db->table('docs')->createTable()->execute();
        $a = '11111111-1111-4111-8111-111111111111';
        $b = '22222222-2222-4222-8222-222222222222';

        $this->db->table('docs')->insert(['id' => $a, 'title' => 'a'])->execute();
        $this->assertSame($a, $this->db->getLastInsertId());
        $this->db->table('docs')->insert(['id' => $b, 'title' => 'b'])->execute();
        $this->assertSame($b, $this->db->getLastInsertId());
    }

    /**
     * An auto-increment key still gives an int.
     *
     * @test
     */
    public function testAutoIncrementStillReturnsInt() {
        $this->db->createBlueprint('docs')->addColumns([
            'id' => [ColOption::TYPE => DataType::INT, ColOption::PRIMARY => true, ColOption::AUTO_INCREMENT => true],
            'title' => [ColOption::TYPE => DataType::VARCHAR, ColOption::SIZE => 50],
        ]);
        $this->db->table('docs')->createTable()->execute();

        $this->db->table('docs')->insert(['title' => 'first'])->execute();

        $this->assertSame(1, $this->db->getLastInsertId());
    }

    /**
     * A raw insert cannot report a UUID, and must not return the SQLite rowid
     * (which is not the key of a table with a text key).
     *
     * @test
     */
    public function testRawInsertIntoUuidTableGivesZero() {
        $this->db->createBlueprint('docs')->addColumns([
            'id' => [ColOption::TYPE => DataType::CHAR, ColOption::SIZE => 36, ColOption::PRIMARY => true],
            'title' => [ColOption::TYPE => DataType::VARCHAR, ColOption::SIZE => 50],
        ]);
        $this->db->table('docs')->createTable()->execute();

        $this->db->table('docs')->insert(['id' => '11111111-1111-4111-8111-111111111111', 'title' => 'a'])->execute();
        $this->db->raw("INSERT INTO docs (id, title) VALUES ('22222222-2222-4222-8222-222222222222', 'b')")->execute();
        $this->assertSame(0, $this->db->getLastInsertId());

        $this->db->raw('INSERT INTO "docs" (id, title) VALUES (?, ?)', ['33333333-3333-4333-8333-333333333333', 'c'])->execute();
        $this->assertSame(0, $this->db->getLastInsertId());
    }

    /**
     * A raw insert into a table with an integer key still gives the id.
     *
     * @test
     */
    public function testRawInsertIntoIntegerKeyTableGivesId() {
        $this->db->createBlueprint('docs')->addColumns([
            'id' => [ColOption::TYPE => DataType::INT, ColOption::PRIMARY => true, ColOption::AUTO_INCREMENT => true],
            'title' => [ColOption::TYPE => DataType::VARCHAR, ColOption::SIZE => 50],
        ]);
        $this->db->table('docs')->createTable()->execute();

        $this->db->raw("INSERT INTO docs (title) VALUES ('a')")->execute();
        $this->assertSame(1, $this->db->getLastInsertId());
        $this->db->raw("insert or ignore into `docs` (title) values ('b')")->execute();
        $this->assertSame(2, $this->db->getLastInsertId());
    }

    /**
     * A builder insert into a table with no key has no id.
     *
     * @test
     */
    public function testBuilderInsertIntoTableWithoutKeyGivesZero() {
        $this->db->createBlueprint('notes')->addColumns([
            'title' => [ColOption::TYPE => DataType::VARCHAR, ColOption::SIZE => 50],
        ]);
        $this->db->table('notes')->createTable()->execute();

        $this->db->table('notes')->insert(['title' => 'a'])->execute();

        $this->assertSame(0, $this->db->getLastInsertId());
    }
}
