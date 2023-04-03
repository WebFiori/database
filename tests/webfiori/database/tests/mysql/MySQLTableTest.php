<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace webfiori\database\tests\mysql;

use PHPUnit\Framework\TestCase;
use webfiori\database\mssql\MSSQLTable;
use webfiori\database\mysql\MySQLColumn;
use webfiori\database\mysql\MySQLTable;
use webfiori\database\Table;
/**
 * Description of MySQLTableTest
 *
 * @author Ibrahim
 */
class MySQLTableTest extends TestCase {
    /**
     * @test
     */
    public function testFK1() {
        $table = new MySQLTable('users');
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
                'type' => 'timestamp',
                'default' => 'now()',
            ],
        ]);
        
        $table2 = new MySQLTable('t');
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
        $this->assertEquals("create table if not exists `t` (\n"
                . "    `user_id` int not null unique auto_increment,\n"
                . "    `email` varchar(256) not null unique collate utf8mb4_unicode_520_ci,\n"
                . "    constraint `t_pk` primary key (`user_id`),\n"
                . "    constraint `fk_ok` foreign key (`user_id`, `email`) references `users` (`user_id`, `email`) on update cascade on delete cascade\n"
                . ")\n"
                . "engine = InnoDB\n"
                . "default charset = utf8mb4\n"
                . "collate = utf8mb4_unicode_520_ci;", $table2->toSQL());
        $this->assertEquals(3, $table->getUniqueColsCount());
    }
    /**
     * @test
     */
    public function testFK2() {
        $table = new MySQLTable('users');
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
                'type' => 'timestamp',
                'default' => 'now()',
            ],
        ]);
        
        $table2 = new MySQLTable('t');
        $table2->addColumns([
            'user-id-super' => [
                'type' => 'int',
                'size' => 11,
                'primary' => true,
                'auto-inc' => true
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
        $this->assertEquals("create table if not exists `t` (\n"
                . "    `user_id_super` int not null unique auto_increment,\n"
                . "    `email_x` varchar(256) not null unique collate utf8mb4_unicode_520_ci,\n"
                . "    constraint `t_pk` primary key (`user_id_super`),\n"
                . "    constraint `fk_ok` foreign key (`user_id_super`, `email_x`) references `users` (`user_id`, `email`) on update cascade on delete cascade\n"
                . ")\n"
                . "engine = InnoDB\n"
                . "default charset = utf8mb4\n"
                . "collate = utf8mb4_unicode_520_ci;", $table2->toSQL());
    }
    /**
     * @test
     */
    public function testMap00() {
        $table = new MySQLTable('users');
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
                'type' => 'timestamp',
                'default' => 'now()',
            ],
        ]);
        
        $table2 = new MySQLTable('t');
        $table2->addColumns([
            'user-id-super' => [
                'type' => 'int',
                'size' => 11,
                'primary' => true,
                'auto-inc' => true
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
        $this->assertEquals("create table if not exists `t` (\n"
                . "    `user_id_super` int not null unique auto_increment,\n"
                . "    `email_x` varchar(256) not null unique collate utf8mb4_unicode_520_ci,\n"
                . "    constraint `t_pk` primary key (`user_id_super`),\n"
                . "    constraint `fk_ok` foreign key (`user_id_super`, `email_x`) references `users` (`user_id`, `email`) on update cascade on delete cascade\n"
                . ")\n"
                . "engine = InnoDB\n"
                . "default charset = utf8mb4\n"
                . "collate = utf8mb4_unicode_520_ci;", $table2->toSQL());
        
        $mappedInstance = Table::map('mssql', $table2);
        $this->assertTrue($mappedInstance instanceof MSSQLTable);
        $this->assertEquals('[t]', $mappedInstance->getName());
        $this->assertEquals("if not exists (select * from sysobjects where name='t' and xtype='U')\n"
                . "create table [t] (\n"
                . "    [user_id_super] [int] not null,\n"
                . "    [email_x] [varchar](256) not null,\n"
                . "    constraint t_pk primary key clustered([user_id_super]) on [PRIMARY],\n"
                . "    constraint fk_ok foreign key ([user_id_super], [email_x]) references [users] ([user_id], [email]) on update cascade on delete cascade,\n"
                . "    constraint AK_t unique (user_id_super, email_x)\n"
                . ")\n", $mappedInstance->toSQL());
    }
    /**
     * @test
     */
    public function testAddColumn00() {
        $table = new MySQLTable();
        $this->assertTrue($table->addColumn('new-col', new MySQLColumn()));
        $this->assertFalse($table->addColumn('new-col-2', new MySQLColumn()));
        $this->assertTrue($table->addColumn('new-col-2', new MySQLColumn('col_2', 'varchar')));
        $this->assertFalse($table->addColumn('new-col-2', new MySQLColumn('col_3', 'varchar')));

        return $table;
    }
    /**
     * @test
     */
    public function testAddColumn01() {
        $table = new MySQLTable();
        $this->assertTrue($table->addColumn(' new-col ', new MySQLColumn()));
        $this->assertTrue($table->addColumn('valid key', new MySQLColumn('col_')));
        $this->assertFalse($table->addColumn('-', new MySQLColumn('col_2')));
        $this->assertFalse($table->addColumn('--', new MySQLColumn('col_2')));

        return $table;
    }
    /**
     * @test
     */
    public function testAddColumn02() {
        $table = new MySQLTable();
        $table->addColumns([
            'id' => new MySQLColumn('col-01'),
            'name' => new MySQLColumn('col-02', 'int')
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
    public function testConstructor00() {
        $table = new MySQLTable();
        $this->assertNull($table->getOldName());
        $this->assertEquals('`new_table`',$table->getName());
    }
    /**
     * @test
     */
    public function testConstructor01() {
        $table = new MySQLTable('valid_name');
        $this->assertEquals('`valid_name`',$table->getName());
    }
    /**
     * @test
     */
    public function testConstructor02() {
        $table = new MySQLTable('    another_Valid_Name    ');
        $this->assertNull($table->getOldName());
        $this->assertEquals('`another_Valid_Name`',$table->getName());
        $table->setName('new-name');
        $this->assertEquals('`another_Valid_Name`', $table->getOldName());
    }
    /**
     * 
     * @test
     */
    public function testGetColByIndex() {
        $table = new MySQLTable();
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
        $this->assertEquals('`user_id`',$col00->getName());
        $this->assertEquals('int',$col00->getDatatype());
        $this->assertEquals(11,$col00->getSize());
        $this->assertTrue($col00->isPrimary());
        $this->assertEquals(1, $table->getPrimaryKeyColsCount());
        
        $col01 = $table->getColByIndex(2);
        $this->assertEquals('mixed',$col01->getDatatype());
        $this->assertEquals(1,$col01->getSize());
        $this->assertFalse($col01->isPrimary());
        $this->asserttrue($col01->isUnique());

        $col02 = $table->getColByIndex(6);
        $this->assertNull($col02);
    }
    /**
     * @test
     */
    public function testGetCreatePrimaryKeyStatement00() {
        $table = new MySQLTable();
        $this->assertTrue(true);
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
        $table = new MySQLTable('hello');
        $table->addColumns([
            'id-col'=>[
            'is-primary' => true,
            'size' => 3
            ]
        ]);
        $this->assertTrue($table->getColByKey('id-col')->isUnique());
        $this->assertEquals(1, $table->getPrimaryKeyColsCount());
        $this->assertEquals(1, $table->getUniqueColsCount());
        $this->assertEquals([
            'id-col'
        ], $table->getUniqueColsKeys());
        return $table;
    }
    /**
     * @test
     * @param MySQLTable $table
     * @depends testPrimaryKey00
     */
    public function testPrimaryKey01($table) {
        $table->addColumns([
            'id-col-2'=>[
                'is-primary' => true
            ]
        ]);
        $this->assertFalse($table->getColByKey('id-col')->isUnique());
        $this->assertFalse($table->getColByKey('id-col-2')->isUnique());
        $this->assertEquals(2, $table->getPrimaryKeyColsCount());
        $this->assertEquals(0, $table->getUniqueColsCount());
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
        $this->assertEquals(1, $table->getUniqueColsCount());
        return $table;
    }
    /**
     * @test
     */
    public function testSetMySQLVersion00() {
        $table = new MySQLTable();
        $table->setMySQLVersion('5.4');
        $this->assertEquals('5.4',$table->getMySQLVersion());
        $this->assertEquals('utf8mb4_unicode_ci',$table->getCollation());
    }
    /**
     * @test
     */
    public function testSetMySQLVersion01() {
        $table = new MySQLTable();
        $table->setMySQLVersion('8.0');
        $this->assertEquals('8.0',$table->getMySQLVersion());
        $this->assertEquals('utf8mb4_unicode_520_ci',$table->getCollation());
    }
    /**
     * @test
     */
    public function testSetMySQLVersion02() {
        $table = new MySQLTable();
        $table->setMySQLVersion('8');
        $this->assertEquals('8.0',$table->getMySQLVersion());
        $this->assertEquals('utf8mb4_unicode_520_ci',$table->getCollation());
        $table->setMySQLVersion('5.0.0');
        $this->assertEquals('utf8mb4_unicode_ci', $table->getCollation());
    }
    /**
     * @test
     */
    public function testSetMySQLVersion03() {
        $table = new MySQLTable();
        $table->setMySQLVersion('8.0.77');
        $this->assertEquals('8.0.77',$table->getMySQLVersion());
        $this->assertEquals('utf8mb4_unicode_520_ci',$table->getCollation());
    }
    /**
     * @test
     */
    public function testWithBoolCol00() {
        $table = new MySQLTable();
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
    /**
     * @test
     */
    public function testWithBoolCol01() {
        $table = new MySQLTable();
        $table->addColumns([
            'user-id' => [
                'size' => 15
            ],
            'is-active' => [
                'type' => 'bool'
            ]
        ]);
        $this->assertEquals('bool',$table->getColByKey('is-active')->getDatatype());
    }
    /**
     * @test
     */
    public function testRemoveRef00() {
        $table = new MySQLTable();
        $table->addColumns([
            'user-id' => [
                'size' => 15,
                'name' => 'cool'
            ],
            'is-active' => [
                'type' => 'bool'
            ]
        ]);
        $this->assertNull($table->getColByName('is-active'));
        $this->assertNotNull($table->getColByName('is_active'));
        $this->assertNull($table->getColByName('user_id'));
        $this->assertNotNull($table->getColByName('cool'));
        $this->assertNull($table->removeReference('not-exist'));
        $this->assertEquals([
            'user-id' => 'mixed',
            'is-active' => 'bool'
        ], $table->getColsDataTypes());
    }
    /**
     * @test
     */
    public function testRemoveRef01() {
        $table = new MySQLTable('active_or_not');
        $table->addColumns([
            'user-id' => [
                'size' => 15
            ],
            'is-active' => [
                'type' => 'bool'
            ]
        ]);
        $table2 = new MySQLTable('user_info');
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
        $this->assertEquals(1, $table->getForeignKeysCount());
        $this->assertNull($table->removeReference('not-exist'));
        $obj = $table->removeReference('hello_fk');
        $this->assertEquals('hello_fk', $obj->getKeyName());
        $this->assertEquals(0, $table->getForeignKeysCount());
    }
    public function testRemoveColFromRef00() {
        $table = new MySQLTable('active_or_not');
        $table->addColumns([
            'user-id' => [
                'size' => 15
            ],
            'username' => [
                'size' => 50
            ],
            'is-active' => [
                'type' => 'bool'
            ]
        ]);
        $table2 = new MySQLTable('user_info');
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
}
