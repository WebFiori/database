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
use webfiori\database\tests\MySQLTestSchema;
use webfiori\database\DatabaseException;
use webfiori\database\mysql\MySQLConnection;
use webfiori\database\ConnectionInfo;
use super\entity\UserEntity;

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
    public function selectTest000() {
        $schema = new MySQLTestSchema();
        $bulder = $schema->table('users')->select();
        $this->assertEquals('select * from `users`', $schema->getLastQuery());
        $bulder->select(['id','first-name','last-name']);
        $this->assertEquals('select `users`.`id`, `users`.`first_name`, `users`.`last_name` from `users`', $schema->getLastQuery());
        $bulder->select(['id','first-name'=>'f_name','last-name'=>'l_name']);
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
        $bulder = $schema->table('users')->select()->where('id', '=', 66);
        $this->assertEquals('select * from `users` where `users`.`id` = 66', $schema->getLastQuery());
        $bulder->groupBy('first-name');
        $this->assertEquals('select * from `users` where `users`.`id` = 66 group by `users`.`first_name`', $schema->getLastQuery());
        $bulder->groupBy(['first-name','last-name']);
        $this->assertEquals('select * from `users` where `users`.`id` = 66 group by `users`.`first_name`, `users`.`last_name`', $schema->getLastQuery());
        $bulder->orderBy(['last-name'=>'a']);
        $this->assertEquals('select * from `users` where `users`.`id` = 66 group by `users`.`first_name`, `users`.`last_name` order by `users`.`last_name` asc', $schema->getLastQuery());
    }
    /**
     * @test
     */
    public function testSelectWithWhere001() {
        $schema = new MySQLTestSchema();
        $schema->table('users')->select()->where(
            $schema->where('id', '=', 7)
        );
        $this->assertEquals('select * from `users` where `users`.`id` = 7', $schema->getLastQuery());
        $schema->orWhere('first-name', '=', 'Ibrahim');
        $this->assertEquals('select * from `users` where `users`.`id` = 7 or `users`.`first_name` = \'Ibrahim\'', $schema->getLastQuery());
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
                $q->where('id', '=', 7)
            )
        );
        $this->assertEquals('select * from `users` where `users`.`id` = 7', $schema->getLastQuery());
        $schema->orWhere('first-name', '=', 'Ibrahim');
        $this->assertEquals('select * from `users` where `users`.`id` = 7 or `users`.`first_name` = \'Ibrahim\'', $schema->getLastQuery());
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
            $q->where('id', '=', 7)
            ->orWhere('id', '=', 8)//This will create one big cond
        //4 
        )->orWhere(
                //3 Cond f_name = Ibr
            $q->where('first-name', '=', 'Ibrahim')
            ->andWhere('last-name', '=', 'BinAlshikh')//This will create one big cond
        );
        '((id = 7) and f_n = ibrahim)';
        $this->assertEquals('select * from `users` '
                . 'where ((`users`.`id` = 7 or `users`.`id` = 8) '
                . 'or (`users`.`first_name` = \'Ibrahim\' and `users`.`last_name` = \'BinAlshikh\'))', $schema->getLastQuery());
    }
    /**
     * @test
     */
    public function testSelectWithWhere004() {
        $schema = new MySQLTestSchema();
        $q = $schema->table('users')->select();
        
        $q->where(
            $q->where('id', '=', 7)
        )->where('id', '=', 8)
         ->orWhere('id', '=', 88);
        
        $this->assertEquals('select * from `users` where `users`.`id` = 7 and `users`.`id` = 8 or `users`.`id` = 88', $schema->getLastQuery());
        $schema->orWhere('first-name', '=', 'Ibrahim');
        
        $this->assertEquals('select * from `users` where `users`.`id` = 7 '
                . 'and `users`.`id` = 8 or `users`.`id` = 88 or `users`.`first_name` = \'Ibrahim\'', $schema->getLastQuery());
    }
    /**
     * @test
     */
    public function testSelectWithWhere002() {
        $schema = new MySQLTestSchema();
        $q = $schema->table('users')->select();
        
        $q->where(//This where will create an expression of sub query.
            $q->where('id', '=', 7)
        )->orWhere(
            $q->where('first-name', '=', 'Ibrahim')
        )->andWhere(
            $q->where('last-name', '=', 'BinAlshikh')
        );
        // Expr(Cond) Cond Expr
        // (id = 7) and f_n = Ibrahim
        $this->assertEquals('select * from `users` '
                . 'where `users`.`id` = 7 '
                . 'or `users`.`first_name` = \'Ibrahim\' and `users`.`last_name` = \'BinAlshikh\'', $schema->getLastQuery());
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
                $q->where('id', '=', 7)
            //3 Expr (id = 7) and id = 8
            )->where('id', '=', 8)
        );
        // Expr(Expr(Cond) Cond)
        $this->assertEquals('select * from `users` where `users`.`id` = 7 and `users`.`id` = 8', $schema->getLastQuery());
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
                $q->where('id', '=', 7)
            //3 Expr (id = 7) and id = 8
            )->where('id', '=', 8)
             ->where('id', '=', 100)
             ->where('first-name', '=', 44)
        );
        $this->assertEquals('select * from `users` where (`users`.`id` = 7 and (`users`.`id` = 8 and `users`.`id` = 100 and `users`.`first_name` = \'44\'))', $schema->getLastQuery());
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
                $q->where('id', '=', 7)
            )->where(
                $q->where('id', '=', 8)
            )
        );
        $this->assertEquals('select * from `users` where `users`.`id` = 7 and `users`.`id` = 8', $schema->getLastQuery());
        $schema->orWhere('first-name', '=', 'Ibrahim');
        $this->assertEquals('select * from `users` where `users`.`id` = 7 and `users`.`id` = 8 or `users`.`first_name` = \'Ibrahim\'', $schema->getLastQuery());
    }
    /**
     * @test
     */
    public function testSelectWithWhere006() {
        $schema = new MySQLTestSchema();
        $q = $schema->table('users')->select();
        
        $q->where('id', '=', 2)
                ->where('id', '!=', 9, 'or')
                ->orWhere('id', '=', 10)
                ->andWhere('id', '=', 30)
                ->andWhere('first-name', '!=', 'Ibr');
        $this->assertEquals('select * from `users` where '
                . '`users`.`id` = 2 or '
                . '`users`.`id` != 9 or '
                . '`users`.`id` = 10 and '
                . '`users`.`id` = 30 and '
                . '`users`.`first_name` != \'Ibr\'', $schema->getLastQuery());
    }
    /**
     * @test
     */
    public function testSelectWithWhere007() {
        $schema = new MySQLTestSchema();
        $q = $schema->table('users')->select();
        
        $q->where($q->where('id', '=', 2)
                ->where('id', '!=', 9, 'or')
                ->orWhere('id', '=', 10)
                ->andWhere('id', '=', 30)
                ->andWhere('first-name', '!=', 'Ibr'));
        $this->assertEquals('select * from `users` where ('
                . '`users`.`id` = 2 or '
                . '`users`.`id` != 9 or '
                . '`users`.`id` = 10 and '
                . '`users`.`id` = 30 and '
                . '`users`.`first_name` != \'Ibr\')', $schema->getLastQuery());
    }
    /**
     * @test
     */
    public function testSelectWithWhere008() {
        $schema = new MySQLTestSchema();
        $q = $schema->table('users')->select();
        
        $q->where($q->where($q->where('id', '=', 2)
                ->where('id', '!=', 9, 'or')
                ->orWhere('id', '=', 10)
                ->andWhere('id', '=', 30)
                ->andWhere('first-name', '!=', 'Ibr')));
        $this->assertEquals('select * from `users` where ('
                . '`users`.`id` = 2 or '
                . '`users`.`id` != 9 or '
                . '`users`.`id` = 10 and '
                . '`users`.`id` = 30 and '
                . '`users`.`first_name` != \'Ibr\')', $schema->getLastQuery());
    }
    
    public function testDelete00() {
        $schema = new MySQLTestSchema();
        $schema->table('users')
                ->delete()
                ->where('id', '=', 88);
        $this->assertEquals("delete from `users` where `users`.`id` = 88", $schema->getLastQuery());
        $schema->where('id', '=', 55);
        $this->assertEquals("delete from `users` where `users`.`id` = 88 and `users`.`id` = 55", $schema->getLastQuery());
        $schema->orWhere('id', '!=', '8');
        $this->assertEquals("delete from `users` where `users`.`id` = 88 and `users`.`id` = 55 or `users`.`id` != 8", $schema->getLastQuery());
    }
    public function testDelete04() {
        $schema = new MySQLTestSchema();
        $q = $schema->table('users');
        
               $q ->delete()
        ->orWhere(
                $q->orWhere('first-name', '=', 'Ibrahim')
                ->andWhere('last-name', '=', 'BinAlshikh'));
        $this->assertEquals("delete from `users` where (`users`.`first_name` = 'Ibrahim' and `users`.`last_name` = 'BinAlshikh')", $schema->getLastQuery());
    }
    public function testDelete03() {
        $schema = new MySQLTestSchema();
        $q = $schema->table('users')
                ->delete()
                ->where('id', '=', 88);
        $this->assertEquals("delete from `users` where `users`.`id` = 88", $schema->getLastQuery());
        $q->where('id', '=', 55);
        $this->assertEquals("delete from `users` where `users`.`id` = 88 and `users`.`id` = 55", $schema->getLastQuery());
    }
    /**
     * @test
     */
    public function testDelete01() {
        $this->expectException(DatabaseException::class);
        $schema = new MySQLTestSchema();
        $schema->table('users')
                ->delete()
                ->where('x-id', '=', 88);
    }
    /**
     * @test
     */
    public function unionTest00() {
        $schema = new MySQLTestSchema();
        $schema->table('users')
                ->select(['id' => 'user_id', 'first-name'])
                ->union($schema->table('users_privileges')->select());
        $this->assertEquals("select `users`.`id` as `user_id`, `users`.`first_name` from `users`"
                . "\nunion\n"
                . "select * from `users_privileges`", $schema->getLastQuery());
    }
    /**
     * @test
     */
    public function unionTest01() {
        $schema = new MySQLTestSchema();
        $schema->table('users')
                ->select(['id' => 'user_id', 'first-name'])
                ->where('id', '!=', 44)
                ->union($schema->table('users_privileges')->select());
        $this->assertEquals("select `users`.`id` as `user_id`, `users`.`first_name` from `users` where `users`.`id` != 44"
                . "\nunion\n"
                . "select * from `users_privileges`", $schema->getLastQuery());
    }
    /**
     * @test
     */
    public function unionTest02() {
        $schema = new MySQLTestSchema();
        $q = $schema->table('users');
                $q->select(['id' => 'user_id', 'first-name'])
                ->where('id', '!=', 44)
                ->union($q->table('users_privileges')->select())
                ->union($q->table('users_tasks')->select(), true);
        $this->assertEquals("select `users`.`id` as `user_id`, `users`.`first_name` from `users` where `users`.`id` != 44"
                . "\nunion\n"
                . "select * from `users_privileges`"
                . "\nunion all\n"
                . "select * from `users_tasks`", $schema->getLastQuery());
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
        $this->assertEquals("insert into `users` (`id`, `first_name`, `last_name`) values (8, 'Ibrahim', 'BinAlshikh');", $schema->getLastQuery());
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
        $this->assertEquals("insert into `users`\n(`id`, `first_name`, `last_name`)\nvalues\n"
                . "(8, 'Ibrahim', 'BinAlshikh'),\n"
                . "(9, 'Web', 'DB');", $schema->getLastQuery());
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
                . "values (6, 'OK task', '".date('Y-m-d H:i:s')."', b'0');", $schema->getLastQuery());
        $q->insert([
            'user-id' => 6,
            'details' => 'OK task',
            'created-on' => '2020-10-16 00:00:00',
            'is-finished' => true
        ]);
        $this->assertEquals("insert into `users_tasks` "
                . "(`user_id`, `details`, `created_on`, `is_finished`) "
                . "values (6, 'OK task', '2020-10-16 00:00:00', b'1');", $schema->getLastQuery());
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
        $this->assertEquals("update `users_tasks` set `details` = 'OKKKKKKKk', set `last_updated` = '$date'", $schema->getLastQuery());
    }
    /**
     * @test
     */
    public function testUpdate01() {
        $schema = new MySQLTestSchema();
        $q = $schema->table('users_tasks');
        
        $q->update([
            'details' => 'OKKKKKKKk'
        ])->where('task-id', '=', 77);
        $date = date('Y-m-d H:i:s');
        $this->assertEquals("update `users_tasks` set `details` = 'OKKKKKKKk', set `last_updated` = '$date' where `users_tasks`.`task_id` = 77", $schema->getLastQuery());
        $q->andWhere('user-id', '=', 6);
        $this->assertEquals("update `users_tasks` set `details` = 'OKKKKKKKk', set `last_updated` = '$date' "
                . "where `users_tasks`.`task_id` = 77 and `users_tasks`.`user_id` = 6", $schema->getLastQuery());
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
        $this->assertEquals("alter table `users_tasks` change column `details` varchar(1500) not null collate utf8mb4_unicode_520_ci;", $schema->getLastQuery());
    }
    /**
     * @test
     */
    public function testModifyCol01() {
        $schema = new MySQLTestSchema();
        $q = $schema->table('users_tasks');
        $q->modifyCol('details', 'first');
        $this->assertEquals("alter table `users_tasks` change column `details` varchar(1500) not null collate utf8mb4_unicode_520_ci first;", $schema->getLastQuery());
    }
    /**
     * @test
     */
    public function testModifyCol02() {
        $schema = new MySQLTestSchema();
        $q = $schema->table('users_tasks');
        $q->modifyCol('details', 'user-id');
        $this->assertEquals("alter table `users_tasks` change column `details` varchar(1500) not null collate utf8mb4_unicode_520_ci after `user_id`;", $schema->getLastQuery());
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
        $connInfo = new ConnectionInfo('mysql','root', '123456', 'testing_db');
        $conn = new MySQLConnection($connInfo);
        $schema = new MySQLTestSchema();
        $schema->setConnection($conn);
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
        $connInfo = new ConnectionInfo('mysql', 'root', '12345', 'testing_db');
        $conn = new MySQLConnection($connInfo);
        $schema = new MySQLTestSchema();
        $schema->setConnection($conn);
        return $schema;
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
        $schema->select()->execute();
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
        $schema->delete()->where('id', '=', $row['id']);
        $this->assertEquals('delete from `users` where `users`.`id` = '.$row['id'], $schema->getLastQuery());
        $schema->execute();
        $schema->select()->execute();
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
        $schema->insert([
            'cols' => [
                'id','first-name','last-name','age'
            ],
            'values' => [
                [100,'Ali','Hassan',16],
                [101,'Dabi','Jona',19]
            ]
        ]);
        $this->assertEquals("insert into `users`\n(`id`, `first_name`, `last_name`, `age`)\nvalues\n"
                . "(100, 'Ali', 'Hassan', 16),\n"
                . "(101, 'Dabi', 'Jona', 19);", $schema->getLastQuery());
        $schema->execute();
        $schema->select()->execute();
        $resultSet = $schema->getLastResultSet();
        $this->assertEquals(2, $resultSet->getRowsCount());
        $this->assertEquals(2, $resultSet->getMappedRowsCount());
        
        $this->assertEquals([
            ['id'=>100,'first_name'=>'Ali','last_name'=>'Hassan','age'=>16],
            ['id'=>101,'first_name'=>'Dabi','last_name'=>'Jona','age'=>19]
        ], $resultSet->getRows());
        
        $this->assertEquals([
            ['id'=>100,'first_name'=>'Ali','last_name'=>'Hassan','age'=>16],
            ['id'=>101,'first_name'=>'Dabi','last_name'=>'Jona','age'=>19]
        ], $resultSet->getMappedRows());
        $schema->insert([
            'cols' => [
                'id','first-name','last-name','age'
            ],
            'values' => [
                [102,'Jon','Mark',22],
                [103,'Ibrahim','Ali',27]
            ]
        ])->execute();
        $schema->select()->execute();
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
        $resultSet->setMappingFunction(function($data) {
            $retVal = [];
            foreach ($data as $record) {
                $obj = new UserEntity();
                $obj->setAge($record['age']);
                $obj->setFirstName($record['first_name']);
                $obj->setId($record['id']);
                $obj->setLastName($record['last_name']);
                $retVal[] = $obj;
            }
            return $retVal;
        });
        $data = $resultSet->getMappedRows();
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
        $this->assertEquals('alter table `users` drop `id`;', $schema->getLastQuery());
        $schema->table('users')->dropCol('first-name ');
        $this->assertEquals('alter table `users` drop `first_name`;', $schema->getLastQuery());
    }
    /**
     * @test
     */
    public function testDropCol01() {
        $schema = new MySQLTestSchema();
        $this->expectException(DatabaseException::class);
        $this->expectExceptionMessage('The table `users` has no column with key \'not-exist\'.');
        $schema->table('users')->dropCol('not-exist');
    }
    /**
     * @test
     */
    public function testJoin00() {
        $schema = new MySQLTestSchema();
        $queryBuilder = $schema->getQueryGenerator();
        $queryBuilder->table('users')->join($queryBuilder->table('users_privileges'))->select();
        $this->assertEquals("select * from `users` join `users_privileges`", $schema->getLastQuery());
        $queryBuilder->on('id', 'id')->select();
        $this->assertEquals("select * from `users` join `users_privileges` on(`users`.`id` = `users_privileges`.`id`)", $schema->getLastQuery());
        //$schema->execute();
        
    }
    /**
     * @test
     */
    public function testJoin01() {
        $schema = new MySQLTestSchema();
        $queryBuilder = $schema->getQueryGenerator();
        $queryBuilder->table('users')->join(
                $queryBuilder->table('users_privileges')->select()->where('id', '=', 3)
                )->on('id', 'id')->select();
        $this->assertEquals("select * from `users` join `users_privileges` on(`users`.`id` = `users_privileges`.`id`) "
                . "where `users_privileges`.`id` = 3", $schema->getLastQuery());
    }
    /**
     * @test
     */
    public function testJoin02() {
        $schema = new MySQLTestSchema();
        $queryBuilder = $schema->getQueryGenerator();
        $queryBuilder->table('users')->select()->where('id', '=', 88)->join(
                $queryBuilder->table('users_privileges')
                )->on('id', 'id')->select();
        $this->assertEquals("select * from `users` join `users_privileges` on(`users`.`id` = `users_privileges`.`id`) "
                . "where `users`.`id` = 88", $schema->getLastQuery());
    }
    /**
     * @test
     */
    public function testJoin03() {
        $schema = new MySQLTestSchema();
        $queryBuilder = $schema->getQueryGenerator();
        $queryBuilder->table('users')->join(
                $queryBuilder->table('users_privileges')->select()->where('id', '=', 3)
                )->on('id', 'id')->select();
        $this->assertEquals("select * from `users` join `users_privileges` on(`users`.`id` = `users_privileges`.`id`) "
                . "where `users_privileges`.`id` = 3", $schema->getLastQuery());
    }
    /**
     * @test
     */
    public function testJoin04() {
        $schema = new MySQLTestSchema();
        $queryBuilder = $schema->getQueryGenerator();
        $queryBuilder->table('users')->join(
                $queryBuilder->table('users_privileges')->select()->where('id', '=', 3)
                )->on('id', 'id')->select([
                    'id','first-name','last-name','can-edit-price','can-do-anything'
                ]);
        $this->assertEquals("select `users`.`id`, `users`.`first_name`, "
                . "`users`.`last_name`, `users_privileges`.`can_edit_price`, "
                . "`users_privileges`.`can_do_anything` "
                . "from `users` join `users_privileges` on(`users`.`id` = `users_privileges`.`id`) "
                . "where `users_privileges`.`id` = 3", $schema->getLastQuery());
    }
    /**
     * @test
     */
    public function testJoin05() {
        $schema = new MySQLTestSchema();
        $queryBuilder = $schema->getQueryGenerator();
        $queryBuilder->table('users')->join(
            $queryBuilder->table('users_privileges')
        )->on('id', 'id')->join(
            $queryBuilder->table('users_tasks')
        )->on('id', 'user-id')->select();
        $this->assertEquals("select * from (select * from `users` join `users_privileges` "
                . "on(`users`.`id` = `users_privileges`.`id`)) "
                . "as T1 join `users_tasks` on(`T1`.`id` = `users_tasks`.`user_id`)", $schema->getLastQuery());
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
        $this->assertEquals("select * from ("
                . "select "
                . "`users_privileges`.`can_edit_price`, "
                . "`users_privileges`.`can_change_username` from `users` join `users_privileges` "
                . "on(`users`.`id` = `users_privileges`.`id`)) "
                . "as T1 join `users_tasks` on(`T1`.`id` = `users_tasks`.`user_id`)", $schema->getLastQuery());
    }
    /**
     * @test
     */
    public function testJoin07() {
        $schema = new MySQLTestSchema();
        $queryBuilder = $schema->getQueryGenerator();
        $queryBuilder->table('users')->join(
            $queryBuilder->table('users_privileges')->select(['can-edit-price','can-change-username'])
        )->on('id', 'id')->select(['id']);
        
        $this->assertEquals("select "
                . "`users`.`id`, "
                . "`users_privileges`.`can_edit_price`, "
                . "`users_privileges`.`can_change_username` "
                . "from `users` join `users_privileges` "
                . "on(`users`.`id` = `users_privileges`.`id`)", $schema->getLastQuery());
        
        $queryBuilder->join(
            $queryBuilder->table('users_tasks')->select(['task-id', 'created-on' => 'created'])
        )->on('id', 'user-id')->select(['id']);
        $this->assertEquals("select "
                . "`T1`.`id`, "
                . "`users_tasks`.`task_id`, "
                . "`users_tasks`.`created_on` as `created` "
                . "from ("
                . "select "
                . "`users`.`id`, "
                . "`users_privileges`.`can_edit_price`, "
                . "`users_privileges`.`can_change_username` "
                . "from `users` join `users_privileges` "
                . "on(`users`.`id` = `users_privileges`.`id`)) "
                . "as T1 join `users_tasks` on(`T1`.`id` = `users_tasks`.`user_id`)", $schema->getLastQuery());
        
        $queryBuilder->join($queryBuilder->table('profile_pics'))->on('id', 'user-id')->select();
        
        $this->assertEquals("select * from ("
                . "select "
                . "`T1`.`id`, "
                . "`users_tasks`.`task_id`, "
                . "`users_tasks`.`created_on` as `created` "
                . "from ("
                . "select "
                . "`users`.`id`, "
                . "`users_privileges`.`can_edit_price`, "
                . "`users_privileges`.`can_change_username` "
                . "from `users` join `users_privileges` "
                . "on(`users`.`id` = `users_privileges`.`id`)) "
                . "as T1 join `users_tasks` on(`T1`.`id` = `users_tasks`.`user_id`)) as T2 "
                . "join `profile_pics` on(`T2`.`id` = `profile_pics`.`user_id`)", $schema->getLastQuery());
        
        $queryBuilder->join($queryBuilder->table('users'))->on('id', 'id')->select();
        
        $this->assertEquals("select * from (select * from ("
                . "select "
                . "`T1`.`id`, "
                . "`users_tasks`.`task_id`, "
                . "`users_tasks`.`created_on` as `created` "
                . "from ("
                . "select "
                . "`users`.`id`, "
                . "`users_privileges`.`can_edit_price`, "
                . "`users_privileges`.`can_change_username` "
                . "from `users` join `users_privileges` "
                . "on(`users`.`id` = `users_privileges`.`id`)) "
                . "as T1 join `users_tasks` on(`T1`.`id` = `users_tasks`.`user_id`)) as T2 "
                . "join `profile_pics` on(`T2`.`id` = `profile_pics`.`user_id`)) as T3 join `users` on(`T3`.`id` = `users`.`id`)", $schema->getLastQuery());
    }
}
