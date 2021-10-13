<?php
namespace mssql;

use PHPUnit\Framework\TestCase;
use webfiori\database\mssql\MSSQLTable;
use webfiori\database\mssql\MSSQLColumn;
/**
 * Description of MSSQLTableTest
 *
 * @author Ibrahim
 */
class MSSQLTableTest extends TestCase {
    /**
     * @test
     */
    public function testAddColumn00() {
        $table = new MSSQLTable();
        $this->assertTrue($table->addColumn('new-col', new MSSQLColumn()));
        $this->assertFalse($table->addColumn('new-col-2', new MSSQLColumn()));
        $this->assertTrue($table->addColumn('new-col-2', new MSSQLColumn('col_2', 'varchar')));
        $this->assertFalse($table->addColumn('new-col-2', new MSSQLColumn('col_3', 'varchar')));

        return $table;
    }
    /**
     * @test
     */
    public function testAddColumn01() {
        $table = new MSSQLTable();
        $this->assertTrue($table->addColumn(' new-col ', new MSSQLColumn()));
        $this->assertFalse($table->addColumn('invalid key', new MSSQLColumn('col_2')));
        $this->assertFalse($table->addColumn('-', new MSSQLColumn('col_2')));
        $this->assertFalse($table->addColumn('--', new MSSQLColumn('col_2')));

        return $table;
    }
    /**
     * @test
     */
    public function testAddColumn02() {
        $table = new MSSQLTable();
        $table->addColumns([
            'id' => new MSSQLColumn('col-01'),
            'name' => new MSSQLColumn('col-02', 'int')
        ]);
        $this->assertTrue($table->hasColumnWithKey('id'));
        $this->assertTrue($table->hasColumnWithKey('name'));
        $this->assertTrue($table->hasColumn('col-01'));
        $this->assertTrue($table->hasColumn('col-02'));

        return $table;
    }
    /**
     * @test
     */
    public function testConstructor01() {
        $table = new MSSQLTable('valid_name');
        $this->assertEquals('[valid_name]',$table->getName());
    }
    /**
     * @test
     */
    public function testConstructor02() {
        $table = new MSSQLTable('    another_Valid_Name    ');
        $this->assertNull($table->getOldName());
        $this->assertEquals('[another_Valid_Name]',$table->getName());
        $table->setName('new-name');
        $this->assertEquals('[another_Valid_Name]', $table->getOldName());
    }
    /**
     * @test
     */
    public function testFK1() {
        $table = new MSSQLTable('users');
        $table->addColumns([
            'user-id' => [
                'type' => 'int',
                'size' => 11,
                'primary' => true,
                'auto-inc' => true
            ],
            'email' => [
                'type' => 'varchar',
                'size' => 256,
                'is-unique' => true
            ],
            'username' => [
                'type' => 'varchar',
                'size' => 20,
                'is-unique' => true
            ],
            'password' => [
                'type' => 'varchar',
                'size' => 256
            ],
            'created-on' => [
                'type' => 'datetime2',
                'default' => 'now',
            ],
        ]);
        $table2 = new MSSQLTable('t');
        $table2->addColumns([
            'user-id' => [
                'type' => 'int',
                'size' => 11,
                'primary' => true,
                'auto-inc' => true
            ],
            'email' => [
                'type' => 'varchar',
                'size' => 256,
                'is-unique' => true
            ],
        ]);
        $table2->addReference($table, ['user-id', 'email'], 'fk_ok', 'cascade', 'cascade');
        $key = $table2->getForeignKey('fk_ok');
        $this->assertEquals(2, count($key->getSourceCols()));
        $this->assertEquals("if not exists (select * from sysobjects where name='t' and xtype='U')\n"
                . "create table [t] (\n"
                . "    [user_id] [int] not null,\n"
                . "    [email] [varchar](256) not null,\n"
                . "    constraint t_pk primary key clustered([user_id]) on [PRIMARY],\n"
                . "    constraint fk_ok foreign key ([user_id], [email]) references [users] ([user_id], [email]) on update cascade on delete cascade,\n"
                . "    constraint AK_t unique (email)\n"
                . ")\n"
                . "", $table2->toSQL());
    }
    /**
     * @test
     */
    public function testFK2() {
        $table = new MSSQLTable('users');
        $table->addColumns([
            'user-id' => [
                'type' => 'int',
                'size' => 11,
                'primary' => true,
                'auto-inc' => true
            ],
            'email' => [
                'type' => 'varchar',
                'size' => 256,
                'is-unique' => true
            ],
            'username' => [
                'type' => 'varchar',
                'size' => 20,
                'is-unique' => true
            ],
            'password' => [
                'type' => 'varchar',
                'size' => 256
            ],
            'created-on' => [
                'type' => 'datetime2',
                'default' => 'now',
            ],
        ]);
        
        $table2 = new MSSQLTable('t');
        $table2->addColumns([
            'user-id-super' => [
                'type' => 'int',
                'size' => 11,
                'primary' => true,
            ],
            'email-x' => [
                'type' => 'varchar',
                'size' => 256,
                'is-unique' => true
            ],
        ]);
        $table2->addReference($table, [
            'user-id-super' => 'user-id','email-x' =>  'email'], 'fk_ok', 'cascade', 'cascade');
        $key = $table2->getForeignKey('fk_ok');
        $this->assertEquals(2, count($key->getSourceCols()));
        $this->assertEquals("if not exists (select * from sysobjects where name='t' and xtype='U')\n"
                . "create table [t] (\n"
                . "    [user_id_super] [int] not null,\n"
                . "    [email_x] [varchar](256) not null,\n"
                . "    constraint t_pk primary key clustered([user_id_super]) on [PRIMARY],\n"
                . "    constraint fk_ok foreign key ([user_id_super], [email_x]) references [users] ([user_id], [email]) on update cascade on delete cascade,\n"
                . "    constraint AK_t unique (email_x)\n"
                . ")\n"
                . "", $table2->toSQL());
    }
    /**
     * 
     * @test
     */
    public function testGetColByIndex() {
        $table = new MSSQLTable();
        $table->addColumns([
            'user-id' => [
                'datatype' => 'int',
                'size' => 11,
                'is-primary' => true
            ],
            'username' => [
                'size' => 20,
                'is-unique' => true
            ],
            'email' => [
                'size' => 150,
                'is-unique' => true
            ],
            'password' => [
                'size' => 64
            ]
        ]);
        $this->assertEquals(4, $table->getColsCount());
        $col00 = $table->getColByIndex(0);
        $this->assertEquals('[user_id]',$col00->getName());
        $this->assertEquals('int',$col00->getDatatype());
        $this->assertEquals(11,$col00->getSize());
        $this->assertTrue($col00->isPrimary());
        $this->assertEquals(1, $table->getPrimaryKeyColsCount());
        
        $col01 = $table->getColByIndex(2);
        $this->assertEquals('nvarchar',$col01->getDatatype());
        $this->assertEquals(150,$col01->getSize());
        $this->assertFalse($col01->isPrimary());
        $this->asserttrue($col01->isUnique());

        $col02 = $table->getColByIndex(6);
        $this->assertNull($col02);
    }
    /**
     * 
     * @param MySQLTable $table
     * @depends testAddColumn00
     */
    public function testHasCol00($table) {
        $this->assertTrue($table->hasColumnWithKey('new-col'));
        $this->assertTrue($table->hasColumnWithKey(' new-col '));
        $this->assertTrue($table->hasColumnWithKey('new-col-2'));
    }
    /**
     * 
     * @param MySQLTable $table
     * @depends testAddColumn00
     */
    public function testHasCol01($table) {
        $this->assertTrue($table->hasColumnWithKey('new-col'));
        $this->assertFalse($table->hasColumnWithKey('invalid key'));
    }
    /**
     * @test
     */
    public function testPrimaryKey00() {
        $table = new MSSQLTable('hello');
        $table->addColumns([
            'id-col'=>[
            'is-primary' => true,
            'size' => 3
            ]
        ]);
        $this->assertEquals(1, $table->getPrimaryKeyColsCount());
        return $table;
    }
    /**
     * @test
     * @param MySQLTable $table
     * @depends testPrimaryKey01
     */
    public function testPrimaryKey02($table) {
        $table->removeColByKey('id-col');
        $this->assertTrue($table->getColByKey('id-col-2')->isUnique());

        return $table;
    }
    /**
     * @test
     */
    public function testRemoveColFromRef00() {
        $table = new MSSQLTable('active_or_not');
        $table->addColumns([
            'user-id' => [
                'size' => 15
            ],
            'username' => [
                'size' => 50
            ],
            'is-active' => [
                'type' => 'boolean'
            ]
        ]);
        $table2 = new MSSQLTable('user_info');
        $table2->addColumns([
            'user-id' => [
                'size' => 15
            ],
            'first-name' => [
                'size' => '50'
            ],
            'last-name' => [
                'size' => '50'
            ]
        ]);
        $table2->addReference($table, [
            'user-id',
            'first-name' => 'username'
            ], 'hello_fk');
        $key = $table2->getForeignKey('hello_fk');
        $this->assertEquals(2, count($key->getOwnerCols()));
        $this->assertFalse($key->removeReference('username'));
        $this->assertTrue($key->removeReference('first-name'));
        $this->assertEquals(1, count($table2->getForeignKey('hello_fk')->getSourceCols()));
    }
    /**
     * @test
     */
    public function testRemoveRef00() {
        $table = new MSSQLTable();
        $table->addColumns([
            'user-id' => [
                'size' => 15
            ],
            'is-active' => [
                'type' => 'boolean'
            ]
        ]);
        $this->assertNull($table->removeReference('not-exist'));
    }
    /**
     * @test
     */
    public function testRemoveRef01() {
        $table = new MSSQLTable('active_or_not');
        $table->addColumns([
            'user-id' => [
                'size' => 15
            ],
            'is-active' => [
                'type' => 'boolean'
            ]
        ]);
        $table2 = new MSSQLTable('user_info');
        $table2->addColumns([
            'user-id' => [
                'size' => 15
            ],
            'first-name' => [
                'size' => '50'
            ],
            'last-name' => [
                'size' => '50'
            ]
        ]);
        $table->addReference($table2, ['user-id'], 'hello_fk');
        $this->assertEquals(1, $table->getForignKeysCount());
        $this->assertNull($table->removeReference('not-exist'));
        $obj = $table->removeReference('hello_fk');
        $this->assertEquals('hello_fk', $obj->getKeyName());
        $this->assertEquals(0, $table->getForignKeysCount());
    }
    /**
     * @test
     */
    public function testWithBoolCol00() {
        $table = new MSSQLTable();
        $table->addColumns([
            'user-id' => [
                'size' => 15
            ],
            'is-active' => [
                'type' => 'boolean'
            ]
        ]);
        $this->assertEquals('boolean',$table->getColByKey('is-active')->getDatatype());
    }
}
