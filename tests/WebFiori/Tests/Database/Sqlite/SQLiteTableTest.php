<?php
namespace WebFiori\Tests\Database\Sqlite;

use PHPUnit\Framework\TestCase;
use WebFiori\Database\ColOption;
use WebFiori\Database\DataType;
use WebFiori\Database\Sqlite\SQLiteTable;

class SQLiteTableTest extends TestCase {
    /**
     * @test
     */
    public function testDefaultConstruction() {
        $table = new SQLiteTable('items');
        $this->assertEquals('"items"', $table->getName());
        $this->assertEquals('items', $table->getNormalName());
    }
    /**
     * @test
     */
    public function testAddColumnsBasic() {
        $table = new SQLiteTable('users');
        $table->addColumns([
            'id' => [ColOption::TYPE => DataType::INT, ColOption::PRIMARY => true, ColOption::AUTO_INCREMENT => true],
            'name' => [ColOption::TYPE => DataType::VARCHAR, ColOption::SIZE => 100],
        ]);
        $this->assertEquals(2, count($table->getCols()));
        $this->assertTrue($table->getColByKey('id')->isPrimary());
        $this->assertTrue($table->getColByKey('id')->isAutoInc());
    }
    /**
     * @test
     */
    public function testAddColumnsAllOptions() {
        $table = new SQLiteTable('t');
        $table->addColumns([
            'a' => [ColOption::TYPE => DataType::INT, ColOption::PRIMARY => true],
            'b' => [ColOption::TYPE => DataType::VARCHAR, ColOption::SIZE => 50, ColOption::UNIQUE => true],
            'c' => [ColOption::TYPE => DataType::DECIMAL, ColOption::NULL => true],
            'd' => [ColOption::TYPE => DataType::VARCHAR, ColOption::DEFAULT => 'hello'],
            'e' => [ColOption::TYPE => DataType::INT, ColOption::COMMENT => 'A comment'],
        ]);
        $this->assertTrue($table->getColByKey('a')->isPrimary());
        $this->assertTrue($table->getColByKey('b')->isUnique());
        $this->assertTrue($table->getColByKey('c')->isNull());
        $this->assertEquals('hello', $table->getColByKey('d')->getDefault());
        $this->assertEquals('A comment', $table->getColByKey('e')->getComment());
    }
    /**
     * @test
     */
    public function testAddColumnsWithColumnInstance() {
        $table = new SQLiteTable('t');
        $col = new \WebFiori\Database\Sqlite\SQLiteColumn('custom', 'integer');
        $table->addColumns(['custom' => $col]);
        $this->assertNotNull($table->getColByKey('custom'));
    }
    /**
     * @test
     */
    public function testToSQLAutoIncrement() {
        $table = new SQLiteTable('users');
        $table->addColumns([
            'id' => [ColOption::TYPE => DataType::INT, ColOption::PRIMARY => true, ColOption::AUTO_INCREMENT => true],
            'name' => [ColOption::TYPE => DataType::VARCHAR, ColOption::SIZE => 100],
        ]);
        $sql = $table->toSQL();
        $this->assertStringContainsString('create table if not exists "users"', $sql);
        $this->assertStringContainsString('"id" integer primary key autoincrement', $sql);
        $this->assertStringContainsString('"name" text not null', $sql);
        // No separate PRIMARY KEY clause for single autoincrement
        $this->assertStringNotContainsString('primary key ("id")', $sql);
    }
    /**
     * @test
     */
    public function testToSQLCompositePK() {
        $table = new SQLiteTable('order_items');
        $table->addColumns([
            'order-id' => [ColOption::TYPE => DataType::INT, ColOption::PRIMARY => true],
            'product-id' => [ColOption::TYPE => DataType::INT, ColOption::PRIMARY => true],
            'qty' => [ColOption::TYPE => DataType::INT],
        ]);
        $sql = $table->toSQL();
        $this->assertStringContainsString('primary key ("order-id", "product-id")', $sql);
    }
    /**
     * @test
     */
    public function testToSQLNoPK() {
        $table = new SQLiteTable('logs');
        $table->addColumns([
            'msg' => [ColOption::TYPE => DataType::VARCHAR, ColOption::SIZE => 500],
        ]);
        $sql = $table->toSQL();
        $this->assertStringNotContainsString('primary key', $sql);
    }
    /**
     * @test
     */
    public function testToSQLWithFK() {
        $authors = new SQLiteTable('authors');
        $authors->addColumns([
            'id' => [ColOption::TYPE => DataType::INT, ColOption::PRIMARY => true, ColOption::AUTO_INCREMENT => true],
            'name' => [ColOption::TYPE => DataType::VARCHAR, ColOption::SIZE => 100],
        ]);

        $posts = new SQLiteTable('posts');
        $posts->addColumns([
            'id' => [ColOption::TYPE => DataType::INT, ColOption::PRIMARY => true, ColOption::AUTO_INCREMENT => true],
            'author-id' => [ColOption::TYPE => DataType::INT],
            'title' => [ColOption::TYPE => DataType::VARCHAR, ColOption::SIZE => 200],
        ]);
        $posts->addReference($authors, ['author-id' => 'id'], 'fk_author', 'cascade', 'set null');

        $sql = $posts->toSQL();
        $this->assertStringContainsString('foreign key ("author-id") references "authors" ("id")', $sql);
        $this->assertStringContainsString('on update cascade', $sql);
        $this->assertStringContainsString('on delete set null', $sql);
    }
    /**
     * @test
     */
    public function testToSQLWithUnique() {
        $table = new SQLiteTable('users');
        $table->addColumns([
            'id' => [ColOption::TYPE => DataType::INT, ColOption::PRIMARY => true, ColOption::AUTO_INCREMENT => true],
            'email' => [ColOption::TYPE => DataType::VARCHAR, ColOption::SIZE => 200, ColOption::UNIQUE => true],
        ]);
        $sql = $table->toSQL();
        $this->assertStringContainsString('"email" text not null unique', $sql);
    }
    /**
     * @test
     */
    public function testToSQLWithNullableAndDefault() {
        $table = new SQLiteTable('config');
        $table->addColumns([
            'key' => [ColOption::TYPE => DataType::VARCHAR, ColOption::SIZE => 50],
            'value' => [ColOption::TYPE => DataType::VARCHAR, ColOption::SIZE => 200, ColOption::NULL => true, ColOption::DEFAULT => 'none'],
        ]);
        $sql = $table->toSQL();
        $this->assertStringContainsString("default 'none'", $sql);
    }
    /**
     * @test
     */
    public function testAddColumnsWithBoolDefault() {
        $table = new SQLiteTable('flags');
        $table->addColumns([
            'active' => [ColOption::TYPE => DataType::BOOL, ColOption::DEFAULT => true],
            'deleted' => [ColOption::TYPE => DataType::BOOL, ColOption::DEFAULT => false],
        ]);
        $this->assertEquals(1, $table->getColByKey('active')->getDefault());
        $this->assertEquals(0, $table->getColByKey('deleted')->getDefault());
    }
}
