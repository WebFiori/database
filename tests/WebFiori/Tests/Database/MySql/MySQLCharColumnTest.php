<?php

namespace WebFiori\Tests\Database\MySql;

use PHPUnit\Framework\TestCase;
use WebFiori\Database\ColOption;
use WebFiori\Database\ConnectionInfo;
use WebFiori\Database\ConnectionPool;
use WebFiori\Database\Database;
use WebFiori\Database\DataType;
use WebFiori\Database\MySql\MySQLColumn;

/**
 * DataType::CHAR on MySQL must honor its size, like VARCHAR does.
 */
class MySQLCharColumnTest extends TestCase {
    /**
     * @test
     */
    public function testCharSizeIsUsedInDdl() {
        $col = new MySQLColumn('id', DataType::CHAR, 36);

        $this->assertEquals(36, $col->getSize());
        $this->assertStringContainsString('char(36)', $col->asString());
    }

    /**
     * @test
     */
    public function testCharSizeCanBeSetAfterCreation() {
        $col = new MySQLColumn('code', DataType::CHAR);

        $this->assertTrue($col->setSize(8));
        $this->assertEquals(8, $col->getSize());
        $this->assertStringContainsString('char(8)', $col->asString());
    }

    /**
     * MySQL allows CHAR up to 255 characters.
     *
     * @test
     */
    public function testCharSizeAbove255IsRejected() {
        $col = new MySQLColumn('code', DataType::CHAR, 10);

        $this->assertFalse($col->setSize(256));
        $this->assertEquals(10, $col->getSize());
    }

    /**
     * @test
     */
    public function testCharDefaultIsQuoted() {
        $col = new MySQLColumn('code', DataType::CHAR, 4);
        $col->setDefault('abcd');

        $this->assertEquals('abcd', $col->getDefault());
        $this->assertStringContainsString("default 'abcd'", $col->asString());
    }

    /**
     * @test
     */
    public function testCharColumnCreatedFromBlueprintKeepsSize() {
        $db = new Database(new ConnectionInfo('mysql', 'root', 'x', 'testing_db', '127.0.0.1'));
        $db->createBlueprint('char_ddl')->addColumns([
            'id' => [ColOption::TYPE => DataType::CHAR, ColOption::SIZE => 36, ColOption::PRIMARY => true],
        ]);

        $this->assertStringContainsString('`id` char(36)', $db->table('char_ddl')->createTable()->getQuery());
    }

    /**
     * Stores and reads back a UUID in a CHAR(36) column.
     *
     * @test
     */
    public function testUuidRoundTripInCharColumn() {
        $db = new Database(new ConnectionInfo('mysql', 'root', getenv('MYSQL_ROOT_PASSWORD'), 'testing_db', '127.0.0.1', (int) (getenv('MYSQL_PORT') ?: 3306)));
        $db->createBlueprint('char_roundtrip')->addColumns([
            'id' => [ColOption::TYPE => DataType::CHAR, ColOption::SIZE => 36, ColOption::PRIMARY => true],
            'name' => [ColOption::TYPE => DataType::VARCHAR, ColOption::SIZE => 20],
        ]);
        $db->table('char_roundtrip')->drop(true)->execute();

        try {
            $db->table('char_roundtrip')->createTable()->execute();
            $uuid = '6f1c1e0a-3b7d-4c52-9a41-0d2f8e5b7c10';
            $db->table('char_roundtrip')->insert(['id' => $uuid, 'name' => 'x'])->execute();

            $rows = $db->table('char_roundtrip')->select()->execute()->getRows();
            $this->assertEquals($uuid, $rows[0]['id']);
        } finally {
            $db->table('char_roundtrip')->drop(true)->execute();
            $db->close();
            ConnectionPool::reset();
        }
    }
}
