<?php

namespace webfiori\database\tests;

use PHPUnit\Framework\TestCase;
use webfiori\database\Database;
use webfiori\database\mysql\MySQLTable;
use webfiori\database\mysql\MySQLColumn;
use webfiori\database\mysql\MySQLQuery;
use webfiori\database\AbstractQuery;
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
        $s = new Database('mysql');
        $table = new MySQLTable('hello');
        $table->addColumn('user-id', new MySQLColumn('user_id', 'int', 11));
        $this->assertEquals('int',$table->getColByKey('user-id')->getDatatype());
        $table->addColumn('username', new MySQLColumn('username', 'varchar', 15));
        $this->assertEquals('varchar',$table->getColByKey('username')->getDatatype());
        $table->addColumn('pass', new MySQLColumn('password', 'varchar', 64));
        $this->assertEquals('varchar',$table->getColByKey('pass')->getDatatype());
        $s->addTable($table);
        $s->table('hello')->drop();
        $this->assertEquals('drop table `'.$table->getName().'`;', $s->getLastQuery());
        $s->createTable();
        $this->assertEquals("create table if not exists `hello` (\n"
                . "    `user_id` int(11) not null,\n"
                . "    `username` varchar(15) not null collate utf8mb4_unicode_520_ci,\n"
                . "    `password` varchar(64) not null collate utf8mb4_unicode_520_ci\n"
                . ")\n"
                . "ENGINE = InnoDB\n"
                . "DEFAULT CHARSET = utf8mb4\n"
                . "collate = utf8mb4_unicode_ci;", $s->getLastQuery());
        $s->insert([
            'user-id' => 33,
            'username' => 'Ibrahim',
            'pass' => 'rand_pass'
        ]);
        $this->assertEquals('insert into `'.$table->getName().'` (`user_id`, `username`, `password`) '
                . "values (33, 'Ibrahim', 'rand_pass');", $s->getLastQuery());
        $s->select();
        $this->assertEquals('select * from `hello`',$s->getLastQuery());
        $s->where('user-id', '=', 66);
        $this->assertEquals('select * from `hello` where `user_id` = 66',$s->getLastQuery());
        $s->where('user-id', '=', 77);
        $this->assertEquals('select * from `hello` where `user_id` = 66 and `user_id` = 77',$s->getLastQuery());
        $s->clear();
        $s->select()->where(
                $s->where(
                        $s->where('user-id', '=', 31)
                        )->where('user-id', '<', 44, 'or')
                )->where('username', '!=', 'Ibrahim', 'and');
        $this->assertEquals('select * from `hello` where ((`user_id` = 31) or `user_id` < 44) and `username` != \'Ibrahim\'',$s->getLastQuery());
        $s->page(1, 40);
        $this->assertEquals('select * from `hello` where ((`user_id` = 31) or `user_id` < 44) and `username` != \'Ibrahim\' limit 40 offset 40',$s->getLastQuery());
        $s->page(5, 40);
        $this->assertEquals('select * from `hello` where ((`user_id` = 31) or `user_id` < 44) and `username` != \'Ibrahim\' limit 40 offset 200',$s->getLastQuery());
    }
}
