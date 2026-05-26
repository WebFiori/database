<?php
namespace WebFiori\Tests\Database\Sqlite;

use PHPUnit\Framework\TestCase;
use WebFiori\Database\Sqlite\SQLiteColumn;

class SQLiteColumnTest extends TestCase {
    /**
     * @test
     */
    public function testDefaultConstruction() {
        $col = new SQLiteColumn();
        $this->assertEquals('"col"', $col->getName());
        $this->assertEquals('text', $col->getDatatype());
        $this->assertEquals(1, $col->getSize());
        $this->assertFalse($col->isPrimary());
        $this->assertFalse($col->isAutoInc());
        $this->assertFalse($col->isNull());
        $this->assertFalse($col->isUnique());
    }
    /**
     * @test
     */
    public function testConstructionWithParams() {
        $col = new SQLiteColumn('age', 'integer', 5);
        $this->assertEquals('"age"', $col->getName());
        $this->assertEquals('integer', $col->getDatatype());
        $this->assertEquals(5, $col->getSize());
    }
    /**
     * @test
     */
    public function testSetDatatypeMapping() {
        $col = new SQLiteColumn('c');

        $col->setDatatype('int');
        $this->assertEquals('integer', $col->getDatatype());

        $col->setDatatype('bigint');
        $this->assertEquals('integer', $col->getDatatype());

        $col->setDatatype('bit');
        $this->assertEquals('integer', $col->getDatatype());

        $col->setDatatype('bool');
        $this->assertEquals('integer', $col->getDatatype());

        $col->setDatatype('boolean');
        $this->assertEquals('integer', $col->getDatatype());

        $col->setDatatype('float');
        $this->assertEquals('real', $col->getDatatype());

        $col->setDatatype('double');
        $this->assertEquals('real', $col->getDatatype());

        $col->setDatatype('decimal');
        $this->assertEquals('real', $col->getDatatype());

        $col->setDatatype('money');
        $this->assertEquals('real', $col->getDatatype());

        $col->setDatatype('numeric');
        $this->assertEquals('real', $col->getDatatype());

        $col->setDatatype('blob');
        $this->assertEquals('blob', $col->getDatatype());

        $col->setDatatype('binary');
        $this->assertEquals('blob', $col->getDatatype());

        $col->setDatatype('varbinary');
        $this->assertEquals('blob', $col->getDatatype());

        $col->setDatatype('varchar');
        $this->assertEquals('text', $col->getDatatype());

        $col->setDatatype('text');
        $this->assertEquals('text', $col->getDatatype());

        $col->setDatatype('datetime');
        $this->assertEquals('text', $col->getDatatype());

        $col->setDatatype('nvarchar');
        $this->assertEquals('text', $col->getDatatype());

        $col->setDatatype('mixed');
        $this->assertEquals('text', $col->getDatatype());
    }
    /**
     * @test
     */
    public function testAsStringBasic() {
        $col = new SQLiteColumn('name', 'text');
        $this->assertEquals('"name" text not null', $col->asString());
    }
    /**
     * @test
     */
    public function testAsStringNullable() {
        $col = new SQLiteColumn('bio', 'text');
        $col->setIsNull(true);
        $this->assertEquals('"bio" text', $col->asString());
    }
    /**
     * @test
     */
    public function testAsStringUnique() {
        $col = new SQLiteColumn('email', 'text');
        $col->setIsUnique(true);
        $this->assertEquals('"email" text not null unique', $col->asString());
    }
    /**
     * @test
     */
    public function testAsStringDefaultString() {
        $col = new SQLiteColumn('status', 'text');
        $col->setDefault('active');
        $this->assertEquals('"status" text not null default \'active\'', $col->asString());
    }
    /**
     * @test
     */
    public function testAsStringDefaultNull() {
        $col = new SQLiteColumn('val', 'integer');
        $col->setDefault('null');
        $this->assertEquals('"val" integer not null default null', $col->asString());
    }
    /**
     * @test
     */
    public function testAsStringDefaultNumeric() {
        $col = new SQLiteColumn('count', 'integer');
        $col->setDefault(0);
        $this->assertEquals('"count" integer not null default 0', $col->asString());
    }
    /**
     * @test
     */
    public function testAsStringAutoIncrement() {
        $col = new SQLiteColumn('id', 'integer');
        $col->setIsAutoInc(true);
        $this->assertEquals('"id" integer primary key autoincrement', $col->asString());
        $this->assertTrue($col->isPrimary());
        $this->assertTrue($col->isAutoInc());
    }
    /**
     * @test
     */
    public function testGetNameWithTablePrefix() {
        $table = new \WebFiori\Database\Sqlite\SQLiteTable('users');
        $table->addColumns(['name' => [\WebFiori\Database\ColOption::TYPE => 'varchar']]);
        $col = $table->getColByKey('name');
        $col->setWithTablePrefix(true);
        $this->assertEquals('"users"."name"', $col->getName());
    }
    /**
     * @test
     */
    public function testGetNameWithoutPrefix() {
        $col = new SQLiteColumn('age', 'integer');
        $col->setWithTablePrefix(false);
        $this->assertEquals('"age"', $col->getName());
    }
    /**
     * @test
     */
    public function testGetNameEmpty() {
        $col = new SQLiteColumn('', 'text');
        $this->assertEquals('', $col->getName());
    }
    /**
     * @test
     */
    public function testDoubleQuoteStatic() {
        $this->assertEquals('"users"."name"', SQLiteColumn::doubleQuote('users.name'));
        $this->assertEquals('"name"', SQLiteColumn::doubleQuote('name'));
        $this->assertEquals('', SQLiteColumn::doubleQuote(''));
        $this->assertEquals('"a"."b"."c"', SQLiteColumn::doubleQuote('a.b.c'));
    }
    /**
     * @test
     */
    public function testGetPHPType() {
        $col = new SQLiteColumn('c', 'integer');
        $this->assertEquals('int', $col->getPHPType());

        $col->setDatatype('real');
        $this->assertEquals('float', $col->getPHPType());

        $col->setDatatype('text');
        $this->assertEquals('string', $col->getPHPType());

        $col->setDatatype('blob');
        $this->assertEquals('string', $col->getPHPType());
    }
    /**
     * @test
     */
    public function testGetPHPTypeNullable() {
        $col = new SQLiteColumn('c', 'integer');
        $col->setIsNull(true);
        $this->assertEquals('int|null', $col->getPHPType());

        $col->setDatatype('text');
        $this->assertEquals('string|null', $col->getPHPType());
    }
    /**
     * @test
     */
    public function testCleanValueInteger() {
        $col = new SQLiteColumn('c', 'integer');
        $this->assertEquals(42, $col->cleanValue('42'));
        $this->assertNull($col->cleanValue(null));
    }
    /**
     * @test
     */
    public function testCleanValueReal() {
        $col = new SQLiteColumn('c', 'float');
        $this->assertEquals(3.14, $col->cleanValue('3.14'));
    }
    /**
     * @test
     */
    public function testCleanValueText() {
        $col = new SQLiteColumn('c', 'text');
        $this->assertEquals('hello', $col->cleanValue('hello'));
        $this->assertEquals('123', $col->cleanValue(123));
    }
    /**
     * @test
     */
    public function testCleanValueArray() {
        $col = new SQLiteColumn('c', 'integer');
        $result = $col->cleanValue([1, '2', '3']);
        $this->assertEquals([1, 2, 3], $result);
    }
    /**
     * @test
     */
    public function testSetAutoUpdate() {
        $col = new SQLiteColumn('c', 'text');
        $col->setAutoUpdate(true);
        $this->assertFalse($col->isAutoUpdate());
    }
    /**
     * @test
     */
    public function testSetIsAutoIncFalse() {
        $col = new SQLiteColumn('id', 'integer');
        $col->setIsAutoInc(true);
        $this->assertTrue($col->isAutoInc());
        $col->setIsAutoInc(false);
        $this->assertFalse($col->isAutoInc());
    }
}
