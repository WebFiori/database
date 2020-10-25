<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace webfiori\database\tests;

use PHPUnit\Framework\TestCase;
use webfiori\database\Database;
use webfiori\database\mysql\MySQLTable;
use webfiori\database\mysql\MySQLColumn;
use webfiori\database\mysql\MySQLQuery;
use webfiori\database\AbstractQuery;
/**
 * Description of MySQLColumnTest
 *
 * @author Ibrahim
 */
class MySQLColumnTest extends TestCase {
    /**
     * @test
     */
    public function testConstructor16() {
        $col = new MySQLColumn('hello_col', 'varchar');
        $this->assertEquals('`hello_col`', $col->getName());
        $this->assertEquals('varchar', $col->getDatatype());
        $this->assertNull($col->getDefault());
        $this->assertFalse($col->isNull());
        $this->assertFalse($col->isPrimary());
        $this->assertFalse($col->isUnique());
        $this->assertEquals(1, $col->getSize());
        $this->assertEquals(0, $col->getScale());
    }
    /**
     * @test
     */
    public function testCustomCleaner00() {
        $col = new MySQLColumn('hello', 'varchar');
        $col->setCustomFilter(function($originalVal, $basicFilterResult){
            
        });
        $this->assertNull($col->cleanValue('Hello World'));
        $col->setCustomFilter(function($originalVal, $basicFilterResult){
            return $originalVal.'?';
        });
        $this->assertEquals("'Hello World.?'",$col->cleanValue('Hello World.'));
    }
    /**
     * @test
     */
    public function testCustomCleaner01() {
        $col = new MySQLColumn('hello', 'int');
        $col->setCustomFilter(function($originalVal, $basicFilterResult){
            return $basicFilterResult*10;
        });
        $this->assertEquals(0,$col->cleanValue('Hello World.'));
        $this->assertEquals(10,$col->cleanValue(1));
        $this->assertEquals(260,$col->cleanValue(26));
        $col->setCustomFilter(function($originalVal, $basicFilterResult){
            return $basicFilterResult*$originalVal;
        });
        $this->assertEquals(100,$col->cleanValue(10));
        $this->assertEquals(9,$col->cleanValue(3));
    }
    /**
     * @test
     */
    public function testCustomCleaner02() {
        $col = new MySQLColumn('hello', 'int');
        $col->setCustomFilter(function(){
            return 5;
        });
        $this->assertEquals(5,$col->cleanValue('Hello World.'));
    }
    /**
     * @test
     */
    public function setCommentTest00() {
        $col = new MySQLColumn('user_id ', 'varchar', 15);
        $col->setComment('A unique ID for the user.');
        $this->assertEquals('A unique ID for the user.',$col->getComment());
        $this->assertEquals('`user_id` varchar(15) not null collate utf8mb4_unicode_520_ci comment \'A unique ID for the user.\'',$col.'');

        return $col;
    }
    /**
     * @test
     * @depends setCommentTest00
     * @param MySQLColumn $col Description
     */
    public function setCommentTest01($col) {
        $col->setComment(null);
        $this->assertNull($col->getComment());
        $this->assertEquals('`user_id` varchar(15) not null collate utf8mb4_unicode_520_ci',$col.'');
    }
    /**
     * @test
     */
    public function testAutoUpdate00() {
        $col = new MySQLColumn();
        $this->assertFalse($col->isAutoUpdate());
        $col->setAutoUpdate(true);
        $this->assertFalse($col->isAutoUpdate());
        $col->setDatatype('datetime');
        $col->setAutoUpdate(true);
        $this->assertTrue($col->isAutoUpdate());
    }
    /**
     * @test
     */
    public function testAutoUpdate01() {
        $col = new MySQLColumn();
        $this->assertFalse($col->isAutoUpdate());
        $col->setAutoUpdate(true);
        $this->assertFalse($col->isAutoUpdate());
        $col->setDatatype('timestamp');
        $col->setAutoUpdate(true);
        $this->assertTrue($col->isAutoUpdate());
    }
    /**
     * @test
     */
    public function testBoolean00() {
        $col = new MySQLColumn('my_col', 'boolean');
        $this->assertEquals('boolean',$col->getDatatype());
        $this->assertEquals('`my_col` bit(1) not null',$col.'');
    }
    /**
     * @test
     */
    public function testBoolean01() {
        $col = new MySQLColumn('my_col', 'bool');
        $this->assertEquals('bool',$col->getDatatype());
        $col->setDefault(true);
        $this->assertEquals('`my_col` bit(1) not null default b\'1\'',$col.'');
        $col->setDefault(false);
        $this->assertEquals('`my_col` bit(1) not null default b\'0\'',$col.'');
        $col->setDefault(null);
        $this->assertEquals('`my_col` bit(1) not null',$col.'');
        $col->setDefault('Random Val');
        $this->assertEquals('`my_col` bit(1) not null default b\'0\'',$col.'');
    }
    /**
     * @test
     */
    public function testBoolean02() {
        $col = new MySQLColumn('my_col', 'bool');
        $this->assertEquals('bool',$col->getDatatype());
        $col->setIsNull(true);
        $this->assertEquals('`my_col` bit(1) not null',$col.'');
        $col->setIsAutoInc(true);
        $this->assertEquals('`my_col` bit(1) not null',$col.'');
        $col->setIsPrimary(true);
        $this->assertEquals('`my_col` bit(1) not null',$col.'');
        $col->setIsUnique(true);
        $this->assertEquals('`my_col` bit(1) not null',$col.'');
    }
    /**
     * @test
     */
    public function testCleanValue00() {
        $col = new MySQLColumn('col', 'varchar');
        $this->assertEquals('\'Hello World!\'',$col->cleanValue('Hello World!'));
        $this->assertEquals('\'I wouln\\\'t do That\'',$col->cleanValue('I wouln\'t do That'));
    }
    /**
     * @test
     */
    public function testCleanValue01() {
        $col = new MySQLColumn('col', 'text');
        $this->assertEquals('\'Hello World!\'',$col->cleanValue('Hello World!'));
        $this->assertEquals('\'I wouln\\\'t do That\'',$col->cleanValue('I wouln\'t do That'));
    }
    /**
     * @test
     */
    public function testCleanValue02() {
        $col = new MySQLColumn('col', 'mediumtext');
        $this->assertEquals('\'Hello World!\'',$col->cleanValue('Hello World!'));
        $this->assertEquals('\'I wouln\\\'t do That\'',$col->cleanValue('I wouln\'t do That'));
    }
    /**
     * @test
     */
    public function testCleanValue03() {
        $col = new MySQLColumn('col', 'int');
        $this->assertEquals(0,$col->cleanValue('Hello World!'));
        $this->assertEquals(0,$col->cleanValue('I wouln\';select * from x'));
        $this->assertEquals(43,$col->cleanValue('43'));
        $this->assertEquals(-99,$col->cleanValue('-99.65'));
        $this->assertEquals(0,$col->cleanValue('hello-99.65'));
        $this->assertEquals(5,$col->cleanValue(5));
    }
    /**
     * @test
     */
    public function testCleanValue04() {
        $col = new MySQLColumn('col', 'decimal');
        $this->assertEquals('\'0\'',$col->cleanValue('Hello World!'));
        $this->assertEquals('\'0\'',$col->cleanValue('I wouln\';select * from x'));
        $this->assertEquals('\'43\'',$col->cleanValue('43'));
        $this->assertEquals('\'-99.65\'',$col->cleanValue('-99.65'));
        $this->assertEquals('\'0\'',$col->cleanValue('hello-99.65'));
        $this->assertEquals('\'5\'',$col->cleanValue(5));
        $this->assertEquals('\'6532.887\'',$col->cleanValue(6532.887));
    }
    /**
     * @test
     */
    public function testCleanValue06() {
        $col = new MySQLColumn('col', 'decimal');
        $this->assertEquals('\'0\'',$col->cleanValue('Hello World!'));
        $this->assertEquals('\'0\'',$col->cleanValue('I wouln\';select * from x'));
        $this->assertEquals('\'43\'',$col->cleanValue('43'));
        $this->assertEquals('\'-99.65\'',$col->cleanValue('-99.65'));
        $this->assertEquals('\'0\'',$col->cleanValue('hello-99.65'));
        $this->assertEquals('\'5\'',$col->cleanValue(5));
        $this->assertEquals('\'6532.887\'',$col->cleanValue(6532.887));
    }
    /**
     * @test
     */
    public function testCleanValue07() {
        $col = new MySQLColumn('col', 'timestamp');
        $this->assertEquals('',$col->cleanValue('Hello World!'));
        $this->assertEquals('',$col->cleanValue('I wouln\';select * from x'));
        $this->assertEquals('',$col->cleanValue(5));
        $this->assertEquals('\'2019-11-01 00:00:00\'',$col->cleanValue('2019-11-01'));
        $this->assertEquals('\'2019-11-01 23:09:44\'',$col->cleanValue('2019-11-01 23:09:44'));
    }
    /**
     * @test
     */
    public function testCleanValue08() {
        $col = new MySQLColumn('col');
        $cleanedArr = $col->cleanValue([1, false, null, 'string', '1.8', 7.9]);
        $this->assertEquals([
            "'1'",
            "''",
            null,
            "'string'",
            "'1.8'",
            "'7.9'"
        ],$cleanedArr);
        
    }
    /**
     * @test
     */
    public function testCleanValue09() {
        $col = new MySQLColumn('col', 'bool');
        $cleanedArr = $col->cleanValue([1, false, null, 'string', '1.8', 7.9, true]);
        $this->assertEquals([
            "b'0'",
            "b'0'",
            null,
            "b'0'",
            "b'0'",
            "b'0'",
            "b'1'"
        ],$cleanedArr);
        
    }
    /**
     * @test
     */
    public function testCreateCol00() {
        $colObj = MySQLColumn::createColObj([]);
        $this->assertNull($colObj);
    }
    /**
     * @test
     */
    public function testCreateCol01() {
        $colObj = MySQLColumn::createColObj([
            'name' => 'my_col',
            'validator' => function ($orgVal, $cleaned) {
                return 'Hello '.$cleaned;
            }
        ]);
        $this->assertNotNull($colObj);
        $this->assertEquals('`my_col`', $colObj->getName());
        $this->assertEquals('varchar', $colObj->getDatatype());
        $this->assertEquals(1, $colObj->getSize());
        $this->assertEquals("'Hello Ibrahim'", $colObj->cleanValue('Ibrahim'));
    }
    /**
     * @test
     */
    public function testConstructor00() {
        $col = new MySQLColumn();
        $this->assertEquals('varchar',$col->getDatatype());
        $this->assertEquals(1,$col->getSize());
        $this->assertEquals('`col`',$col->getName());
    }
    /**
     * @test
     */
    public function testConstructor01() {
        $col = new MySQLColumn('user_id ', 'varchar', 15);
        $this->assertEquals('varchar',$col->getDatatype());
        $this->assertEquals(15,$col->getSize());
        $this->assertEquals('`user_id`',$col->getName());
    }
    /**
     * @test
     */
    public function testConstructor04() {
        $this->expectException('webfiori\database\DatabaseException');
        $col = new MySQLColumn('valid_name', 'invalid type', 15);
        $this->assertEquals('varchar',$col->getDatatype());
        $this->assertEquals(15,$col->getSize());
        $this->assertEquals('valid_name',$col->getName());
    }
    /**
     * @test
     */
    public function testConstructor05() {
        $col = new MySQLColumn('valid_name', 'InT', 15);
        $this->assertEquals('int',$col->getDatatype());
        $this->assertEquals(11,$col->getSize());
        $this->assertEquals('`valid_name`',$col->getName());
    }
    /**
     * @test
     */
    public function testConstructor06() {
        $col = new MySQLColumn('valid_name', 'Varchar ', 15);
        $this->assertEquals('varchar',$col->getDatatype());
        $this->assertEquals(15,$col->getSize());
        $this->assertEquals('`valid_name`',$col->getName());
    }
    /**
     * @test
     */
    public function testConstructor07() {
        $col = new MySQLColumn('valid_name', 'Varchar ', 21846);
        $this->assertEquals('mediumtext',$col->getDatatype());
        $this->assertEquals(21846,$col->getSize());
        $this->assertEquals('`valid_name`',$col->getName());
    }
    /**
     * @test
     */
    public function testConstructor08() {
        $col = new MySQLColumn('valid_name', 'Varchar ', 0);
        $this->assertEquals('varchar',$col->getDatatype());
        $this->assertEquals(1,$col->getSize());
        $this->assertEquals('`valid_name`',$col->getName());
    }
    /**
     * @test
     */
    public function testConstructor09() {
        $col = new MySQLColumn('amount', 'decimal ');
        $this->assertEquals('decimal',$col->getDatatype());
        $this->assertEquals(1,$col->getSize());

        return $col;
    }
    /**
     * @test
     */
    public function testConstructor10() {
        $col = new MySQLColumn('amount', 'decimal ',0);
        $this->assertEquals('decimal',$col->getDatatype());
        $this->assertEquals(1,$col->getSize());
        $this->assertEquals(0,$col->getScale());

        return $col;
    }
    /**
     * @test
     */
    public function testConstructor11() {
        $col = new MySQLColumn('amount', 'decimal ',1);
        $this->assertEquals('decimal',$col->getDatatype());
        $this->assertEquals(1,$col->getSize());
        $this->assertEquals(0,$col->getScale());

        return $col;
    }
    /**
     * @test
     */
    public function testConstructor12() {
        $col = new MySQLColumn('amount', 'decimal ',2);
        $this->assertEquals('decimal',$col->getDatatype());
        $this->assertEquals(2,$col->getSize());
        $this->assertEquals(0,$col->getScale());

        return $col;
    }
    /**
     * @test
     */
    public function testConstructor13() {
        $col = new MySQLColumn('amount', 'decimal ',3);
        $this->assertEquals('decimal',$col->getDatatype());
        $this->assertEquals(3,$col->getSize());
        $this->assertEquals(0,$col->getScale());

        return $col;
    }
    /**
     * @test
     */
    public function testConstructor14() {
        $col = new MySQLColumn('amount', 'decimal ',4);
        $this->assertEquals('decimal',$col->getDatatype());
        $this->assertEquals(4,$col->getSize());
        $this->assertEquals(0,$col->getScale());

        return $col;
    }
    /**
     * @test
     */
    public function testConstructor15() {
        $col = new MySQLColumn('amount', 'decimal ',-9);
        $this->assertEquals('decimal',$col->getDatatype());
        $this->assertEquals(1,$col->getSize());
        $this->assertEquals(0,$col->getScale());

        return $col;
    }
    /**
     * @test
     */
    public function testSetDefault00() {
        $col = new MySQLColumn('date', 'timestamp');
        $col->setDefault('2019-11-09');
        $this->assertEquals('2019-11-09 00:00:00',$col->getDefault());
        $this->assertEquals('`date` timestamp not null default \'2019-11-09 00:00:00\'',$col.'');
    }
    /**
     * @test
     */
    public function testSetDefault01() {
        $col = new MySQLColumn('date', 'timestamp');
        $col->setDefault('2019-07-07 09:09:09');
        $this->assertEquals('2019-07-07 09:09:09',$col->getDefault());
        $this->assertEquals('`date` timestamp not null default \'2019-07-07 09:09:09\'',$col.'');
    }
    /**
     * @test
     */
    public function testSetDefault02() {
        $col = new MySQLColumn('date', 'datetime');
        $col->setDefault('');
        $this->assertNull($col->getDefault());
        $this->assertEquals('`date` datetime not null',$col.'');
    }
    /**
     * @test
     */
    public function testSetDefault03() {
        $col = new MySQLColumn('date', 'datetime');
        $col->setDefault('2019-07-07 09:09:09');
        $this->assertEquals('2019-07-07 09:09:09',$col->getDefault());
        $this->assertEquals('`date` datetime not null default \'2019-07-07 09:09:09\'',$col.'');
    }
    /**
     * @test
     */
    public function testSetDefault04() {
        $col = new MySQLColumn('date', 'datetime');
        $col->setDefault('2019-15-07 09:09:09');
        $this->assertNull($col->getDefault());
        $this->assertEquals('`date` datetime not null',$col.'');
        $col->setDefault('2019-12-33 09:09:09');
        $this->assertNull($col->getDefault());
        $this->assertEquals('`date` datetime not null',$col.'');
        $col->setDefault('2019-12-31 24:09:09');
        $this->assertNull($col->getDefault());
        $col->setDefault('2019-12-31 23:60:09');
        $this->assertNull($col->getDefault());
        $col->setDefault('2019-12-31 23:59:60');
        $this->assertNull($col->getDefault());
        $col->setDefault('2019-12-31 23:59:59');
        $this->assertEquals('2019-12-31 23:59:59',$col->getDefault());
    }
    /**
     * @test
     */
    public function testSetDefault05() {
        $col = new MySQLColumn('id', 'int');
        $this->assertEquals('`id` int not null',$col.'');
        $col->setMySQLVersion('5.0.0');
        $col->setDefault(-122);
        $this->assertEquals(-122,$col->getDefault());
        $this->assertEquals('`id` int(1) not null default -122',$col.'');
        $col->setDefault(null);
        $this->assertNull($col->getDefault());
        $col->setDefault('a string');
        $this->assertEquals(0,$col->getDefault());
        $col->setDefault(1.8);
        $this->assertEquals(1,$col->getDefault());
    }
    /**
     * @test
     */
    public function testSetDefault06() {
        $col = new MySQLColumn('id', 'varchar');
        $this->assertEquals('`id` varchar(1) not null collate utf8mb4_unicode_520_ci',$col.'');
        $col->setDefault('A random string.');
        $this->assertEquals('A random string.',$col->getDefault());
        $col->setMySQLVersion('5.0.0');
        $this->assertEquals('`id` varchar(1) not null default \'A random string.\' collate utf8mb4_unicode_ci',$col.'');
        $col->setDefault(null);
        $this->assertNull($col->getDefault());
        $col->setDefault(33);
        $this->assertEquals(33,$col->getDefault());
        $col->setDefault(1.8);
        $this->assertEquals(1.8,$col->getDefault());
    }
    /**
     * @test
     */
    public function testSetDefault07() {
        $col = new MySQLColumn('id', 'decimal');
        $this->assertEquals('`id` decimal(1,0) not null',$col.'');
        $col->setDefault(1);
        $this->assertEquals(1,$col->getDefault());
        $this->assertEquals('`id` decimal(1,0) not null default \'1\'',$col.'');
        $col->setDefault(1.66);
        $this->assertEquals(1.66,$col->getDefault());
        $this->assertEquals('`id` decimal(1,0) not null default \'1.66\'',$col.'');
        $col->setDefault(null);
        $this->assertNull($col->getDefault());
        $col->setDefault('33');
        $this->assertEquals(33,$col->getDefault());
        $col->setDefault('');
        $this->assertEquals(0,$col->getDefault());
    }
    /**
     * @test
     */
    public function testSetDefault08() {
        $col = new MySQLColumn('date', 'datetime');
        $col->setDefault('now()');
        $this->assertEquals('now()',$col->getDefault());
        $this->assertEquals('`date` datetime not null default now()',$col.'');
    }
    /**
     * @test
     */
    public function testSetMySQLVersion00() {
        $col = new MySQLColumn();
        $col->setMySQLVersion('5.4');
        $this->assertEquals('5.4',$col->getMySQLVersion());
        $this->assertEquals('utf8mb4_unicode_ci',$col->getCollation());
    }
    /**
     * @test
     */
    public function testSetMySQLVersion01() {
        $col = new MySQLColumn();
        $col->setMySQLVersion('8.0');
        $this->assertEquals('8.0',$col->getMySQLVersion());
        $this->assertEquals('utf8mb4_unicode_520_ci',$col->getCollation());
    }
    /**
     * @test
     */
    public function testSetMySQLVersion02() {
        $column = new MySQLColumn();
        $column->setMySQLVersion('5');
        $this->assertEquals('8.0',$column->getMySQLVersion());
        $this->assertEquals('utf8mb4_unicode_520_ci',$column->getCollation());
    }
    /**
     * @test
     */
    public function testSetMySQLVersion03() {
        $col = new MySQLColumn();
        $col->setMySQLVersion('8.0.77');
        $this->assertEquals('8.0.77',$col->getMySQLVersion());
        $this->assertEquals('utf8mb4_unicode_520_ci',$col->getCollation());
    }
    /**
     * 
     * @param MySQLColumn $col
     * @depends testConstructor09
     */
    public function testSetScale00($col) {
        $col->setSize(10);
        $this->assertTrue($col->setScale(3));
        $this->assertEquals(3,$col->getScale());
        $this->assertTrue($col->setScale(0));
        $this->assertEquals(0,$col->getScale());
        $this->assertTrue($col->setScale(9));
        $this->assertEquals(9,$col->getScale());
        $this->assertFalse($col->setScale(10));
        $this->assertEquals(9,$col->getScale());
    }
    /**
     * @test
     */
    public function testSetType00() {
        $col = new MySQLColumn();
        $col->setDatatype('int');
        $this->assertEquals('int',$col->getDatatype());
        $this->assertEquals(1,$col->getSize());
        $this->assertNull($col->getDefault());
        $col->setSize(11);
        $col->setDefault(6000);
        $this->assertEquals(11,$col->getSize());
        $this->assertEquals(6000,$col->getDefault());
        $col->setDatatype('  int ');
        $col->setSize(12);
        $col->setDefault(698);
        $this->assertEquals('int',$col->getDatatype());
        $this->assertEquals(11,$col->getSize());
    }
    /**
     * @test
     */
    public function testSetType01() {
        $col = new MySQLColumn();
        $col->setDatatype('varchar');
        $col->setSize(0);
        $this->assertEquals('varchar',$col->getDatatype());
        $this->assertEquals(1,$col->getSize());
        $this->assertNull($col->getDefault());
        $col->setDatatype('  varchar');
        $col->setSize(5000);
        $col->setDefault(6000);
        $this->assertEquals(5000,$col->getSize());
        $this->assertSame('6000',$col->getDefault());
        $col->setSize(500000);
        $col->setDefault('Hello World');
        $this->assertEquals('mediumtext',$col->getDatatype());
        $this->assertEquals(500000,$col->getSize());
        $this->assertSame('Hello World',$col->getDefault());
    }
    /**
     * @test
     */
    public function testSetType02() {
        $col = new MySQLColumn();
        $col->setDatatype('varchar');
        $this->assertEquals('varchar',$col->getDatatype());
        $col->setSize(5000);
        $this->assertEquals(5000,$col->getSize());
        $col->setDefault('Hello');
        $this->assertSame('Hello',$col->getDefault());
        $col->setDatatype('int');
        $this->assertEquals('int',$col->getDatatype());
        $col->setSize(1);
        $this->assertEquals(1,$col->getSize());
        $this->assertNull($col->getDefault());
    }
    /**
     * @test
     */
    public function testSetType03() {
        $col = new MySQLColumn();
        $col->setDatatype('datetime');
        $this->assertEquals('datetime',$col->getDatatype());
        $this->assertEquals(1,$col->getSize());
        $col->setDefault('2019-01-11');
        $this->assertSame('2019-01-11 00:00:00',$col->getDefault());
    }
    /**
     * @test
     */
    public function testSetType04() {
        $col = new MySQLColumn();
        $col->setDatatype('datetime');
        $col->setDefault('2019-01-11 28:00:00');
        $this->assertEquals('datetime',$col->getDatatype());
        $this->assertEquals(1,$col->getSize());
        $this->assertNull($col->getDefault());
        $col->setDefault('2019-13-11 00:00:00');
        $this->assertNull($col->getDefault());
        $col->setDefault('2019-04-44 00:00:00');
        $this->assertNull($col->getDefault());
        
        $col->setDatatype('timestamp');
        $col->setDefault('2019-12-11 00:60:00');
        $this->assertNull($col->getDefault());
        $col->setDefault('2019-12-11 00:00:60');
        $this->assertNull($col->getDefault());
        $col->setDefault('2019-12-30 23:59:59');
        $this->assertEquals('2019-12-30 23:59:59',$col->getDefault());
    }
    /**
     * @test
     */
    public function testSetType05() {
        $col = new MySQLColumn();
        $col->setDatatype('datetime');
        $col->setDefault('now()');
        $this->assertEquals('now()', $col->getDefault());
        $col->setDefault('current_timestamp');
        $this->assertEquals('current_timestamp', $col->getDefault());
    }
    /**
     * @test
     */
    public function testSetType06() {
        $col = new MySQLColumn();
        $col->setDatatype('timestamp');
        $col->setDefault('now()');
        $this->assertEquals('now()', $col->getDefault());
        $col->setDefault('current_timestamp');
        $this->assertEquals('current_timestamp', $col->getDefault());
    }
    /**
     * @test
     */
    public function testGetPHPType00() {
        $colObj = new MySQLColumn();
        $this->assertEquals('string', $colObj->getPHPType());
        $colObj->setIsNull(true);
        $this->assertEquals('string|null', $colObj->getPHPType());
    }
    /**
     * @test
     */
    public function testGetPHPType01() {
        $colObj = new MySQLColumn('col', 'bool');
        $this->assertEquals('boolean', $colObj->getPHPType());
        $colObj->setIsNull(true);
        $this->assertEquals('boolean', $colObj->getPHPType());
    }
    /**
     * @test
     */
    public function testGetPHPType02() {
        $colObj = new MySQLColumn('col', 'boolean');
        $this->assertEquals('boolean', $colObj->getPHPType());
        $colObj->setIsNull(true);
        $this->assertEquals('boolean', $colObj->getPHPType());
    }
    /**
     * @test
     */
    public function testGetPHPType03() {
        $colObj = new MySQLColumn('col', 'decimal');
        $this->assertEquals('double', $colObj->getPHPType());
        $colObj->setIsNull(true);
        $this->assertEquals('double|null', $colObj->getPHPType());
    }
    /**
     * @test
     */
    public function testGetPHPType04() {
        $colObj = new MySQLColumn('col', 'blob');
        $this->assertEquals('string', $colObj->getPHPType());
        $colObj->setIsNull(true);
        $this->assertEquals('string|null', $colObj->getPHPType());
    }
    /**
     * @test
     */
    public function testGetPHPType05() {
        $colObj = new MySQLColumn('col', 'datetime');
        $this->assertEquals('string', $colObj->getPHPType());
        $colObj->setIsNull(true);
        $this->assertEquals('string|null', $colObj->getPHPType());
    }
}
