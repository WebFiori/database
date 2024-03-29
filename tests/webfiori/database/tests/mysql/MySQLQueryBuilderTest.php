<?php
namespace webfiori\database\tests\mysql;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use super\entity\UserEntity;
use webfiori\database\ConnectionInfo;
use webfiori\database\Database;
use webfiori\database\DatabaseException;
use webfiori\database\mysql\MySQLConnection;
use webfiori\database\tests\mysql\MySQLTestSchema;

/**
 * Description of MySQLQueryBuilderTest
 *
 * @author Ibrahim
 */
class MySQLQueryBuilderTest extends TestCase {
//    public function testConnect() {
//        //Testing multiple connections.
//        
//        $connInfo = new ConnectionInfo('mysql','root', '123456', 'testing_db');
//        $conn = new MySQLConnection($connInfo);
//        $schema = new MySQLTestSchema();
//        $schema->setConnection($conn);
//        
//        $s = new MySQLTestSchema();
//        $c = $s->getConnection();
//        
//        $s2 = new MySQLTestSchema();
//        $c2 = $s2->getConnection();
//        
//        $s3 = new MySQLTestSchema();
//        $c3 = $s3->getConnection();
//        $this->assertTrue(true);
//    }
    public function testCreateTables() {
        $schema = new MySQLTestSchema();
        $this->assertEquals([
            "set character_set_client='utf8'",
            "set character_set_results='utf8'"
        ],$schema->getExecutedQueries());
        $schema->createTables();
        $this->assertEquals("create table if not exists `users` (\n"
                . "    `id` int not null unique auto_increment,\n"
                . "    `first_name` varchar(15) not null collate utf8mb4_unicode_520_ci,\n"
                . "    `last_name` varchar(20) not null collate utf8mb4_unicode_520_ci,\n"
                . "    `age` int not null,\n"
                . "    constraint `users_pk` primary key (`id`)\n"
                . ")\n"
                . "engine = InnoDB\n"
                . "default charset = utf8mb4\n"
                . "collate = utf8mb4_unicode_520_ci;\n"
                . "create table if not exists `users_privileges` (\n"
                . "    `id` int not null unique,\n"
                . "    `can_edit_price` bit(1) not null default b'0',\n"
                . "    `can_change_username` bit(1) not null,\n"
                . "    `can_do_anything` bit(1) not null,\n"
                . "    constraint `users_privileges_pk` primary key (`id`),\n"
                . "    constraint `user_privilege_fk` foreign key (`id`) references `users` (`id`) on update cascade on delete restrict\n"
                . ")\n"
                . "engine = InnoDB\n"
                . "default charset = utf8mb4\n"
                . "collate = utf8mb4_unicode_520_ci;\n"
                . "create table if not exists `users_tasks` (\n"
                . "    `task_id` int not null unique auto_increment,\n"
                . "    `user_id` int not null comment 'The ID of the user who must perform the activity.',\n"
                . "    `created_on` timestamp not null default now(),\n"
                . "    `last_updated` datetime null,\n"
                . "    `is_finished` bit(1) not null default b'0',\n"
                . "    `details` varchar(1500) not null collate utf8mb4_unicode_520_ci,\n"
                . "    constraint `users_tasks_pk` primary key (`task_id`),\n"
                . "    constraint `user_task_fk` foreign key (`user_id`) references `users` (`id`) on update cascade on delete restrict\n"
                . ")\n"
                . "comment 'The tasks at which each user can have.'\n"
                . "engine = InnoDB\n"
                . "default charset = utf8mb4\n"
                . "collate = utf8mb4_unicode_520_ci;\n"
                . "create table if not exists `profile_pics` (\n"
                . "    `user_id` int not null unique,\n"
                . "    `pic` mediumblob not null,\n"
                . "    constraint `profile_pics_pk` primary key (`user_id`),\n"
                . "    constraint `user_profile_pic_fk` foreign key (`user_id`) references `users` (`id`) on update cascade on delete restrict\n"
                . ")\n"
                . "engine = InnoDB\n"
                . "default charset = utf8mb4\n"
                . "collate = utf8mb4_unicode_520_ci;", $schema->getLastQuery());
    }
    /**
     * 
     * @param MySQLTestSchema $schema
     * @depends testSetConnection00
     */
    public function testCreateTable($schema) {
        $schema->table('users')->createTable();
        $this->assertEquals("create table if not exists `users` (\n"
                . "    `id` int not null unique auto_increment,\n"
                . "    `first_name` varchar(15) not null collate utf8mb4_unicode_520_ci,\n"
                . "    `last_name` varchar(20) not null collate utf8mb4_unicode_520_ci,\n"
                . "    `age` int not null,\n"
                . "    constraint `users_pk` primary key (`id`)\n"
                . ")\n"
                . "engine = InnoDB\n"
                . "default charset = utf8mb4\n"
                . "collate = utf8mb4_unicode_520_ci;", $schema->getLastQuery());
        $schema->execute();
        $this->assertEquals([
            "set character_set_client='utf8'",
            "set character_set_results='utf8'",
            "set collation_connection = ?",
            "create table if not exists `users` (\n"
                . "    `id` int not null unique auto_increment,\n"
                . "    `first_name` varchar(15) not null collate utf8mb4_unicode_520_ci,\n"
                . "    `last_name` varchar(20) not null collate utf8mb4_unicode_520_ci,\n"
                . "    `age` int not null,\n"
                . "    constraint `users_pk` primary key (`id`)\n"
                . ")\n"
                . "engine = InnoDB\n"
                . "default charset = utf8mb4\n"
                . "collate = utf8mb4_unicode_520_ci;"
        ], $schema->getExecutedQueries());
        $schema->table('users_privileges')->createTable();
        $this->assertEquals("create table if not exists `users_privileges` (\n"
                . "    `id` int not null unique,\n"
                . "    `can_edit_price` bit(1) not null default b'0',\n"
                . "    `can_change_username` bit(1) not null,\n"
                . "    `can_do_anything` bit(1) not null,\n"
                . "    constraint `users_privileges_pk` primary key (`id`),\n"
                . "    constraint `user_privilege_fk` foreign key (`id`) references `users` (`id`) on update cascade on delete restrict\n"
                . ")\n"
                . "engine = InnoDB\n"
                . "default charset = utf8mb4\n"
                . "collate = utf8mb4_unicode_520_ci;", $schema->getLastQuery());
        $schema->execute();
        $this->assertEquals([
            "set character_set_client='utf8'",
            "set character_set_results='utf8'",
            "set collation_connection = ?",
            "create table if not exists `users` (\n"
                . "    `id` int not null unique auto_increment,\n"
                . "    `first_name` varchar(15) not null collate utf8mb4_unicode_520_ci,\n"
                . "    `last_name` varchar(20) not null collate utf8mb4_unicode_520_ci,\n"
                . "    `age` int not null,\n"
                . "    constraint `users_pk` primary key (`id`)\n"
                . ")\n"
                . "engine = InnoDB\n"
                . "default charset = utf8mb4\n"
                . "collate = utf8mb4_unicode_520_ci;",
            "create table if not exists `users_privileges` (\n"
                . "    `id` int not null unique,\n"
                . "    `can_edit_price` bit(1) not null default b'0',\n"
                . "    `can_change_username` bit(1) not null,\n"
                . "    `can_do_anything` bit(1) not null,\n"
                . "    constraint `users_privileges_pk` primary key (`id`),\n"
                . "    constraint `user_privilege_fk` foreign key (`id`) references `users` (`id`) on update cascade on delete restrict\n"
                . ")\n"
                . "engine = InnoDB\n"
                . "default charset = utf8mb4\n"
                . "collate = utf8mb4_unicode_520_ci;"
        ], $schema->getExecutedQueries());
        $schema->table('users_tasks')->createTable();
        $this->assertEquals("create table if not exists `users_tasks` (\n"
                . "    `task_id` int not null unique auto_increment,\n"
                . "    `user_id` int not null comment 'The ID of the user who must perform the activity.',\n"
                . "    `created_on` timestamp not null default now(),\n"
                . "    `last_updated` datetime null,\n"
                . "    `is_finished` bit(1) not null default b'0',\n"
                . "    `details` varchar(1500) not null collate utf8mb4_unicode_520_ci,\n"
                . "    constraint `users_tasks_pk` primary key (`task_id`),\n"
                . "    constraint `user_task_fk` foreign key (`user_id`) references `users` (`id`) on update cascade on delete restrict\n"
                . ")\n"
                . "comment 'The tasks at which each user can have.'\n"
                . "engine = InnoDB\n"
                . "default charset = utf8mb4\n"
                . "collate = utf8mb4_unicode_520_ci;", $schema->getLastQuery());
        $schema->execute();
        
        $schema->table('profile_pics')->createTable();
        $this->assertEquals("create table if not exists `profile_pics` (\n"
                . "    `user_id` int not null unique,\n"
                . "    `pic` mediumblob not null,\n"
                . "    constraint `profile_pics_pk` primary key (`user_id`),\n"
                . "    constraint `user_profile_pic_fk` foreign key (`user_id`) references `users` (`id`) on update cascade on delete restrict\n"
                . ")\n"
                . "engine = InnoDB\n"
                . "default charset = utf8mb4\n"
                . "collate = utf8mb4_unicode_520_ci;", $schema->getLastQuery());
        $schema->execute();
        
        return $schema;
    }
    /**
     * @test
     */
    public function testSelect000() {
        $schema = new MySQLTestSchema();
        $bulder = $schema->table('users')->select();
        $this->assertEquals('select * from `users`', $schema->getLastQuery());
        $bulder->select(['id','first-name','last-name']);
        $this->assertEquals('select `users`.`id`, `users`.`first_name`, `users`.`last_name` from `users`', $schema->getLastQuery());
        $bulder->select(['id','first-name'=>[
            'as' => 'f_name'
        ],'last-name'=>[
            'alias' => 'l_name'
        ]]);
        $this->assertEquals('select `users`.`id`, `users`.`first_name` as `f_name`, `users`.`last_name` as `l_name` from `users`', $schema->getLastQuery());
        $bulder->orderBy(['id']);
        $this->assertEquals('select `users`.`id`, `users`.`first_name` as `f_name`, `users`.`last_name` as `l_name` from `users` order by `users`.`id`', $schema->getLastQuery());
        $bulder->orderBy(['id','first-name','last-name'=>'d']);
        $this->assertEquals('select `users`.`id`, `users`.`first_name` as `f_name`, `users`.`last_name` as `l_name` from `users` order by `users`.`id`, `users`.`first_name`, `users`.`last_name` desc', $schema->getLastQuery());
    }
    /**
     * @test
     */
    public function testSelectWithWhere000() {
        $schema = new MySQLTestSchema();
        $bulder = $schema->table('users')->select()->where('id', 66);
        $this->assertEquals('select * from `users` where `users`.`id` = ?', $schema->getLastQuery());
        $this->assertEquals([
            'bind' => 'i',
            'values' => [
                66
            ]
        ], $schema->getQueryGenerator()->getBindings());
        $bulder->groupBy('first-name');
        $this->assertEquals('select * from `users` where `users`.`id` = ? group by `users`.`first_name`', $schema->getLastQuery());
        $bulder->groupBy(['first-name','last-name']);
        $this->assertEquals('select * from `users` where `users`.`id` = ? group by `users`.`first_name`, `users`.`last_name`', $schema->getLastQuery());
        $bulder->orderBy(['last-name'=>'a']);
        $this->assertEquals('select * from `users` where `users`.`id` = ? group by `users`.`first_name`, `users`.`last_name` order by `users`.`last_name` asc', $schema->getLastQuery());
    }
    /**
     * @test
     */
    public function testSelectWithWhere001() {
        $schema = new MySQLTestSchema();
        $schema->table('users')->select()->where(
            $schema->where('id', 7)
        );
        $this->assertEquals('select * from `users` where `users`.`id` = ?', $schema->getLastQuery());
        $this->assertEquals([
            'bind' => 'i',
            'values' => [
                7
            ]
        ], $schema->getQueryGenerator()->getBindings());
        $schema->orWhere('first-name', 'Ibrahim');
        $this->assertEquals('select * from `users` where `users`.`id` = ? or `users`.`first_name` = ?', $schema->getLastQuery());
        $this->assertEquals([
            'bind' => 'is',
            'values' => [
                7,
                'Ibrahim'
            ]
        ], $schema->getQueryGenerator()->getBindings());
        $schema->clear();
    }
    
    /**
     * @test
     */
    public function testSelectWithWhere003() {
        $schema = new MySQLTestSchema();
        $q = $schema->table('users')->select();
        $q->where(
            $q->where(
                $q->where('id', 7)
            )
        );
        $this->assertEquals([
            'bind' => 'i',
            'values' => [
                7,
            ]
        ], $schema->getQueryGenerator()->getBindings());
        $this->assertEquals('select * from `users` where `users`.`id` = ?', $schema->getLastQuery());
        $schema->orWhere('first-name', 'Ibrahim');
        $this->assertEquals('select * from `users` where `users`.`id` = ? or `users`.`first_name` = ?', $schema->getLastQuery());
        $this->assertEquals([
            'bind' => 'is',
            'values' => [
                7,
                'Ibrahim'
            ]
        ], $schema->getQueryGenerator()->getBindings());
        $schema->clear();
    }
    
    /**
     * @test
     */
    public function testSelectWithWhere010() {
        $schema = new MySQLTestSchema();
        $q = $schema->table('users')->select();
        //2 Expr (id = 7)
        $q->where(//This where will create an expression of sub query.
            //1 Cond id = 7
            $q->where('id', 7)
            ->orWhere('id', 8)//This will create one big cond
        //4 
        )->orWhere(
                //3 Cond f_name = Ibr
            $q->where('first-name', 'Ibrahim')
            ->andWhere('last-name', 'BinAlshikh')//This will create one big cond
        );
        '((id = 7) and f_n = ibrahim)';
        $this->assertEquals('select * from `users` '
                . 'where ((`users`.`id` = ? or `users`.`id` = ?) '
                . 'or (`users`.`first_name` = ? and `users`.`last_name` = ?))', $schema->getLastQuery());
        $this->assertEquals([
            'bind' => 'iiss',
            'values' => [
                7,
                8,
                'Ibrahim',
                'BinAlshikh'
            ]
        ], $schema->getQueryGenerator()->getBindings());
    }
    /**
     * @test
     */
    public function testSelectWithWhere004() {
        $schema = new MySQLTestSchema();
        $q = $schema->table('users')->select();
        
        $q->where(
            $q->where('id', 7)
        )->where('id', 8)
         ->orWhere('id', 88);
        
        $this->assertEquals('select * from `users` where `users`.`id` = ? and `users`.`id` = ? or `users`.`id` = ?', $schema->getLastQuery());
        $this->assertEquals([
            'bind' => 'iii',
            'values' => [
                7,
                8,
                88
            ]
        ], $schema->getQueryGenerator()->getBindings());
        $schema->orWhere('first-name', 'Ibrahim');
        
        $this->assertEquals('select * from `users` where `users`.`id` = ? '
                . 'and `users`.`id` = ? or `users`.`id` = ? or `users`.`first_name` = ?', $schema->getLastQuery());
        $this->assertEquals([
            'bind' => 'iiis',
            'values' => [
                7,
                8,
                88,
                'Ibrahim'
            ]
        ], $schema->getQueryGenerator()->getBindings());
    }
    /**
     * @test
     */
    public function testSelectWithWhere002() {
        $schema = new MySQLTestSchema();
        $q = $schema->table('users')->select();
        
        $q->where(//This where will create an expression of sub query.
            $q->where('id', 7)
        )->orWhere(
            $q->where('first-name', 'Ibrahim')
        )->andWhere(
            $q->where('last-name', 'BinAlshikh')
        );
        // Expr(Cond) Cond Expr
        // (id = 7) and f_n = Ibrahim
        $this->assertEquals('select * from `users` '
                . 'where `users`.`id` = ? '
                . 'or `users`.`first_name` = ? and `users`.`last_name` = ?', $schema->getLastQuery());
        $this->assertEquals([
            'bind' => 'iss',
            'values' => [
                7,
                'Ibrahim',
                'BinAlshikh'
            ]
        ], $schema->getQueryGenerator()->getBindings());
    }
    /**
     * @test
     * 
     */
    public function testSelectWithWhere011() {
        $schema = new MySQLTestSchema();
        $q = $schema->table('users')->select();
        //4
        $q->where(
            //2 Expr (id = 7)
            $q->where(
                //1 Cond id = 7
                $q->where('id', 7)
            //3 Expr (id = 7) and id = 8
            )->where('id', 8)
        );
        // Expr(Expr(Cond) Cond)
        $this->assertEquals('select * from `users` where `users`.`id` = ? and `users`.`id` = ?', $schema->getLastQuery());
        $this->assertEquals([
            'bind' => 'ii',
            'values' => [
                7,
                8,
            ]
        ], $schema->getQueryGenerator()->getBindings());
    }
    /**
     * @test
     * 
     */
    public function testSelectWithWhere012() {
        $schema = new MySQLTestSchema();
        $q = $schema->table('users')->select();
        //4
        $q->where(
            //2 Expr (id = 7)
            $q->where(
                //1 Cond id = 7
                $q->where('id', null)
            //3 Expr (id = 7) and id = 8
            )->where('id', null, '!=')
        );
        // Expr(Expr(Cond) Cond)
        $this->assertEquals('select * from `users` where `users`.`id` is null and `users`.`id` is not null', $schema->getLastQuery());
        $this->assertEquals([
            'bind' => '',
            'values' => [
                
            ]
        ], $schema->getQueryGenerator()->getBindings());
    }
    /**
     * @test
     * 
     */
    public function testSelectWithWhere009() {
        $schema = new MySQLTestSchema();
        $q = $schema->table('users')->select();
        //4
        $q->where(
            //2 Expr (id = 7)
            $q->where(
                //1 Cond id = 7
                $q->where('id', 7)
            //3 Expr (id = 7) and id = 8
            )->where('id', 8)
             ->where('id', 100)
             ->where('first-name', 44)
        );
        $this->assertEquals('select * from `users` where (`users`.`id` = ? and (`users`.`id` = ? and `users`.`id` = ? and `users`.`first_name` = ?))', $schema->getLastQuery());
        $this->assertEquals([
            'bind' => 'iiis',
            'values' => [
                7,
                8,
                100,
                '44'
            ]
        ], $schema->getQueryGenerator()->getBindings());
    }
    
    /**
     * @test
     */
    public function testSelectWithWhere005() {
        $schema = new MySQLTestSchema();
        $q = $schema->table('users')->select();
        
        //5
        $q->where(
            $q->where(
                $q->where('id', 7)
            )->where(
                $q->where('id', 8)
            )
        );
        $this->assertEquals('select * from `users` where `users`.`id` = ? and `users`.`id` = ?', $schema->getLastQuery());
        $this->assertEquals([
            'bind' => 'ii',
            'values' => [
                7,
                8
            ]
        ], $schema->getQueryGenerator()->getBindings());
        $schema->orWhere('first-name', 'Ibrahim');
        $this->assertEquals('select * from `users` where `users`.`id` = ? and `users`.`id` = ? or `users`.`first_name` = ?', $schema->getLastQuery());
        $this->assertEquals([
            'bind' => 'iis',
            'values' => [
                7,
                8,
                'Ibrahim'
            ]
        ], $schema->getQueryGenerator()->getBindings());
    }
    /**
     * @test
     */
    public function testSelectWithWhere006() {
        $schema = new MySQLTestSchema();
        $q = $schema->table('users')->select();
        
        $q->where('id', 2)
                ->where('id', 9,'!=', 'or')
                ->orWhere('id', 10)
                ->andWhere('id', 30)
                ->andWhere('first-name', 'Ibr', '!=');
        $this->assertEquals('select * from `users` where '
                . '`users`.`id` = ? or '
                . '`users`.`id` != ? or '
                . '`users`.`id` = ? and '
                . '`users`.`id` = ? and '
                . '`users`.`first_name` != ?', $schema->getLastQuery());
        $this->assertEquals([
            'bind' => 'iiiis',
            'values' => [
                2,
                9,
                10,
                30,
                'Ibr'
            ]
        ], $schema->getQueryGenerator()->getBindings());
    }
    /**
     * @test
     */
    public function testSelectWithWhere007() {
        $schema = new MySQLTestSchema();
        $q = $schema->table('users')->select();
        
        $q->where($q->where('id', 2)
                ->where('id', 9,'!=', 'or')
                ->orWhere('id', 10)
                ->andWhere('id', 30)
                ->andWhere('first-name', 'Ibr', '!='));
        $this->assertEquals('select * from `users` where ('
                . '`users`.`id` = ? or '
                . '`users`.`id` != ? or '
                . '`users`.`id` = ? and '
                . '`users`.`id` = ? and '
                . '`users`.`first_name` != ?)', $schema->getLastQuery());
        $this->assertEquals([
            'bind' => 'iiiis',
            'values' => [
                2,
                9,
                10,
                30,
                'Ibr'
            ]
        ], $schema->getQueryGenerator()->getBindings());
    }
    /**
     * @test
     */
    public function testSelectWithWhere008() {
        $schema = new MySQLTestSchema();
        $q = $schema->table('users')->select();
        
        $q->where($q->where($q->where('id', 2)
                ->where('id', 9, '!=', 'or')
                ->orWhere('id', 10)
                ->andWhere('id', 30)
                ->andWhere('first-name', 'Ibr', '!=')));
        $this->assertEquals('select * from `users` where ('
                . '`users`.`id` = ? or '
                . '`users`.`id` != ? or '
                . '`users`.`id` = ? and '
                . '`users`.`id` = ? and '
                . '`users`.`first_name` != ?)', $schema->getLastQuery());
        $this->assertEquals([
            'bind' => 'iiiis',
            'values' => [
                2,
                9,
                10,
                30,
                'Ibr'
            ]
        ], $schema->getQueryGenerator()->getBindings());
    }
    
    public function testDelete00() {
        $schema = new MySQLTestSchema();
        $schema->table('users')
                ->delete()
                ->where('id', 88);
        $this->assertEquals("delete from `users` where `users`.`id` = ?", $schema->getLastQuery());
        $this->assertEquals([
            'bind' => 'i',
            'values' => [
                88,
            ]
        ], $schema->getQueryGenerator()->getBindings());
        $schema->where('id', 55);
        $this->assertEquals("delete from `users` where `users`.`id` = ? and `users`.`id` = ?", $schema->getLastQuery());
        $this->assertEquals([
            'bind' => 'ii',
            'values' => [
                88,
                55
            ]
        ], $schema->getQueryGenerator()->getBindings());
        $schema->orWhere('id', '8', '!=');
        $this->assertEquals("delete from `users` where `users`.`id` = ? and `users`.`id` = ? or `users`.`id` != ?", $schema->getLastQuery());
        $this->assertEquals([
            'bind' => 'iii',
            'values' => [
                88,
                55,
                8
            ]
        ], $schema->getQueryGenerator()->getBindings());
    }
    public function testDelete04() {
        $schema = new MySQLTestSchema();
        $q = $schema->table('users');
        
               $q ->delete()
        ->orWhere(
                $q->orWhere('first-name', 'Ibrahim')
                ->andWhere('last-name', 'BinAlshikh'));
        $this->assertEquals("delete from `users` where (`users`.`first_name` = ? and `users`.`last_name` = ?)", $schema->getLastQuery());
        $this->assertEquals([
            'bind' => 'ss',
            'values' => [
                'Ibrahim',
                'BinAlshikh',
            ]
        ], $schema->getQueryGenerator()->getBindings());
    }
    public function testDelete03() {
        $schema = new MySQLTestSchema();
        $q = $schema->table('users')
                ->delete()
                ->where('id', 88);
        $this->assertEquals("delete from `users` where `users`.`id` = ?", $schema->getLastQuery());
        $q->where('id', 55);
        $this->assertEquals("delete from `users` where `users`.`id` = ? and `users`.`id` = ?", $schema->getLastQuery());
        $this->assertEquals([
            'bind' => 'ii',
            'values' => [
                88,
                55,
            ]
        ], $schema->getQueryGenerator()->getBindings());
    }
    /**
     * @test
     */
    public function testUnion00() {
        $schema = new MySQLTestSchema();
        $schema->table('users')
                ->select(['id' => [
                    'alias' => 'user_id'
                ], 'first-name'])
                ->union($schema->table('users_privileges')->select());
        $this->assertEquals("select `users`.`id` as `user_id`, `users`.`first_name` from `users`"
                . "\nunion\n"
                . "select * from `users_privileges`", $schema->getLastQuery());
    }
    /**
     * @test
     */
    public function testUnion01() {
        $schema = new MySQLTestSchema();
        $schema->table('users')
                ->select(['id' => [
                    'as' => 'user_id'
                ], 'first-name'])
                ->where('id', 44, '!=')
                ->union($schema->table('users_privileges')->select());
        $this->assertEquals("select `users`.`id` as `user_id`, `users`.`first_name` from `users` where `users`.`id` != ?"
                . "\nunion\n"
                . "select * from `users_privileges`", $schema->getLastQuery());
        $this->assertEquals([
            'bind' => 'i',
            'values' => [
                44,
            ]
        ], $schema->getQueryGenerator()->getBindings());
    }
    /**
     * @test
     */
    public function testUnion02() {
        $schema = new MySQLTestSchema();
        $q = $schema->table('users');
                $q->select(['id' => [
                    'alias' => 'user_id'
                ], 'first-name'])
                ->where('id', 44, '!=');
        $this->assertEquals("select `users`.`id` as `user_id`, `users`.`first_name` from `users` where `users`.`id` != ?", $schema->getLastQuery());     
        $this->assertEquals([
            'bind' => 'i',
            'values' => [
                44,
            ]
        ], $schema->getQueryGenerator()->getBindings());
        $q->union($q->table('users_privileges')->select());
        $this->assertEquals("select `users`.`id` as `user_id`, `users`.`first_name` from `users` where `users`.`id` != ?"
                . "\nunion\n"
                . "select * from `users_privileges`", $schema->getLastQuery());
        $q->union($q->table('users_tasks')->select(), true);
        $this->assertEquals("select `users`.`id` as `user_id`, `users`.`first_name` from `users` where `users`.`id` != ?"
                . "\nunion\n"
                . "select * from `users_privileges`"
                . "\nunion all\n"
                . "select * from `users_tasks`", $schema->getLastQuery());
        $this->assertEquals([
            'bind' => 'i',
            'values' => [
                44,
            ]
        ], $schema->getQueryGenerator()->getBindings());
    }
    /**
     * @test
     */
    public function testUnion03() {
        $schema = new MySQLTestSchema();
        $schema->table('users')
                ->select(['id' => [
                    'as' => 'user_id'
                ], 'first-name'])
                ->where('id', 44, '!=');
        $this->assertEquals("select `users`.`id` as `user_id`, `users`.`first_name` from `users` where `users`.`id` != ?", $schema->getLastQuery());
        $schema->getQueryGenerator()->union($schema->table('users_privileges')->select()->where('can-edit_price', true));
        $this->assertEquals("select `users`.`id` as `user_id`, `users`.`first_name` from `users` where `users`.`id` != ?"
                . "\nunion\n"
                . "select * from `users_privileges` where `users_privileges`.`can_edit_price` = ?", $schema->getLastQuery());
        $this->assertEquals([
            'bind' => 'ii',
            'values' => [
                44,
                1
            ]
        ], $schema->getQueryGenerator()->getBindings());
    }
    /**
     * @test
     */
    public function testReplace00() {
        $schema = new MySQLTestSchema();
        $q = $schema->table('users');
        $q->replace([
            'id' => 8,
            'first-name' => 'Ibrahim',
            'last-name' => 'BinAlshikh'
        ]);
        $this->assertEquals("replace into `users` (`id`, `first_name`, `last_name`) values (?, ?, ?);", $schema->getLastQuery());
    }
    /**
     * @test
     */
    public function testReplace01() {
        $schema = new MySQLTestSchema();
        $q = $schema->table('users');
        $q->replace([
            'cols' => [
                'id','first-name','last-name'
            ],
            'values' => [
                [8,'Ibrahim','BinAlshikh'],
                [9,'Web','DB']
            ],
                
        ]);
        $this->assertEquals("replace into `users` (`id`, `first_name`, `last_name`)\nvalues\n"
                . "(?, ?, ?),\n"
                . "(?, ?, ?);", $schema->getLastQuery());
    }
    /**
     * @test
     */
    public function testReplace02() {
        $schema = new MySQLTestSchema();
        $q = $schema->table('users_tasks');
        $q->replace([
            'user-id' => 6,
            'details' => 'OK task'
        ]);
        $this->assertEquals("replace into `users_tasks` "
                . "(`user_id`, `details`, `created_on`, `is_finished`) "
                . "values (?, ?, ?, ?);", $schema->getLastQuery());
        $q->replace([
            'user-id' => 6,
            'details' => 'OK task',
            'created-on' => '2020-10-16 00:00:00',
            'is-finished' => true
        ]);
        $this->assertEquals("replace into `users_tasks` "
                . "(`user_id`, `details`, `created_on`, `is_finished`) "
                . "values (?, ?, ?, ?);", $schema->getLastQuery());
    }
    /**
     * @test
     */
    public function testReplace05() {
        $schema = new MySQLTestSchema();
        $q = $schema->table('users_tasks');
        $q->replace([
            'user-id' => null,
            'details' => 'OK task'
        ]);
        $this->assertEquals("replace into `users_tasks` "
                . "(`user_id`, `details`, `created_on`, `is_finished`) "
                . "values (?, ?, ?, ?);", $schema->getLastQuery());
    }
    /**
     * @test
     */
    public function testInsert00() {
        $schema = new MySQLTestSchema();
        $q = $schema->table('users');
        $q->insert([
            'id' => 8,
            'first-name' => 'Ibrahim',
            'last-name' => 'BinAlshikh'
        ]);
        $this->assertEquals("insert into `users` (`id`, `first_name`, `last_name`) values (?, ?, ?);", $schema->getLastQuery());
    }
    /**
     * @test
     */
    public function testInsert01() {
        $schema = new MySQLTestSchema();
        $q = $schema->table('users');
        $q->insert([
            'cols' => [
                'id','first-name','last-name'
            ],
            'values' => [
                [8,'Ibrahim','BinAlshikh'],
                [9,'Web','DB']
            ],
                
        ]);
        $this->assertEquals("insert into `users` (`id`, `first_name`, `last_name`)\nvalues\n"
                . "(?, ?, ?),\n"
                . "(?, ?, ?);", $schema->getLastQuery());
    }
    /**
     * @test
     */
    public function testInsert02() {
        $schema = new MySQLTestSchema();
        $q = $schema->table('users_tasks');
        $q->insert([
            'user-id' => 6,
            'details' => 'OK task'
        ]);
        $this->assertEquals("insert into `users_tasks` "
                . "(`user_id`, `details`, `created_on`, `is_finished`) "
                . "values (?, ?, ?, ?);", $schema->getLastQuery());
        $q->insert([
            'user-id' => 6,
            'details' => 'OK task',
            'created-on' => '2020-10-16 00:00:00',
            'is-finished' => true
        ]);
        $this->assertEquals("insert into `users_tasks` "
                . "(`user_id`, `details`, `created_on`, `is_finished`) "
                . "values (?, ?, ?, ?);", $schema->getLastQuery());
    }
    /**
     * @test
     */
    public function testInsert05() {
        $schema = new MySQLTestSchema();
        $q = $schema->table('users_tasks');
        $q->insert([
            'user-id' => null,
            'details' => 'OK task'
        ]);
        $this->assertEquals("insert into `users_tasks` "
                . "(`user_id`, `details`, `created_on`, `is_finished`) "
                . "values (?, ?, ?, ?);", $schema->getLastQuery());
    }
    /**
     * @test
     */
    public function testUpdate00() {
        $schema = new MySQLTestSchema();
        $q = $schema->table('users_tasks');
        
        $q->update([
            'details' => 'OKKKKKKKk'
        ]);
        $date = date('Y-m-d H:i:s');
        $this->assertEquals("update `users_tasks` set `details` = ?, `last_updated` = ?", $schema->getLastQuery());
        $this->assertEquals([
            'bind' => 'ss',
            'values' => [
                'OKKKKKKKk',
                $date
            ]
        ], $schema->getQueryGenerator()->getBindings());
    }
    /**
     * @test
     */
    public function testUpdate01() {
        $schema = new MySQLTestSchema();
        $q = $schema->table('users_tasks');
        
        $q->update([
            'details' => 'OK'
        ])->where('task-id', 77);
        $date = date('Y-m-d H:i:s');
        $this->assertEquals("update `users_tasks` set `details` = ?, `last_updated` = ? where `users_tasks`.`task_id` = ?", $schema->getLastQuery());
        $this->assertEquals([
            'bind' => 'ssi',
            'values' => [
                'OK',
                $date,
                77
            ]
        ], $schema->getQueryGenerator()->getBindings());
        $q->andWhere('user-id', 6);
        $this->assertEquals("update `users_tasks` set `details` = ?, `last_updated` = ? "
                . "where `users_tasks`.`task_id` = ? and `users_tasks`.`user_id` = ?", $schema->getLastQuery());
        $this->assertEquals([
            'bind' => 'ssii',
            'values' => [
                'OK',
                $date,
                77,
                6
            ]
        ], $schema->getQueryGenerator()->getBindings());
    }
     /**
     * @test
     */
    public function testUpdate02() {
        $schema = new MySQLTestSchema();
        $q = $schema->table('users_tasks');
        
        $q->update([
            'details' => null
        ])->where('task-id', 77);
        $date = date('Y-m-d H:i:s');
        $this->assertEquals("update `users_tasks` set `details` = null, `last_updated` = ? where `users_tasks`.`task_id` = ?", $schema->getLastQuery());
        $this->assertEquals([
            'bind' => 'si',
            'values' => [
                $date,
                77
            ]
        ], $schema->getQueryGenerator()->getBindings());
        $q->andWhere('user-id', 6);
        $this->assertEquals("update `users_tasks` set `details` = null, `last_updated` = ? "
                . "where `users_tasks`.`task_id` = ? and `users_tasks`.`user_id` = ?", $schema->getLastQuery());
        $this->assertEquals([
            'bind' => 'sii',
            'values' => [
                $date,
                77,
                6
            ]
        ], $schema->getQueryGenerator()->getBindings());
    }
     /**
     * @test
     */
    public function testUpdate03() {
        $schema = new MySQLTestSchema();
        $q = $schema->table('users_tasks');
        
        $q->update([
            'details' => null
        ])->where('last-updated', '2021-07-13');
        $date = date('Y-m-d H:i:s');
        $this->assertEquals("update `users_tasks` set `details` = null, "
                . "`last_updated` = ? where `users_tasks`.`last_updated` = ?", $schema->getLastQuery());
        $this->assertEquals([
            'bind' => 'ss',
            'values' => [
                $date,
                '2021-07-13',
            ]
        ], $schema->getQueryGenerator()->getBindings());
        $q->andWhere('user-id', 6);
        $this->assertEquals("update `users_tasks` set `details` = null, `last_updated` = ? "
                . "where `users_tasks`.`last_updated` = ? and `users_tasks`.`user_id` = ?", $schema->getLastQuery());
        $this->assertEquals([
            'bind' => 'ssi',
            'values' => [
                $date,
                '2021-07-13',
                6
            ]
        ], $schema->getQueryGenerator()->getBindings());
    }
    /**
     * @test
     */
    public function testAddColumn00() {
        $schema = new MySQLTestSchema();
        $q = $schema->table('users_tasks');
        $q->addCol('details');
        $this->assertEquals("alter table `users_tasks` add `details` varchar(1500) not null collate utf8mb4_unicode_520_ci;", $schema->getLastQuery());
    }
    /**
     * @test
     */
    public function testAddColumn01() {
        $schema = new MySQLTestSchema();
        $q = $schema->table('users_tasks');
        $q->addCol('details', 'first');
        $this->assertEquals("alter table `users_tasks` add `details` varchar(1500) not null collate utf8mb4_unicode_520_ci first;", $schema->getLastQuery());
    }
    /**
     * @test
     */
    public function testAddColumn02() {
        $schema = new MySQLTestSchema();
        $q = $schema->table('users_tasks');
        $q->addCol('details', 'is-finished');
        $this->assertEquals("alter table `users_tasks` add `details` varchar(1500) not null collate utf8mb4_unicode_520_ci after `is_finished`;", $schema->getLastQuery());
    }
    /**
     * @test
     */
    public function testAddColumn03() {
        $this->expectException(DatabaseException::class);
        $schema = new MySQLTestSchema();
        $q = $schema->table('users_tasks');
        $q->addCol('details', 'not-exist');
        $this->assertEquals("alter table `users_tasks` add `details` varchar(1500) not null collate utf8mb4_unicode_520_ci after `is_finished`;", $schema->getLastQuery());
    }
    /**
     * @test
     */
    public function testModifyCol00() {
        $schema = new MySQLTestSchema();
        $q = $schema->table('users_tasks');
        $q->modifyCol('details');
        $this->assertEquals("alter table `users_tasks` change column `details` `details` varchar(1500) not null collate utf8mb4_unicode_520_ci;", $schema->getLastQuery());
    }
    /**
     * @test
     */
    public function testModifyCol01() {
        $schema = new MySQLTestSchema();
        $q = $schema->table('users_tasks');
        $q->modifyCol('details', 'first');
        $this->assertEquals("alter table `users_tasks` change column `details` `details` varchar(1500) not null collate utf8mb4_unicode_520_ci first;", $schema->getLastQuery());
    }
    /**
     * @test
     */
    public function testModifyCol02() {
        $schema = new MySQLTestSchema();
        $q = $schema->table('users_tasks');
        $q->modifyCol('details', 'user-id');
        $this->assertEquals("alter table `users_tasks` change column `details` `details` varchar(1500) not null collate utf8mb4_unicode_520_ci after `user_id`;", $schema->getLastQuery());
    }
    /**
     * @test
     */
    public function testModifyCol03() {
        $schema = new MySQLTestSchema();
        $this->expectException(DatabaseException::class);
        $q = $schema->table('users_tasks');
        $q->modifyCol('details', 'xx-id');
    }
    /**
     * @test
     * 
     * @return MySQLTestSchema Description
     */
    public function testSetConnection00() {
        $connInfo = new ConnectionInfo('mysql','root', '123456', 'testing_db', '127.0.0.1');
        $conn = new MySQLConnection($connInfo);
        $schema = new MySQLTestSchema();
        $schema->setConnection($conn);
        $this->assertEquals([
            "set character_set_client='utf8'",
            "set character_set_results='utf8'"
        ], $schema->getExecutedQueries());
        $this->assertTrue(true);
        return $schema;
    }
    /**
     * @test
     * 
     * @return MySQLTestSchema Description
     */
    public function testSetConnection01() {
        $this->expectException(DatabaseException::class);
        $connInfo = new ConnectionInfo('mysql', 'root', '12345', 'testing_db', '127.0.0.1');
        $conn = new MySQLConnection($connInfo);
        $schema = new MySQLTestSchema();
        $schema->setConnection($conn);
        return $schema;
    }
    public function testSetQuery00() {
        $schema = new MySQLTestSchema();
        $schema->table('users_tasks')->select()->whereBetween('task-id', 0, 33);
        $this->assertEquals('select * from `users_tasks` where (`users_tasks`.`task_id` between ? and ?)', $schema->getLastQuery());
        $schema->setQuery('select * from users');
        $this->assertEquals('select * from users', $schema->getLastQuery());
    }
    /**
     * 
     * @param MySQLTestSchema $schema
     * @depends testSetConnection00
     */
//    public function testDropTable00($schema) {
//        $this->expectException(DatabaseException::class);
//        $this->expectExceptionMessage("1146 - Table 'testing_db.users_privileges' doesn't exist");
//        $schema->table('users_privileges')->select()->execute();
//        $this->assertEquals(0, $schema->getLastResultSet()->getRowsCount());
//        $schema->table('users_privileges')->drop()->execute();
//        $schema->table('users_privileges')->select()->execute();
//    }
    /**
     * @test
     * @param MySQLTestSchema $schema
     * @depends testCreateTable
     */
    public function testInsert03($schema) {
        $schema->table('users')->insert([
            'first-name' => 'Ibrahim',
            'last-name' => 'BinAlshikh',
            'age' => 28
        ])->execute();
        $schema->table('users')->select()->execute();
        $this->assertEquals(1, $schema->getLastResultSet()->getRowsCount());
        return $schema;
    }
    /**
     * @test
     * @param MySQLTestSchema $schema
     * @depends testInsert03
     */
    public function testDropRecord00($schema) {
        $row = $schema->getLastResultSet()->getRows()[0];
        $schema->table('users')->delete()->where('id', $row['id']);
        $this->assertEquals('delete from `users` where `users`.`id` = ?', $schema->getLastQuery());
        $this->assertEquals([
            'bind' => 'i',
            'values' => [
                $row['id']
            ]
        ], $schema->getQueryGenerator()->getBindings());
        $schema->execute();
        $schema->table('users')->select()->execute();
        $this->assertEquals(0, $schema->getLastResultSet()->getRowsCount());
        return $schema;
    }
    /**
     * 
     * @test
     * @param MySQLTestSchema $schema
     * @depends testDropRecord00
     */
    public function testInsert04($schema) {
        $schema->table('users')->insert([
            'cols' => [
                'id','first-name','last-name','age'
            ],
            'values' => [
                [100,'Ali','Hassan',16],
                [101,'Dabi','Jona',19]
            ]
        ]);
        $this->assertEquals("insert into `users` (`id`, `first_name`, `last_name`, `age`)\nvalues\n"
                . "(?, ?, ?, ?),\n"
                . "(?, ?, ?, ?);", $schema->getLastQuery());
        $schema->execute();
        $schema->table('users')->select()->execute();
        $resultSet = $schema->getLastResultSet();
        $this->assertEquals(2, $resultSet->getRowsCount());
        
        
        $this->assertEquals([
            ['id'=>100,'first_name'=>'Ali','last_name'=>'Hassan','age'=>16],
            ['id'=>101,'first_name'=>'Dabi','last_name'=>'Jona','age'=>19]
        ], $resultSet->getRows());
        
        $this->assertEquals([
            ['id'=>100,'first_name'=>'Ali','last_name'=>'Hassan','age'=>16],
            ['id'=>101,'first_name'=>'Dabi','last_name'=>'Jona','age'=>19]
        ], $resultSet->getRows());
        $schema->table('users')->insert([
            'cols' => [
                'id','first-name','last-name','age'
            ],
            'values' => [
                [102,'Jon','Mark',22],
                [103,'Ibrahim','Ali',27]
            ]
        ])->execute();
        $schema->table('users')->select()->execute();
        $resultSet = $schema->getLastResultSet();
        foreach ($resultSet as $row) {
            if ($row['id'] == 100) {
                $this->assertEquals('Ali', $row['first_name']);
            }
            if ($row['id'] == 101) {
                $this->assertEquals('Dabi', $row['first_name']);
            }
            if ($row['id'] == 102) {
                $this->assertEquals('Jon', $row['first_name']);
            }
            if ($row['id'] == 103) {
                $this->assertEquals('Ibrahim', $row['first_name']);
            }
        }
        return $schema;
    }
    /**
     * 
     * 
     * @test
     * @param MySQLTestSchema $schema
     * @depends testInsert04
     */
    public function testMappedResult00($schema) {
        $mapper = $schema->getTable('users')->getEntityMapper();
        $mapper->setEntityName('UserEntity');
        $mapper->setNamespace('super\entity');
        $mapper->setPath(__DIR__);
        $mapper->create();
        require_once $mapper->getAbsolutePath();
        $resultSet = $schema->getLastResultSet();
        $newSet = $resultSet->map(function($record) {
            $obj = new UserEntity();
            $obj->setAge($record['age']);
            $obj->setFirstName($record['first_name']);
            $obj->setId($record['id']);
            $obj->setLastName($record['last_name']);
            return $obj;
        });
        $data = $newSet->getRows();
        foreach ($data as $obj) {
            $this->assertTrue($obj instanceof UserEntity);
        }
    }
    /**
     * @test
     */
    public function testDropPrimaryKey00() {
        $schema = new MySQLTestSchema();
        $schema->table('users')->dropPrimaryKey();
        $this->assertEquals('alter table `users` drop primary key;', $schema->getLastQuery());
    }
    /**
     * @test
     */
    public function testAddPrimaryKey00() {
        $schema = new MySQLTestSchema();
        $schema->table('users')->addPrimaryKey('my_key', ['id']);
        $this->assertEquals('alter table `users` add constraint `my_key` primary key (`id`);', $schema->getLastQuery());
        $schema->table('users')->addPrimaryKey('my_key', ['first-name', 'last-name']);
        $this->assertEquals('alter table `users` add constraint `my_key` primary key (`first_name`, `last_name`);', $schema->getLastQuery());
    }
    /**
     * @test
     */
    public function testDropCol00() {
        $schema = new MySQLTestSchema();
        $schema->table('users')->dropCol('id');
        $this->assertEquals('alter table `users` drop column `id`;', $schema->getLastQuery());
        $schema->table('users')->dropCol('first-name ');
        $this->assertEquals('alter table `users` drop column `first_name`;', $schema->getLastQuery());
    }
    /**
     * @test
     */
    public function testDropCol01() {
        $schema = new MySQLTestSchema();
        $schema->table('users')->dropCol('not-exist');
        $this->assertEquals('alter table `users` drop column `not_exist`;', $schema->getLastQuery());
    }
    /**
     * @test
     */
    public function testJoin00() {
        $schema = new MySQLTestSchema();
        $queryBuilder = $schema->getQueryGenerator();
        $queryBuilder->table('users')->join($queryBuilder->table('users_privileges'))->select();
        $this->assertEquals("select * from (select * from `users` join `users_privileges`) as `T1`", $schema->getLastQuery());
        $queryBuilder->on('id', 'id')->select();
        $this->assertEquals("select * from (select * from `users` join `users_privileges` on(`users`.`id` = `users_privileges`.`id`)) as `T1`", $schema->getLastQuery());
        //$schema->execute();
        
    }
    /**
     * @test
     */
    public function testJoin01() {
        $schema = new MySQLTestSchema();
        $queryBuilder = $schema->getQueryGenerator();
        $queryBuilder->table('users')->join(
                $queryBuilder->table('users_privileges')->select()->where('id', 3)
                )->on('id', 'id')->select();
        $this->assertEquals("select * from (select * from `users` join `users_privileges` on(`users`.`id` = `users_privileges`.`id`) "
                . "where `users_privileges`.`id` = ?) as `T1`", $schema->getLastQuery());
    }
    /**
     * @test
     */
    public function testJoin02() {
        $schema = new MySQLTestSchema();
        $queryBuilder = $schema->getQueryGenerator();
        $queryBuilder->table('users')->select()->where('id', 88)->join(
            $queryBuilder->table('users_privileges')
        )->on('id', 'id')->select();
        $this->assertEquals("select * from (select * from `users` join `users_privileges` on(`users`.`id` = `users_privileges`.`id`) "
                . "where `users`.`id` = ?) as `T1`", $schema->getLastQuery());
    }
    /**
     * @test
     */
    public function testJoin03() {
        $schema = new MySQLTestSchema();
        $queryBuilder = $schema->getQueryGenerator();
        $queryBuilder->table('users')->join(
                $queryBuilder->table('users_privileges')->select()->where('id', 3)
                )->on('id', 'id')->select();
        $this->assertEquals("select * from (select * from `users` join `users_privileges` on(`users`.`id` = `users_privileges`.`id`) "
                . "where `users_privileges`.`id` = ?) as `T1`", $schema->getLastQuery());
    }
    /**
     * @test
     */
    public function testJoin04() {
        $schema = new MySQLTestSchema();
        $queryBuilder = $schema->getQueryGenerator();
        $queryBuilder->table('users')->join(
            $queryBuilder->table('users_privileges')->select()->where('id', 3)
        )->on('id', 'id')->select([
            'id','first-name','last-name','can-edit-price','can-do-anything'
        ]);
        $this->assertEquals(""
                . "select `T1`.`id`, "
                . "`T1`.`first_name`, "
                . "`T1`.`last_name`, "
                . "`T1`.`can_edit_price`, "
                . "`T1`.`can_do_anything` from ("
                . "select * "
                . "from `users` join `users_privileges` on(`users`.`id` = `users_privileges`.`id`) "
                . "where `users_privileges`.`id` = ?) as `T1`", $schema->getLastQuery());
        $this->assertEquals([
            'bind' => 'i',
            'values' => [
                3,
            ]
        ], $schema->getQueryGenerator()->getBindings());
        
        $queryBuilder->resetBinding();
        $queryBuilder->table('users')->select(['id'])->join(
            $queryBuilder->table('users_privileges')->select()->where('id', 3)
        )->on('id', 'id')->select([
            'id','first-name','last-name','can-edit-price','can-do-anything'
        ]);
        $this->assertEquals(""
                . "select `T1`.`id`, "
                . "`T1`.`first_name`, "
                . "`T1`.`last_name`, "
                . "`T1`.`can_edit_price`, "
                . "`T1`.`can_do_anything` from ("
                . "select `users`.`id`, `users_privileges`.* "
                . "from `users` join `users_privileges` on(`users`.`id` = `users_privileges`.`id`) "
                . "where `users_privileges`.`id` = ?) as `T1`", $schema->getLastQuery());
        $this->assertEquals([
            'bind' => 'i',
            'values' => [
                3,
            ]
        ], $schema->getQueryGenerator()->getBindings());
    }
    /**
     * @test
     */
    public function testJoin05() {
        $str = 'T0';

        $schema = new MySQLTestSchema();
        $queryBuilder = $schema->getQueryGenerator();
        $queryBuilder->table('users')->join(
            $queryBuilder->table('users_privileges')
        )->on('id', 'id')->join(
            $queryBuilder->table('users_tasks')
        )->on('id', 'user-id')->select();
        $this->assertEquals(""
                . "select * from (select * from ("
                . "select * from `users` join `users_privileges` "
                . "on(`users`.`id` = `users_privileges`.`id`)) "
                . "as `T1` join `users_tasks` on(`T1`.`id` = `users_tasks`.`user_id`)) as `T2`", $schema->getLastQuery());
    }
    /**
     * @test
     */
    public function testJoin06() {
        $schema = new MySQLTestSchema();
        $queryBuilder = $schema->getQueryGenerator();
        $queryBuilder->table('users')->join(
            $queryBuilder->table('users_privileges')->select(['can-edit-price','can-change-username'])
        )->on('id', 'id')->join(
            $queryBuilder->table('users_tasks')
        )->on('id', 'user-id')->select();
        $this->assertEquals("select * from (select * from ("
                . "select `users`.*, "
                . "`users_privileges`.`can_edit_price`, "
                . "`users_privileges`.`can_change_username` from `users` join `users_privileges` "
                . "on(`users`.`id` = `users_privileges`.`id`)) "
                . "as `T1` join `users_tasks` on(`T1`.`id` = `users_tasks`.`user_id`)) as `T2`", $schema->getLastQuery());
    }
    /**
     * @test
     */
    public function testJoin07() {
        $schema = new MySQLTestSchema();
        $queryBuilder = $schema->getQueryGenerator();
        $queryBuilder->table('users')->join(
            $queryBuilder->table('users_privileges')->select(['can-edit-price','can-change-username'])
        )->on('id', 'id')->select([
                'id'
        ]);
//        
        $this->assertEquals("select `T1`.`id` from (select "
                . "`users`.*, "
                . "`users_privileges`.`can_edit_price`, "
                . "`users_privileges`.`can_change_username` "
                . "from `users` join `users_privileges` "
                . "on(`users`.`id` = `users_privileges`.`id`)) as `T1`", $schema->getLastQuery());
        
        $queryBuilder->join(
            $queryBuilder->table('users_tasks')->select(['task-id', 'created-on' => [
                'as' => 'created'
            ]])
        )->on('id', 'user-id')->select([
                'id'
        ]);
        $this->assertEquals("select `T2`.`id` from (select `T1`.`id` from (select "
                . "`users`.*, "
                . "`users_privileges`.`can_edit_price`, "
                . "`users_privileges`.`can_change_username` "
                . "from `users` join `users_privileges` "
                . "on(`users`.`id` = `users_privileges`.`id`)) as `T1` join `users_tasks` on(`T1`.`id` = `users_tasks`.`user_id`)) as `T2`", $schema->getLastQuery());
        
        $queryBuilder->join($queryBuilder->table('profile_pics'))->on('id', 'user-id')->select();
        
        $this->assertEquals("select * from ("
                . "select `T2`.`id` from (select `T1`.`id` from (select "
                . "`users`.*, "
                . "`users_privileges`.`can_edit_price`, "
                . "`users_privileges`.`can_change_username` "
                . "from `users` join `users_privileges` "
                . "on(`users`.`id` = `users_privileges`.`id`)) as `T1` join `users_tasks` on(`T1`.`id` = `users_tasks`.`user_id`)) as `T2` "
                . "join `profile_pics` on(`T2`.`id` = `profile_pics`.`user_id`)) as `T3`", $schema->getLastQuery());
        
        $queryBuilder->join($queryBuilder->table('users'))->on('id', 'id')->select();
        
        $this->assertEquals("select * from (select * from ("
                . "select `T2`.`id` from (select `T1`.`id` from (select "
                . "`users`.*, "
                . "`users_privileges`.`can_edit_price`, "
                . "`users_privileges`.`can_change_username` "
                . "from `users` join `users_privileges` "
                . "on(`users`.`id` = `users_privileges`.`id`)) as `T1` join `users_tasks` on(`T1`.`id` = `users_tasks`.`user_id`)) as `T2` "
                . "join `profile_pics` on(`T2`.`id` = `profile_pics`.`user_id`)) as `T3` join `users` on(`T3`.`id` = `users`.`id`)) as `T4`", $schema->getLastQuery());
    }
    /**
     * @test
     */
    public function testJoin08() {
        $schema = new MySQLTestSchema();
        $queryBuilder = $schema->getQueryGenerator();
        $queryBuilder->table('users')
                ->select([
            'id' => [
                'alias' => 'user_id'
            ]
        ])->join(
            $queryBuilder->table('users_privileges')
        )->on('id', 'id')->select();
        
        $this->assertEquals("select * from ("
                . "select `users`.`id` as `user_id`, `users_privileges`.* from `users` "
                . "join `users_privileges` on(`users`.`id` = `users_privileges`.`id`)) as `T1`", $schema->getLastQuery());
        $queryBuilder->join($queryBuilder->table('profile_pics'))->on('id', 'user-id')->select(); 
        
        $this->assertEquals("select * from (select * from ("
                . "select `users`.`id` as `user_id`, `users_privileges`.* from `users` "
                . "join `users_privileges` on(`users`.`id` = `users_privileges`.`id`)) as `T1` "
                . "join `profile_pics` on(`T1`.`user_id` = `profile_pics`.`user_id`)) as `T2`",$queryBuilder->getQuery());
    //$schema->execute();
        
    }
    /**
     * @test
     */
    public function testRenameCol00() {
        $schema = new MySQLTestSchema();
        $schema->getTable('users')->getColByKey('id')->setName('user_id');
        
        $queryBuilder = $schema->getQueryGenerator();
        $queryBuilder->table('users')->renameCol('id');
        $this->assertEquals('alter table `users` rename column `id` to `user_id`;', $schema->getLastQuery());
    }
    /**
     * @test
     */
    public function testRenameCol01() {
        $this->expectException(DatabaseException::class);
        $this->expectExceptionMessage('The table `users` has no column with key \'not-exist\'.');
        $schema = new MySQLTestSchema();
        $schema->table('users')->renameCol('not-exist');
    }
    /**
     * @test
     */
    public function testRenameCol02() {
        $schema = new MySQLTestSchema();
        $schema->table('users')->renameCol('id');
        $this->assertEquals('alter table `users` rename column `id` to `id`;', $schema->getLastQuery());
    }
    /**
     * @test
     */
    public function testAddFk00() {
        $schema = new MySQLTestSchema();
        $schema->table('users_tasks')->addForeignKey('user_task_fk');
        $this->assertEquals("alter table `users_tasks` add constraint user_task_fk foreign key (`user_id`) references `users` (`id`) on update cascade on delete restrict;", $schema->getLastQuery());
    }
    /**
     * @test
     */
    public function testAddFk01() {
        $schema = new MySQLTestSchema();
        $this->expectException(DatabaseException::class);
        $this->expectExceptionMessage("No such foreign key: 'xyz'.");
        $schema->table('users_tasks')->addForeignKey('xyz');
    }
    /**
     * @test
     */
    public function testLike00() {
        $schema = new MySQLTestSchema();
        $schema->table('users_tasks')->select()->whereLike('task-id', 9);
        $this->assertEquals("select * from `users_tasks`", $schema->getLastQuery()); 
    }
    /**
     * @test
     */
    public function testLike01() {
        $schema = new MySQLTestSchema();
        $schema->table('users')->select()->whereLike('first-name', '%Ibra%');
        $this->assertEquals("select * from `users` where `users`.`first_name` like ?", $schema->getLastQuery()); 
    }
    /**
     * @test
     */
    public function testLike02() {
        $schema = new MySQLTestSchema();
        $schema->table('users')->select()->whereNotLike('first-name', '%Ibra%');
        $this->assertEquals("select * from `users` where `users`.`first_name` not like ?", $schema->getLastQuery()); 
    }
    /**
     * @test
     */
    public function testLike03() {
        $schema = new MySQLTestSchema();
        $schema->table('users_tasks')->select()->whereNotLike('first-naome', '%Ibra%');
        $this->assertEquals("select * from `users_tasks` where `users_tasks`.`first_naome` not like ?", $schema->getLastQuery());
    }
    /**
     * @test
     */
    public function testWhereIn00() {
        $schema = new MySQLTestSchema();
        $schema->table('users_tasks')->select()->whereIn('task-id', [7,"9","100"]);
        $this->assertEquals("select * from `users_tasks` where `users_tasks`.`task_id` in(?, ?, ?)", $schema->getLastQuery()); 
    }
    /**
     * @test
     */
    public function testWhereIn01() {
        $schema = new MySQLTestSchema();
        $schema->table('users_tasks')->select()->whereNotIn('task-id', [7,"9","100"]);
        $this->assertEquals("select * from `users_tasks` where `users_tasks`.`task_id` not in(?, ?, ?)", $schema->getLastQuery()); 
        $this->assertEquals([
            'bind' => 'iii',
            'values' => [
                7,
                9,
                100
            ]
        ], $schema->getQueryGenerator()->getBindings());
    }
    /**
     * @test
     */
    public function testWhereIn02() {
        $schema = new MySQLTestSchema();
        $schema->table('users')->select()->whereNotIn('first-name', [7,"9","100"]);
        $this->assertEquals("select * from `users` where `users`.`first_name` not in(?, ?, ?)", $schema->getLastQuery()); 
        $this->assertEquals([
            'bind' => 'sss',
            'values' => [
                7,
                '9',
                '100'
            ]
        ], $schema->getQueryGenerator()->getBindings());
    }
    /**
     * @test
     */
    public function testWhereIn03() {
        $schema = new MySQLTestSchema();
        $schema->table('users')->select()->whereNotIn('first-naiome', [7,"9","100"]);
        $this->assertEquals("select * from `users` where `users`.`first_naiome` not in(?, ?, ?)", $schema->getLastQuery());
        $this->assertEquals([
            'bind' => 'sss',
            'values' => [
                7,
                9,
                100
            ]
        ], $schema->getQueryGenerator()->getBindings());
    }
    /**
     * @test
     */
    public function testWhereBetween00() {
        $schema = new MySQLTestSchema();
        $schema->table('users_tasks')->select()->whereBetween('task-id', 0, 33);
        $this->assertEquals('select * from `users_tasks` where (`users_tasks`.`task_id` between ? and ?)', $schema->getLastQuery());
        $schema->andWhere('user-id', 88);
        $this->assertEquals('select * from `users_tasks` where (`users_tasks`.`task_id` between ? and ?) and `users_tasks`.`user_id` = ?', $schema->getLastQuery());
        $this->assertEquals([
            'bind' => 'iii',
            'values' => [
                0,
                33,
                88
            ]
        ], $schema->getQueryGenerator()->getBindings());
    }
    /**
     * @test
     */
    public function testWhereBetween01() {
        $schema = new MySQLTestSchema();
        $schema->table('users_tasks')->select()->whereBetween('task-idx', 0, 33);
        $this->assertEquals("select * from `users_tasks` where (`users_tasks`.`task_idx` between ? and ?)", $schema->getLastQuery());
        $this->assertEquals([
            'bind' => 'ii',
            'values' => [
                0, 
                33
            ]
        ], $schema->getQueryGenerator()->getBindings());
        $schema->resetBinding();
        $schema->table('users_tasks')->getTable()->getColByKey('task-idx')->setDatatype('varchar');
        $schema->table('users_tasks')->select()->whereBetween('task-idx', 0, 33);
        $this->assertEquals("select * from `users_tasks` where (`users_tasks`.`task_idx` between ? and ?)", $schema->getLastQuery());
        $this->assertEquals([
            'bind' => 'ss',
            'values' => [
                0,
                33
            ]
        ], $schema->getQueryGenerator()->getBindings());
    }
    /**
     * @test
     */
    public function testWhereBetween02() {
        $schema = new MySQLTestSchema();
        $this->expectException(DatabaseException::class);
        $schema->table('users_tasks')->whereBetween('task-idx', 0, 33);
    }
    /**
     * @test
     */
    public function testWhereBetween03() {
        $schema = new MySQLTestSchema();
        $schema->table('users_tasks')->select()->whereNotBetween('task-id', 0, 33);
        $this->assertEquals('select * from `users_tasks` where (`users_tasks`.`task_id` not between ? and ?)', $schema->getLastQuery());
        $schema->getQueryGenerator()->andWhere('user-id', 88);
        $this->assertEquals('select * from `users_tasks` where (`users_tasks`.`task_id` not between ? and ?) and `users_tasks`.`user_id` = ?', $schema->getLastQuery());
        $this->assertEquals([
            'bind' => 'iii',
            'values' => [
                0,
                33,
                88,
            ]
        ], $schema->getQueryGenerator()->getBindings());
    }
    /**
     * @test
     */
    public function testWhereBetween04() {
        $schema = new MySQLTestSchema();
        $schema->table('users_tasks')->select()->whereNotBetween('task-id', 0, 33);
        $this->assertEquals('select * from `users_tasks` where (`users_tasks`.`task_id` not between ? and ?)', $schema->getLastQuery());
        $this->assertEquals([
            'bind' => 'ii',
            'values' => [
                0,
                33
            ]
        ], $schema->getQueryGenerator()->getBindings());
    }
    /**
     * @test
     */
    public function testWhereBetween05() {
        $schema = new MySQLTestSchema();
        $schema->table('users_tasks')->select()->whereBetween('last-updated', '2020-01-02 01:30:00', '2020-02-15');
        $this->assertEquals('select * from `users_tasks` where (`users_tasks`.`last_updated` between ? and ?)', $schema->getLastQuery());
    }
    /**
     * @test
     */
    public function testDropFk00() {
        $schema = new MySQLTestSchema();
        $schema->table('users_tasks')->dropForeignKey('user_task_fk');
        $this->assertEquals("alter table `users_tasks` drop foreign key user_task_fk;", $schema->getLastQuery());
    }
    public function testRawQuery00() {
        $this->expectException(DatabaseException::class);
        $this->expectExceptionMessage("1146 - Table 'testing_db.userss_tasks' doesn't exist");
        $schema = new MySQLTestSchema();
        $schema->setQuery('select * from userss_tasks;');
        $this->assertEquals('select * from userss_tasks;', $schema->getLastQuery());
        $schema->execute();
        $this->assertTrue(true);
    }
    /**
     * @test
     */
    public function testTableNotExist00() {
        $schema = new MySQLTestSchema();
        $this->assertFalse($schema->hasTable('userss_tasks'));
        $schema->table('userss_tasks');
        $this->assertTrue($schema->hasTable('userss_tasks'));
        return $schema;
    }
    /**
     * @test
     * @depends testTableNotExist00
     */
    public function testTableNotExist01(MySQLTestSchema $s) {
        $s->select(['id']);
        $this->assertEquals('select `userss_tasks`.`id` from `userss_tasks`', $s->getLastQuery());
        return $s;
    }
    /**
     * @test
     * @depends testInsert04
     */
    public function testTransaction00() {
        $schema = new MySQLTestSchema();
        $schema->transaction(function (Database $db) {
            $q = $db->table('users');
            $q->insert([
                'first-name' => 'Ibrahim',
                'last-name' => 'BinAlshikh',
                'age' => 30
            ])->execute();
            $userId = $q->table('users')->selectMax('id')->execute()->getRows()[0]['max'];
            $q->table('users_privileges')
                ->insert([
                    'id' => $userId,
                    'can-edit-price' => 1,
                    'can-change-username' => 0,
                    'can-do-anything' => 1
                ])->execute();
            $q->table('users_tasks')
                ->insert([
                    'user-id' => $userId,
                    'details' => 'This task is about testing if transactions work as intended.',
                ])->execute();
            return true;
        });
        $userId = $schema->table('users')->selectMax('id')->execute()->getRows()[0]['max'];
        $privileges = $schema->table('users_privileges')->select()->where('id', $userId)->execute()->getRows()[0];
        $this->assertEquals([
            'id' => $userId,
            'can_edit_price' => 1,
            'can_change_username' => 0,
            'can_do_anything' => 1
        ], $privileges);
        $tasks = $schema->table('users_tasks')->select()->where('user_id', $userId)->execute()->getRows();
        $this->assertEquals([
            [
                'task_id' => 1,
                'user_id' => $userId,
                'created_on' => date('Y-m-d H:i:s'),
                'last_updated' => null,
                'is_finished' => 0,
                'details' => 'This task is about testing if transactions work as intended.',
            ]
        ], $tasks);
        return intval($userId);
    }
    /**
     * @test
     * @depends testTransaction00
     */
    public function testTransaction01(int $userId) {
        $schema = new MySQLTestSchema();
        $schema->transaction(function (Database $db, int $uId) {
            $db->table('users_tasks')
                ->insert([
                    'user-id' => $uId,
                    'details' => 'This another task.',
                ])->execute();
            return true;
        }, [$userId]);
        $tasks = $schema->table('users_tasks')->select()->where('user_id', $userId)->execute()->getRows();
        $this->assertEquals([
            [
                'task_id' => 1,
                'user_id' => $userId,
                'created_on' => date('Y-m-d H:i:s'),
                'last_updated' => null,
                'is_finished' => 0,
                'details' => 'This task is about testing if transactions work as intended.',
            ], [
                'task_id' => 2,
                'user_id' => $userId,
                'created_on' => date('Y-m-d H:i:s'),
                'last_updated' => null,
                'is_finished' => 0,
                'details' => 'This another task.',
            ]
        ], $tasks);
        $schema->transaction(function (Database $db, int $uId) {
            $db->table('users_tasks')
                ->insert([
                    'user-id' => $uId,
                    'details' => 'This third task.',
                ])->execute();
            return false;
        }, [$userId]);
        $tasks = $schema->table('users_tasks')->select()->where('user_id', $userId)->execute()->getRows();
        $this->assertEquals([
            [
                'task_id' => 1,
                'user_id' => $userId,
                'created_on' => date('Y-m-d H:i:s'),
                'last_updated' => null,
                'is_finished' => 0,
                'details' => 'This task is about testing if transactions work as intended.',
            ], [
                'task_id' => 2,
                'user_id' => $userId,
                'created_on' => date('Y-m-d H:i:s'),
                'last_updated' => null,
                'is_finished' => 0,
                'details' => 'This another task.',
            ]
        ], $tasks);
        return $userId;
    }
    /**
     * @test
     * @depends testTransaction01
     */
    public function testTransaction02(int $userId) {
        $schema = new MySQLTestSchema();
        try {
            $schema->transaction(function (Database $db, $uId) {
                $db->table('users')->update([
                    'first-name' => 'Ali',
                    'age' => 32
                ])->where('id', $uId)->execute();

                $db->transaction(function (Database $db) {
                    $db->table('users')->insert([
                        'first-name' => 'Ibrahim',
                        'last-name' => 'BinAlshikh',
                    ])->execute();
                });
            }, [$userId]);
        } catch (DatabaseException $ex) {
            $this->assertEquals("1364 - Field 'age' doesn't have a default value", $ex->getMessage());
            $this->assertEquals(1364, $ex->getCode());
            $user = $schema->table('users')->select()->where('id', $userId)->execute()->getRows()[0];
            $this->assertEquals([
                'id' => $userId,
                'first_name' => 'Ali',
                'last_name' => 'BinAlshikh',
                'age' => 32
            ], $user);
        }
        
    }
    /**
     * @test
     * @depends testTableNotExist01
     */
    public function testTableNotExist0(MySQLTestSchema $s) {
        $s->getQueryGenerator()->groupBy('username')->orderBy([
            'email'
        ]);
        $this->assertEquals('select `userss_tasks`.`id` from `userss_tasks` group by `userss_tasks`.`username` order by `userss_tasks`.`email`', $s->getLastQuery());
    }
    /**
     * @test
     */
    public function testLeft00() {
        $this->expectException(DatabaseException::class);
        $schema = new MySQLTestSchema();
        $schema->table('userss_tasks')->whereLeft('details', 3, '=', 'hello');
    }
    /**
     * @test
     */
    public function testLeft01() {
        $schema = new MySQLTestSchema();
        $schema->table('users_tasks')->select()->whereLeft('details', 2, '=', 'hello');
        $this->assertEquals("select * from `users_tasks` where left(`users_tasks`.`details`, 2) = ?", $schema->getLastQuery());
    }
    /**
     * @test
     */
    public function testLeft02() {
        $schema = new MySQLTestSchema();
        $schema->table('users_tasks')->select()->whereLeft('details', 8, '*', 'good');
        $this->assertEquals("select * from `users_tasks` where left(`users_tasks`.`details`, 8) = ?", $schema->getLastQuery());
    }
    /**
     * @test
     */
    public function testLeft03() {
        $schema = new MySQLTestSchema();
        $schema->table('users_tasks')->select()->whereLeft('details', 8, '!=', 'good');
        $this->assertEquals("select * from `users_tasks` where left(`users_tasks`.`details`, 8) != ?", $schema->getLastQuery());
    }
    /**
     * @test
     */
    public function testLeft04() {
        $schema = new MySQLTestSchema();
        $schema->table('users_tasks')->select()->whereLeft('details', 8, 'in', 'good');
        $this->assertEquals("select * from `users_tasks` where left(`users_tasks`.`details`, 8) in(?)", $schema->getLastQuery());
    }
    /**
     * @test
     */
    public function testLeft05() {
        $schema = new MySQLTestSchema();
        $q = $schema->getQueryGenerator();
        $q->table('users_tasks')->select()
                ->whereLeft('details', 8, 'not in', ['good']);
        $this->assertEquals("select * from `users_tasks` where left(`users_tasks`.`details`, 8) not in(?)", $schema->getLastQuery());
        $q->andWhere('user-id', 9);
        $this->assertEquals("select * from `users_tasks` where left(`users_tasks`.`details`, 8) not in(?) and `users_tasks`.`user_id` = ?", $schema->getLastQuery());
        $this->assertEquals([
            'bind' => 'si',
            'values' => [
                'good',
                9
            ]
        ], $schema->getQueryGenerator()->getBindings());
    }
    /**
     * @test
     */
    public function testLeft06() {
        $schema = new MySQLTestSchema();
        $schema->table('users_tasks')->select()->whereLeft('details', 8, 'not in', ['good', 'bad']);
        $this->assertEquals("select * from `users_tasks` where left(`users_tasks`.`details`, 8) not in(?, ?)", $schema->getLastQuery());
    }
    /**
     * @test
     */
    public function testLeft07() {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The value must be of type string since the condition is \'=\'.');
        $schema = new MySQLTestSchema();
        $schema->table('users_tasks')->select()->whereLeft('details', 8, '=', ['good', 'bad']);
    }
    /**
     * @test
     */
    public function testLeft08() {
        $schema = new MySQLTestSchema();
        $q = $schema->getQueryGenerator();
        $q->table('users_tasks')->select()->whereBetween('created-on', '2020-01-01', '2020-04-01');
        $this->assertEquals('select * from `users_tasks` where (`users_tasks`.`created_on` between ? and ?)', $q->getQuery());
        $this->assertEquals([
            'bind' => 'ss',
            'values' => [
                '2020-01-01',
                '2020-04-01'
            ]
        ], $schema->getQueryGenerator()->getBindings());
        $q->whereLeft('details', 5, '=', 'ok ok', 'or');
        $this->assertEquals('select * from `users_tasks` where (`users_tasks`.`created_on` between ? and ?) or left(`users_tasks`.`details`, 5) = ?', $q->getQuery());
        $this->assertEquals([
            'bind' => 'sss',
            'values' => [
                '2020-01-01',
                '2020-04-01',
                'ok ok'
            ]
        ], $schema->getQueryGenerator()->getBindings());
    }
    /**
     * @test
     */
    public function testRight01() {
        $schema = new MySQLTestSchema();
        $schema->table('users_tasks')->select()->whereRight('details', 2, '=', 'hello');
        $this->assertEquals("select * from `users_tasks` where right(`users_tasks`.`details`, 2) = ?", $schema->getLastQuery());
        $this->assertEquals([
            'bind' => 's',
            'values' => [
                'hello'
            ]
        ], $schema->getQueryGenerator()->getBindings());
    }
    /**
     * @test
     */
    public function testRight02() {
        $schema = new MySQLTestSchema();
        $schema->table('users_tasks')->select()->whereRight('details', 8, '*', 'good');
        $this->assertEquals("select * from `users_tasks` where right(`users_tasks`.`details`, 8) = ?", $schema->getLastQuery());
        $this->assertEquals([
            'bind' => 's',
            'values' => [
                'good'
            ]
        ], $schema->getQueryGenerator()->getBindings());
    }
    /**
     * @test
     */
    public function testRight03() {
        $schema = new MySQLTestSchema();
        $schema->table('users_tasks')->select()->whereRight('details', 8, '!=', 'good');
        $this->assertEquals("select * from `users_tasks` where right(`users_tasks`.`details`, 8) != ?", $schema->getLastQuery());
    }
    /**
     * @test
     */
    public function testRight04() {
        $schema = new MySQLTestSchema();
        $schema->table('users_tasks')->select()->whereRight('details', 8, 'in', 'good');
        $this->assertEquals("select * from `users_tasks` where right(`users_tasks`.`details`, 8) in(?)", $schema->getLastQuery());
    }
    /**
     * @test
     */
    public function testRight05() {
        $schema = new MySQLTestSchema();
        $schema->table('users_tasks')->select()->whereRight('details', 8, 'not in', ['good']);
        $this->assertEquals("select * from `users_tasks` where right(`users_tasks`.`details`, 8) not in(?)", $schema->getLastQuery());
        $this->assertEquals([
            'bind' => 's',
            'values' => [
                'good'
            ]
        ], $schema->getQueryGenerator()->getBindings());
    }
    /**
     * @test
     */
    public function testRight06() {
        $schema = new MySQLTestSchema();
        $schema->table('users_tasks')->select()->whereRight('details', 8, 'not in', ['good', 'bad']);
        $this->assertEquals("select * from `users_tasks` where right(`users_tasks`.`details`, 8) not in(?, ?)", $schema->getLastQuery());
        $this->assertEquals([
            'bind' => 'ss',
            'values' => [
                'good',
                'bad'
            ]
        ], $schema->getQueryGenerator()->getBindings());
    }
    /**
     * @test
     */
    public function testRight07() {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The value must be of type string since the condition is \'!=\'.');
        $schema = new MySQLTestSchema();
        $schema->table('users_tasks')->select()->whereLeft('details', 8, '!=', ['good', 'bad']);
    }
    /**
     * @test
     */
    public function testAggregate00() {
        $schema = new MySQLTestSchema();
        $q = $schema->getQueryGenerator();
        $q->table('users')->select([
            'id' => [
                'aggregate' => 'max'
            ]
        ]);
        $this->assertEquals('select max(`users`.`id`) from `users`', $q->getQuery());
    }
    /**
     * @test
     */
    public function testAggregate01() {
        $schema = new MySQLTestSchema();
        $q = $schema->getQueryGenerator();
        $q->table('users')->select([
            'id' => [
                'aggregate' => 'avg',
                'as' => 'id_avg'
            ]
        ]);
        $this->assertEquals('select avg(`users`.`id`) as `id_avg` from `users`', $q->getQuery());
    }
    /**
     * @test
     */
    public function testAggregate02() {
        $schema = new MySQLTestSchema();
        $q = $schema->getQueryGenerator();
        $q->table('users')->selectMax('id');
        $this->assertEquals('select max(`users`.`id`) as `max` from `users`', $q->getQuery());
    }
    /**
     * @test
     */
    public function testAggregate03() {
        $schema = new MySQLTestSchema();
        $q = $schema->getQueryGenerator();
        $q->table('users')->selectMin('id');
        $this->assertEquals('select min(`users`.`id`) as `min` from `users`', $q->getQuery());
    }
    /**
     * @test
     */
    public function testAggregate04() {
        $schema = new MySQLTestSchema();
        $q = $schema->getQueryGenerator();
        $q->table('users')->selectAvg('id');
        $this->assertEquals('select avg(`users`.`id`) as `avg` from `users`', $q->getQuery());
    }
    /**
     * @test
     */
    public function testAggregate05() {
        $schema = new MySQLTestSchema();
        $q = $schema->getQueryGenerator();
        $q->table('users')->selectCount();
        $this->assertEquals('select count(*) as count from `users`', $q->getQuery());
    }
    /**
     * @test
     */
    public function testAggregate06() {
        $schema = new MySQLTestSchema();
        $q = $schema->getQueryGenerator();
        $q->table('users')->selectCount('id');
        $this->assertEquals('select count(`users`.`id`) as `count` from `users`', $q->getQuery());
    }
}
