<?php
namespace WebFiori\Tests\Database\Sqlite;

use PHPUnit\Framework\TestCase;
use WebFiori\Database\ColOption;
use WebFiori\Database\ConnectionInfo;
use WebFiori\Database\Database;
use WebFiori\Database\DatabaseException;
use WebFiori\Database\DataType;
use WebFiori\Database\Sqlite\SQLiteQuery;
use WebFiori\Database\Sqlite\SQLiteTable;
use WebFiori\Database\Sqlite\SQLiteInsertBuilder;

class SQLiteQueryTest extends TestCase {
    private static Database $db;

    public static function setUpBeforeClass(): void {
        $conn = new ConnectionInfo('sqlite', '', '', ':memory:');
        self::$db = new Database($conn);
        self::$db->createBlueprint('users')->addColumns([
            'id' => [ColOption::TYPE => DataType::INT, ColOption::PRIMARY => true, ColOption::AUTO_INCREMENT => true],
            'name' => [ColOption::TYPE => DataType::VARCHAR, ColOption::SIZE => 100],
            'age' => [ColOption::TYPE => DataType::INT],
        ]);
    }
    /**
     * @test
     */
    public function testSelectQuery() {
        self::$db->table('users')->select();
        $this->assertEquals('select * from "users"', self::$db->getLastQuery());
    }
    /**
     * @test
     */
    public function testSelectWithColumns() {
        self::$db->table('users')->select(['name', 'age']);
        $this->assertStringContainsString('"users"."name"', self::$db->getLastQuery());
        $this->assertStringContainsString('"users"."age"', self::$db->getLastQuery());
    }
    /**
     * @test
     */
    public function testSelectWithWhere() {
        self::$db->table('users')->select()->where('id', 1);
        $this->assertEquals('select * from "users" where "users"."id" = ?', self::$db->getLastQuery());
    }
    /**
     * @test
     */
    public function testSelectWithAndWhere() {
        self::$db->table('users')->select()->where('id', 1)->andWhere('name', 'test');
        $this->assertStringContainsString('where "users"."id" = ? and "users"."name" = ?', self::$db->getLastQuery());
    }
    /**
     * @test
     */
    public function testSelectWithOrWhere() {
        self::$db->table('users')->select()->where('age', 20)->orWhere('age', 30);
        $this->assertStringContainsString('or "users"."age" = ?', self::$db->getLastQuery());
    }
    /**
     * @test
     */
    public function testSelectWithLimit() {
        self::$db->table('users')->select()->limit(10);
        $this->assertStringContainsString('LIMIT 10', self::$db->getQueryGenerator()->getQuery());
    }
    /**
     * @test
     */
    public function testSelectWithLimitAndOffset() {
        self::$db->table('users')->select()->limit(10)->offset(5);
        $query = self::$db->getQueryGenerator()->getQuery();
        $this->assertStringContainsString('LIMIT 10', $query);
        $this->assertStringContainsString('OFFSET 5', $query);
    }
    /**
     * @test
     */
    public function testSelectNoLimitNoOffset() {
        self::$db->table('users')->select();
        $query = self::$db->getQueryGenerator()->getQuery();
        $this->assertStringNotContainsString('LIMIT', $query);
        $this->assertStringNotContainsString('OFFSET', $query);
    }
    /**
     * @test
     */
    public function testInsertQuery() {
        self::$db->table('users')->insert(['name' => 'Test', 'age' => 25]);
        $this->assertStringContainsString('insert into "users"', self::$db->getLastQuery());
        $this->assertStringContainsString('values (?, ?)', self::$db->getLastQuery());
    }
    /**
     * @test
     */
    public function testUpdateQuery() {
        self::$db->table('users')->update(['name' => 'New'])->where('id', 1);
        $this->assertStringContainsString('update "users" set "name" = ?', self::$db->getLastQuery());
        $this->assertStringContainsString('where "users"."id" = ?', self::$db->getLastQuery());
    }
    /**
     * @test
     */
    public function testUpdateWithNull() {
        self::$db->table('users')->update(['name' => null])->where('id', 1);
        $this->assertStringContainsString('"name" = null', self::$db->getLastQuery());
    }
    /**
     * @test
     */
    public function testUpdateWithDynamicColumn() {
        self::$db->table('users')->update(['name' => 'X', 'new-col' => 'val']);
        $this->assertStringContainsString('update "users" set', self::$db->getLastQuery());
    }
    /**
     * @test
     */
    public function testDeleteQuery() {
        self::$db->table('users')->delete()->where('id', 1);
        $this->assertEquals('delete from "users" where "users"."id" = ?', self::$db->getLastQuery());
    }
    /**
     * @test
     */
    public function testDropQuery() {
        self::$db->table('users')->drop();
        $this->assertEquals('drop table "users";', self::$db->getLastQuery());
    }
    /**
     * @test
     */
    public function testCreateTableQuery() {
        self::$db->table('users')->createTable();
        $this->assertStringContainsString('create table if not exists "users"', self::$db->getLastQuery());
    }
    /**
     * @test
     */
    public function testAddCol() {
        $q = self::$db->getQueryGenerator();
        $q->table('users')->addCol('name');
        $this->assertStringContainsString('alter table "users" add column', self::$db->getLastQuery());
    }
    /**
     * @test
     */
    public function testAddColNotExist() {
        $this->expectException(DatabaseException::class);
        self::$db->getQueryGenerator()->table('users')->addCol('not-exist');
    }
    /**
     * @test
     */
    public function testRenameCol() {
        self::$db->getTable('users')->getColByKey('name')->setName('username');
        self::$db->getQueryGenerator()->table('users')->renameCol('name');
        $this->assertStringContainsString('alter table "users" rename column', self::$db->getLastQuery());
    }
    /**
     * @test
     */
    public function testRenameColNotExist() {
        $this->expectException(DatabaseException::class);
        self::$db->getQueryGenerator()->table('users')->renameCol('xyz');
    }
    /**
     * @test
     */
    public function testRenameColNoOldName() {
        // When column was never renamed, getOldName() returns current name
        // so rename just produces a no-op rename SQL
        $table = new SQLiteTable('t');
        $table->addColumns(['col-a' => [ColOption::TYPE => DataType::INT]]);
        $q = new SQLiteQuery();
        $q->setTable($table);
        $q->renameCol('col-a');
        $this->assertStringContainsString('rename column', $q->getQuery());
    }
    /**
     * @test
     */
    public function testModifyColThrows() {
        $this->expectException(DatabaseException::class);
        $this->expectExceptionMessage('SQLite does not support ALTER TABLE MODIFY COLUMN');
        self::$db->getQueryGenerator()->table('users')->modifyCol('name');
    }
    /**
     * @test
     */
    public function testAddPrimaryKeyThrows() {
        $this->expectException(DatabaseException::class);
        self::$db->getQueryGenerator()->addPrimaryKey('pk', ['id']);
    }
    /**
     * @test
     */
    public function testDropPrimaryKeyThrows() {
        $this->expectException(DatabaseException::class);
        self::$db->getQueryGenerator()->dropPrimaryKey('pk');
    }
    /**
     * @test
     */
    public function testCopyQuery() {
        $q = self::$db->getQueryGenerator();
        $copy = $q->copyQuery();
        $this->assertInstanceOf(SQLiteQuery::class, $copy);
    }
    /**
     * @test
     */
    public function testBindings() {
        $q = new SQLiteQuery();
        $q->resetBinding();
        $this->assertEquals([], $q->getBindings());

        $q->setBindings([1, 2, 3]);
        $this->assertEquals([1, 2, 3], $q->getBindings());

        $q->setBindings([0], 'first');
        $this->assertEquals([0, 1, 2, 3], $q->getBindings());

        $q->setBindings([4], 'end');
        $this->assertEquals([0, 1, 2, 3, 4], $q->getBindings());

        $q->setBindings([9]);
        $this->assertEquals([9], $q->getBindings());
    }
    /**
     * @test
     */
    public function testInsertBuilderParseValues() {
        $table = new SQLiteTable('t');
        $table->addColumns([
            'a' => [ColOption::TYPE => DataType::INT],
            'b' => [ColOption::TYPE => DataType::VARCHAR],
        ]);
        $builder = new SQLiteInsertBuilder($table, ['a' => 1, 'b' => 'hello']);
        $params = $builder->getQueryParams();
        $this->assertContains(1, $params);
        $this->assertContains('hello', $params);
    }
}
