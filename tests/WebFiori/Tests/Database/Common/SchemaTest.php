<?php

namespace WebFiori\Tests\Database\Common;

use PHPUnit\Framework\TestCase;
use WebFiori\Database\Database;
use WebFiori\Database\MySql\MySQLTable;
use WebFiori\Database\MySql\MySQLColumn;
use WebFiori\Database\MySql\MySQLQuery;
use WebFiori\Database\AbstractQuery;
use WebFiori\Database\ConnectionInfo;
/**
 * Description of SchemaTest
 *
 * @author Ibrahim
 */
class SchemaTest extends TestCase{
    /**
     * @test
     */
    public function test00() {
        $connInfo = new ConnectionInfo('mysql','root', getenv('MYSQL_ROOT_PASSWORD'), 'testing_db', '127.0.0.1');
        $s = new Database($connInfo);
        $this->assertEquals('testing_db', $s->getName());
        $table = new MySQLTable('hello');
        $table->addColumn('user-id', new MySQLColumn('user_id', 'int', 11));
        $this->assertEquals('int',$table->getColByKey('user-id')->getDatatype());
        $table->addColumn('username', new MySQLColumn('username', 'varchar', 15));
        $this->assertEquals('varchar',$table->getColByKey('username')->getDatatype());
        $table->addColumn('pass', new MySQLColumn('password', 'varchar', 64));
        $this->assertEquals('varchar',$table->getColByKey('pass')->getDatatype());
        $s->addTable($table);
        $s->table('hello')->drop();
        $this->assertEquals('drop table '.$table->getName().';', $s->getLastQuery());
        $s->createTable();
        $this->assertEquals("create table if not exists `hello` (\n"
                . "    `user_id` int not null,\n"
                . "    `username` varchar(15) not null collate utf8mb4_unicode_520_ci,\n"
                . "    `password` varchar(64) not null collate utf8mb4_unicode_520_ci\n"
                . ")\n"
                . "engine = InnoDB\n"
                . "default charset = utf8mb4\n"
                . "collate = utf8mb4_unicode_520_ci;", $s->getLastQuery());
        $s->table('hello')->insert([
            'user-id' => 33,
            'username' => 'Ibrahim',
            'pass' => 'rand_pass'
        ]);
        $this->assertEquals('insert into '.$table->getName().' (`user_id`, `username`, `password`) '
                . "values (?, ?, ?);", $s->getLastQuery());
        $s->table('hello')->select();
        $this->assertEquals('select * from `hello`',$s->getLastQuery());
        $s->where('user-id', 66);
        $this->assertEquals('select * from `hello` where `hello`.`user_id` = ?',$s->getLastQuery());
        $s->where('user-id', 77);
        $this->assertEquals('select * from `hello` where `hello`.`user_id` = ? and `hello`.`user_id` = ?',$s->getLastQuery());
        $s->clear();
        $s->table('hello')->select()->where(
                $s->where(
                        $s->where('user-id', 31)
                        )->where('user_id', 44, '<', 'or')
                )->where('username', 'Ibrahim', '!=', 'and');
        $this->assertEquals("select * from `hello` where `hello`.`user_id` = ? and `hello`.`user_id` < ? and `hello`.`username` != ?",$s->getLastQuery());
        $s->page(1, 40);
        $this->assertEquals("select * from `hello` where `hello`.`user_id` = ? and `hello`.`user_id` < ? and `hello`.`username` != ? limit 40",$s->getLastQuery());
        $s->page(5, 40);
        $this->assertEquals("select * from `hello` where `hello`.`user_id` = ? and `hello`.`user_id` < ? and `hello`.`username` != ? limit 40 offset 160",$s->getLastQuery());
    }
    
    /**
     * @test
     */
    public function test01() {
        $connInfo = new ConnectionInfo('mysql','root', getenv('MYSQL_ROOT_PASSWORD'), 'testing_db', '127.0.0.1');
        $s = new Database($connInfo);
        $s->addTable(new HelloTable());
        $s->table('hello')->drop();
        $this->assertEquals('drop table `hello`;', $s->getLastQuery());
        $s->createTable();
        $this->assertEquals("create table if not exists `hello` (\n"
                . "    `user_id` int not null,\n"
                . "    `username` varchar(15) not null collate utf8mb4_unicode_520_ci,\n"
                . "    `password` varchar(64) not null collate utf8mb4_unicode_520_ci\n"
                . ")\n"
                . "engine = InnoDB\n"
                . "default charset = utf8mb4\n"
                . "collate = utf8mb4_unicode_520_ci;", $s->getLastQuery());
        $s->table('hello')->insert([
            'user_id' => 33,
            'username' => 'Ibrahim',
            'pass' => 'rand_pass'
        ]);
        $this->assertEquals('insert into `hello` (`user_id`, `username`, `password`) '
                . "values (?, ?, ?);", $s->getLastQuery());
        $s->table('hello')->select();
        $this->assertEquals('select * from `hello`',$s->getLastQuery());
        $s->where('user-id', 66);
        $this->assertEquals('select * from `hello` where `hello`.`user_id` = ?',$s->getLastQuery());
        $s->where('user_id', 77);
        $this->assertEquals('select * from `hello` where `hello`.`user_id` = ? and `hello`.`user_id` = ?',$s->getLastQuery());
        $s->clear();
        $s->table('hello')->select()->where(
                $s->where(
                        $s->where('user-id', 31)
                        )->where('user-id', 44, '<', 'or')
                )->where('username', 'Ibrahim', '!=', 'and');
        $this->assertEquals("select * from `hello` where `hello`.`user_id` = ? and `hello`.`user_id` < ? and `hello`.`username` != ?",$s->getLastQuery());
        $s->page(1, 40);
        $this->assertEquals("select * from `hello` where `hello`.`user_id` = ? and `hello`.`user_id` < ? and `hello`.`username` != ? limit 40",$s->getLastQuery());
        $s->page(5, 40);
        $this->assertEquals("select * from `hello` where `hello`.`user_id` = ? and `hello`.`user_id` < ? and `hello`.`username` != ? limit 40 offset 160",$s->getLastQuery());
    }
    /**
     * @test
     */
    public function test02() {
        $connInfo = new ConnectionInfo('mysql','root', getenv('MYSQL_ROOT_PASSWORD'), 'testing_db', '127.0.0.1');
        $s = new Database($connInfo);
        
        $s->table('hello');
        $this->assertFalse($s->addTable(new HelloTable()));
    }
    /**
     * @test
     */
    public function test03() {
        $connInfo = new ConnectionInfo('mysql','root', getenv('MYSQL_ROOT_PASSWORD'), 'testing_db', '127.0.0.1');
        $s = new Database($connInfo);
        
        $s->table('hello');
        $s->delete();
        $this->assertEquals("delete from `hello`", $s->getLastQuery());
    }
    /**
     * @test
     */
    public function test04() {
        $connInfo = new ConnectionInfo('mysql','root', getenv('MYSQL_ROOT_PASSWORD'), 'testing_db', '127.0.0.1');
        $s = new Database($connInfo);
        
        $s->table('hello');
        $s->drop();
        $this->assertEquals("drop table `hello`;", $s->getLastQuery());
        $s->select();
        $this->assertEquals("select * from `hello`", $s->getLastQuery());
        $s->limit(50);
        $s->offset(20);
        $this->assertEquals("select * from `hello` limit 50 offset 20", $s->getLastQuery());
        
        $s->insert([
            'user-id' => 33,
            'username' => 'Ibrahim',
            'password' => 'rand_pass'
        ]);
        $this->assertEquals('insert into `hello` (`user_id`, `username`, `password`) '
                . "values (?, ?, ?);", $s->getLastQuery());
    }
    /**
     * @test
     */
    public function test05() {
        $connInfo = new ConnectionInfo('mysql','root', getenv('MYSQL_ROOT_PASSWORD'), 'testing_db', '127.0.0.1');
        $s = new Database($connInfo);
        $this->assertEquals([
            'message' => '',
            'code' => 0
        ], $s->getLastError());
    }
    /**
     * @test
     */
    public function test06() {
        $connInfo = new ConnectionInfo('mysql','root', getenv('MYSQL_ROOT_PASSWORD'), 'testing_db', '127.0.0.1');
        $s = new Database($connInfo);
        try {
            $s->table('hello')->drop()->execute();
        } catch (\Exception $ex) {
            $this->assertEquals([
                'message' => "Unknown table 'testing_db.hello'",
                'code' => 1051
            ], $s->getLastError());
        }
        
    }
}
