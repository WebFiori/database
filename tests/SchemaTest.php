<?php

namespace webfiori\database\tests;

use PHPUnit\Framework\TestCase;
use webfiori\database\Database;
use webfiori\database\mysql\MySQLTable;
use webfiori\database\mysql\MySQLColumn;
use webfiori\database\mysql\MySQLQuery;
use webfiori\database\AbstractQuery;
use webfiori\database\ConnectionInfo;
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
        $connInfo = new ConnectionInfo('mysql','root', '12345', 'testing_db');
        $s = new Database($connInfo);
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
                . "    `password` varchar(64) not null collate utf8mb4_unicode_520_ci\n\n"
                . ")\n"
                . "engine = InnoDB\n"
                . "default charset = utf8mb4\n"
                . "collate = utf8mb4_unicode_520_ci;", $s->getLastQuery());
        $s->insert([
            'user-id' => 33,
            'username' => 'Ibrahim',
            'pass' => 'rand_pass'
        ]);
        $this->assertEquals('insert into '.$table->getName().' (`user_id`, `username`, `password`) '
                . "values (33, 'Ibrahim', 'rand_pass');", $s->getLastQuery());
        $s->select();
        $this->assertEquals('select * from `hello`',$s->getLastQuery());
        $s->where('user-id', '=', 66);
        $this->assertEquals('select * from `hello` where `hello`.`user_id` = 66',$s->getLastQuery());
        $s->where('user-id', '=', 77);
        $this->assertEquals('select * from `hello` where `hello`.`user_id` = 66 and `hello`.`user_id` = 77',$s->getLastQuery());
        $s->clear();
        $s->select()->where(
                $s->where(
                        $s->where('user-id', '=', 31)
                        )->where('user-id', '<', 44, 'or')
                )->where('username', '!=', 'Ibrahim', 'and');
        $this->assertEquals("select * from `hello` where `hello`.`user_id` = 31 and `hello`.`user_id` < 44 and `hello`.`username` != 'Ibrahim'",$s->getLastQuery());
        $s->page(1, 40);
        $this->assertEquals("select * from `hello` where `hello`.`user_id` = 31 and `hello`.`user_id` < 44 and `hello`.`username` != 'Ibrahim' limit 40 offset 40",$s->getLastQuery());
        $s->page(5, 40);
        $this->assertEquals("select * from `hello` where `hello`.`user_id` = 31 and `hello`.`user_id` < 44 and `hello`.`username` != 'Ibrahim' limit 40 offset 200",$s->getLastQuery());
    }
}
