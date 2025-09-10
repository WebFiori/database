<?php
namespace WebFiori\Tests\Database\MsSql;

use PHPUnit\Framework\TestCase;
use WebFiori\Database\ColumnFactory;
use WebFiori\Database\MsSql\MSSQLColumn;
use WebFiori\Database\MySql\MySQLColumn;
/**
 * Description of MSSQLColumnTest
 *
 * @author Ibrahim
 */
class MSSQLColumnTest extends TestCase {
    /**
     * @test
     */
    public function testConstructor00() {
        $col = new MSSQLColumn();
        $this->assertEquals('nvarchar',$col->getDatatype());
        $this->assertEquals(1,$col->getSize());
        $this->assertEquals('[col]',$col->getName());
    }
    /**
     * @test
     */
    public function testConstructor01() {
        $col = new MSSQLColumn('user_id ', 'varchar', 15);
        $this->assertEquals('varchar',$col->getDatatype());
        $this->assertEquals(15,$col->getSize());
        $this->assertEquals('[user_id]',$col->getName());
    }
    /**
     * @test
     */
    public function testConstructor02() {
        $this->expectException('WebFiori\Database\DatabaseException');
        $col = new MSSQLColumn('valid_name', 'invalid type', 15);
        $this->assertEquals('varchar',$col->getDatatype());
        $this->assertEquals(15,$col->getSize());
        $this->assertEquals('valid_name',$col->getName());
    }
    /**
     * @test
     */
    public function testConstructor03() {
        $col = new MSSQLColumn('valid_name', 'InT', 15);
        $this->assertEquals('int',$col->getDatatype());
        $this->assertEquals(15,$col->getSize());
        $this->assertEquals('[valid_name]',$col->getName());
    }
    /**
     * @test
     */
    public function testConstructor04() {
        $col = new MSSQLColumn('valid_name', 'Varchar ', 15);
        $this->assertEquals('varchar',$col->getDatatype());
        $this->assertEquals(15,$col->getSize());
        $this->assertEquals('[valid_name]',$col->getName());
    }
    /**
     * @test
     */
    public function testConstructor06() {
        $col = new MSSQLColumn('valid_name', 'Varchar ', 0);
        $this->assertEquals('varchar',$col->getDatatype());
        $this->assertEquals(1,$col->getSize());
        $this->assertEquals('[valid_name]',$col->getName());
    }
    /**
     * @test
     */
    public function testConstructor07() {
        $col = new MSSQLColumn('amount', 'decimal ');
        $this->assertEquals('decimal',$col->getDatatype());
        $this->assertEquals(1,$col->getSize());

        return $col;
    }
    /**
     * @test
     */
    public function testConstructor08() {
        $col = new MSSQLColumn('amount', 'decimal ',0);
        $this->assertEquals('decimal',$col->getDatatype());
        $this->assertEquals(1,$col->getSize());
        $this->assertEquals(0,$col->getScale());

        return $col;
    }
    /**
     * @test
     */
    public function testConstructor09() {
        $col = new MSSQLColumn('amount', 'decimal ',1);
        $this->assertEquals('decimal',$col->getDatatype());
        $this->assertEquals(1,$col->getSize());
        $this->assertEquals(0,$col->getScale());

        return $col;
    }
    /**
     * @test
     */
    public function testConstructor10() {
        $col = new MSSQLColumn('amount', 'decimal ',2);
        $this->assertEquals('decimal',$col->getDatatype());
        $this->assertEquals(2,$col->getSize());
        $this->assertEquals(0,$col->getScale());

        return $col;
    }
    /**
     * @test
     */
    public function testConstructor11() {
        $col = new MSSQLColumn('amount', 'decimal ',3);
        $this->assertEquals('decimal',$col->getDatatype());
        $this->assertEquals(3,$col->getSize());
        $this->assertEquals(0,$col->getScale());

        return $col;
    }
    /**
     * @test
     */
    public function testConstructor12() {
        $col = new MSSQLColumn('amount', 'decimal ',4);
        $this->assertEquals('decimal',$col->getDatatype());
        $this->assertEquals(4,$col->getSize());
        $this->assertEquals(0,$col->getScale());

        return $col;
    }
    /**
     * @test
     */
    public function testConstructor13() {
        $col = new MSSQLColumn('amount', 'decimal ',-9);
        $this->assertEquals('decimal',$col->getDatatype());
        $this->assertEquals(1,$col->getSize());
        $this->assertEquals(0,$col->getScale());

        return $col;
    }
    /**
     * @test
     */
    public function testConstructor14() {
        $col = new MSSQLColumn('free_text', 'varchar ', 44);
        $this->assertEquals("'This isn''t good.'", $col->cleanValue("This isn't good."));

        return $col;
    }
    /**
     * @test
     */
    public function testCreateCol00() {
        $colObj = MSSQLColumn::createColObj([]);
        $this->assertNull($colObj);
    }
    /**
     * @test
     */
    public function testCreateCol01() {
        $colObj = MSSQLColumn::createColObj([
            'name' => 'my_col',
            'validator' => function ($orgVal, $cleaned) {
                return 'Hello '.$cleaned;
            }
        ]);
        $this->assertNotNull($colObj);
        $this->assertEquals('[my_col]', $colObj->getName());
        $this->assertEquals('mixed', $colObj->getDatatype());
        $this->assertEquals(1, $colObj->getSize());
        $this->assertEquals("N'Hello Ibrahim'", $colObj->cleanValue('Ibrahim'));
    }
    /**
     * @test
     */
    public function testCustomCleaner00() {
        $col = new MSSQLColumn('hello', 'varchar');
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
        $col = new MSSQLColumn('hello', 'int');
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
        $col = new MSSQLColumn('hello', 'int');
        $col->setCustomFilter(function(){
            return 5;
        });
        $this->assertEquals(5,$col->cleanValue('Hello World.'));
    }
    /**
     * @test
     */
    public function testGetPHPType00() {
        $colObj = new MSSQLColumn();
        $this->assertEquals('string', $colObj->getPHPType());
        $colObj->setIsNull(true);
        $this->assertEquals('string|null', $colObj->getPHPType());
    }
    /**
     * @test
     */
    public function testGetPHPType01() {
        $colObj = new MSSQLColumn('col', 'money');
        $this->assertEquals('float', $colObj->getPHPType());
        $colObj->setIsNull(true);
        $this->assertEquals('float|null', $colObj->getPHPType());
    }
    /**
     * @test
     */
    public function testGetPHPType02() {
        $colObj = new MSSQLColumn('col', 'boolean');
        $this->assertEquals('bool', $colObj->getPHPType());
        $colObj->setIsNull(true);
        $this->assertEquals('bool', $colObj->getPHPType());
    }
    /**
     * @test
     */
    public function testGetPHPType03() {
        $colObj = new MSSQLColumn('col', 'decimal');
        $this->assertEquals('float', $colObj->getPHPType());
        $colObj->setIsNull(true);
        $this->assertEquals('float|null', $colObj->getPHPType());
    }
    /**
     * @test
     */
    public function testGetPHPType04() {
        $colObj = new MSSQLColumn('col', 'binary');
        $this->assertEquals('string', $colObj->getPHPType());
        $colObj->setIsNull(true);
        $this->assertEquals('string|null', $colObj->getPHPType());
    }
    /**
     * @test
     */
    public function testGetPHPType05() {
        $colObj = new MSSQLColumn('col', 'datetime2');
        $this->assertEquals('string', $colObj->getPHPType());
        $colObj->setIsNull(true);
        $this->assertEquals('string|null', $colObj->getPHPType());
    }
    /**
     * @test
     */
    public function testGetPHPType06() {
        $colObj = new MSSQLColumn('col', 'mixed');
        $this->assertEquals('mixed', $colObj->getPHPType());
        $colObj->setIsNull(true);
        $this->assertEquals('mixed|null', $colObj->getPHPType());
    }
    /**
     * @test
     */
    public function testSetDefault00() {
        $col = new MSSQLColumn('date', 'datetime2');
        $col->setDefault('2019-11-09');
        $this->assertEquals('2019-11-09',$col->getDefault());
        $this->assertEquals('[date] [datetime2](1) not null default \'2019-11-09\'',$col.'');
    }
    /**
     * @test
     */
    public function testSetDefault01() {
        $col = new MSSQLColumn('date', 'datetime2');
        $col->setDefault('2019-07-07 09:09:09');
        $this->assertEquals('2019-07-07 09:09:09',$col->getDefault());
        $this->assertEquals('[date] [datetime2](1) not null default \'2019-07-07 09:09:09\'',$col.'');
    }
    /**
     * @test
     */
    public function testSetDefault02() {
        $col = new MSSQLColumn('date', 'datetime2');
        $col->setDefault('');
        $this->assertNull($col->getDefault());
        $this->assertEquals('[date] [datetime2](1) not null',$col.'');
    }
    /**
     * @test
     */
    public function testSetDefault03() {
        $col = new MSSQLColumn('date', 'datetime2');
        $col->setDefault('2019-07-07 09:09:09');
        $this->assertEquals('2019-07-07 09:09:09',$col->getDefault());
        $col->setSize(4);
        $this->assertEquals('[date] [datetime2](4) not null default \'2019-07-07 09:09:09\'',$col.'');
    }
    /**
     * @test
     */
    public function testSetDefault04() {
        $col = new MSSQLColumn('date', 'datetime2');
        $col->setDefault('2019-15-07 09:09:09');
        $this->assertNull($col->getDefault());
        $this->assertEquals('[date] [datetime2](1) not null',$col.'');
        $col->setDefault('2019-12-33 09:09:09');
        $this->assertNull($col->getDefault());
        $this->assertEquals('[date] [datetime2](1) not null',$col.'');
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
        $col = new MSSQLColumn('id', 'int');
        $this->assertEquals('[id] [int] not null',$col.'');
        $col->setDefault(-122);
        $this->assertEquals(-122,$col->getDefault());
        $this->assertEquals('[id] [int] not null default -122',$col.'');
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
        $col = new MSSQLColumn('id', 'varchar');
        $this->assertEquals('[id] [varchar](1) not null',$col.'');
        $col->setDefault('A random string.');
        $this->assertEquals('A random string.',$col->getDefault());
        $this->assertEquals('[id] [varchar](1) not null default \'A random string.\'',$col.'');
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
        $col = new MSSQLColumn('id', 'decimal');
        $this->assertEquals('[id] [decimal](1,0) not null',$col.'');
        $col->setDefault(1);
        $this->assertEquals(1,$col->getDefault());
        $this->assertEquals('[id] [decimal](1,0) not null default 1',$col.'');
        $col->setDefault(1.66);
        $this->assertEquals(1.66,$col->getDefault());
        $this->assertEquals('[id] [decimal](1,0) not null default 1.66',$col.'');
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
        $col = new MSSQLColumn('date', 'datetime2');
        $col->setDefault('now');
        $this->assertEquals('now',$col->getDefault());
        $this->assertEquals('[date] [datetime2](1) not null default getdate()',$col.'');
    }
    /**
     * @test
     */
    public function testSetDefault09() {
        $col = new MSSQLColumn('mix', 'mixed');
        $col->setDefault('2019-11-09');
        $this->assertEquals("2019-11-09",$col->getDefault());
        $this->assertEquals("[mix] [nvarchar](256) not null default N'2019-11-09'",$col.'');
    }
    /**
     * @test
     */
    public function testSetDefault10() {
        $col = new MSSQLColumn('mix', 'mixed');
        $col->setDefault(1);
        $this->assertEquals(1,$col->getDefault());
        $this->assertEquals("[mix] [nvarchar](256) not null default N'1'",$col.'');
    }
    /**
     * 
     * @param MSSQLColumn $col
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
        $this->assertFalse($col->setScale(-1));
        $this->assertEquals(9,$col->getScale());
        $this->assertFalse($col->setScale(10));
        $this->assertEquals(9,$col->getScale());
    }
    /**
     * @test
     */
    public function testSetType00() {
        $col = new MSSQLColumn();
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
        $this->assertEquals(12,$col->getSize());
    }
    
    /**
     * @test
     */
    public function testSetType02() {
        $col = new MSSQLColumn();
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
        $col = new MSSQLColumn();
        $col->setDatatype('datetime2');
        $this->assertEquals('datetime2',$col->getDatatype());
        $this->assertEquals(1,$col->getSize());
        $col->setDefault('2019-01-11');
        $this->assertSame('2019-01-11',$col->getDefault());
    }
    /**
     * @test
     */
    public function testSetType04() {
        $col = new MSSQLColumn();
        $col->setDatatype('datetime2');
        $col->setDefault('2019-01-11 28:00:00');
        $this->assertEquals('datetime2',$col->getDatatype());
        $this->assertEquals(1,$col->getSize());
        $this->assertNull($col->getDefault());
        $col->setDefault('2019-13-11');
        $this->assertNull($col->getDefault());
        $col->setDefault('2019-04-44');
        $this->assertNull($col->getDefault());
        
    }
    /**
     * @test
     */
    public function testSetType05() {
        $col = new MSSQLColumn();
        $col->setDatatype('datetime2');
        $col->setDefault('now');
        $this->assertEquals('now', $col->getDefault());
        $col->setDefault('current_timestamp');
        $this->assertEquals('current_timestamp', $col->getDefault());
    }
    /**
     * @test
     */
    public function testSetType06() {
        $col = new MSSQLColumn();
        $col->setDatatype('datetime2');
        $col->setDefault('now');
        $this->assertEquals('now', $col->getDefault());
        $col->setDefault('current_timestamp');
        $this->assertEquals('current_timestamp', $col->getDefault());
    }
    /**
     * @test
     */
    public function testSetType07() {
        $col = new MSSQLColumn();
        $col->setDatatype('datetime2');
        $col->setSize(0);
        $this->assertEquals(0, $col->getSize());
        $col->setDatatype('datetime2');
        $this->assertEquals(0, $col->getSize());
        $col->setDatatype('int');
        $this->assertEquals(1, $col->getSize());
    }
    /**
     * @test
     */
    public function testMap00() {
        $col = new MSSQLColumn();
        $col->setDatatype('datetime2');
        $col->setDefault('now');
        $this->assertEquals('now', $col->getDefault());
        $col->setDefault('current_timestamp');
        
        $newCol = ColumnFactory::map('mysql', $col);
        $this->assertTrue($newCol instanceof MySQLColumn);
        $this->assertEquals('col', $newCol->getNormalName());
        $this->assertEquals('datetime', $newCol->getDatatype());
        $this->assertEquals('current_timestamp', $newCol->getDefault());
    }
    /**
     * @test
     */
    public function testIdentity00() {
        $col = new MSSQLColumn();
        $this->assertFalse($col->isIdentity());
        $col->setIsIdentity(true);
        $this->assertFalse($col->isIdentity());
        $col->setDatatype('bigint');
        $col->setIsIdentity(true);
        $this->assertTrue($col->isIdentity());
        $col->setDatatype('mixed');
        $this->assertFalse($col->isIdentity());
        $col->setDatatype('int');
        $col->setIsIdentity(true);
        $this->assertTrue($col->isIdentity());
        $col->setDatatype('bigint');
        $col->setIsIdentity(true);
    }
    /**
     * @test
     */
    public function testIdentity01() {
        $col = new MSSQLColumn('iden', 'int');
        $col->setIsIdentity(true);
        $this->assertEquals('[iden] [int] identity(1,1) not null',$col.'');
    }
    /**
     * @test
     */
    public function testIdentity02() {
        $col = new MSSQLColumn('iden', 'bigint');
        $col->setIsIdentity(true);
        $this->assertEquals('[iden] [bigint] identity(1,1) not null',$col.'');
    }
}
