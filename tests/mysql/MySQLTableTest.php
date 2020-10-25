<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace webfiori\database\tests;
use PHPUnit\Framework\TestCase;
use webfiori\database\mysql\MySQLTable;
use webfiori\database\mysql\MySQLColumn;
/**
 * Description of MySQLTableTest
 *
 * @author Ibrahim
 */
class MySQLTableTest extends TestCase {
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
        $this->assertFalse($table->addColumn('invalid key', new MySQLColumn('col_2')));
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
        $this->assertEquals('`another_Valid_Name`',$table->getName());
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
        $this->assertEquals('varchar',$col01->getDatatype());
        $this->assertEquals(150,$col01->getSize());
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
}
