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

/**
 * Description of MySQLQueryBuilderTest
 *
 * @author Ibrahim
 */
class MySQLQueryBuilderTest extends TestCase {
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
                . "    constraint `user_privilege_fk` foreign key (`id`) references `users` (`id`)\n"
                . ")\n"
                . "engine = InnoDB\n"
                . "default charset = utf8mb4\n"
                . "collate = utf8mb4_unicode_520_ci;", $schema->getLastQuery());
        $schema->execute();
        
        $schema->table('users_tasks')->createTable();
        $date = date('Y-m-d H:i:s');
        $this->assertEquals("create table if not exists `users_tasks` (\n"
                . "    `task_id` int not null unique auto_increment,\n"
                . "    `user_id` int not null comment 'The ID of the user who must perform the activity.',\n"
                . "    `created_on` timestamp not null default now(),\n"
                . "    `last_updated` datetime null,\n"
                . "    `is_finished` bit(1) not null default b'0',\n"
                . "    `details` varchar(1500) not null collate utf8mb4_unicode_520_ci,\n"
                . "    constraint `users_tasks_pk` primary key (`task_id`),\n"
                . "    constraint `user_task_fk` foreign key (`user_id`) references `users` (`id`)\n"
                . ")\n"
                . "comment 'The tasks at which each user can have.'\n"
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
        $schema->table('users')->select();
        $this->assertEquals('select * from `users`', $schema->getLastQuery());
        $schema->select(['id','first-name','last-name']);
        $this->assertEquals('select `id`, `first_name`, `last_name` from `users`', $schema->getLastQuery());
        $schema->select(['id','first-name'=>'f_name','last-name'=>'l_name']);
        $this->assertEquals('select `id`, `first_name` as `f_name`, `last_name` as `l_name` from `users`', $schema->getLastQuery());
    }
    /**
     * @test
     */
    public function testSelectWithWhere000() {
        $schema = new MySQLTestSchema();
        $schema->table('users')->select()->where('id', '=', 66);
        $this->assertEquals('select * from `users` where `id` = 66', $schema->getLastQuery());
    }
    /**
     * @test
     */
    public function testSelectWithWhere001() {
        $schema = new MySQLTestSchema();
        $schema->table('users')->select()->where(
            $schema->where('id', '=', 7)
        );
        $this->assertEquals('select * from `users` where `id` = 7', $schema->getLastQuery());
        $schema->orWhere('first-name', '=', 'Ibrahim');
        $this->assertEquals('select * from `users` where `id` = 7 or `first_name` = \'Ibrahim\'', $schema->getLastQuery());
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
        $this->assertEquals('select * from `users` where `id` = 7', $schema->getLastQuery());
        $schema->orWhere('first-name', '=', 'Ibrahim');
        $this->assertEquals('select * from `users` where `id` = 7 or `first_name` = \'Ibrahim\'', $schema->getLastQuery());
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
                . 'where ((`id` = 7 or `id` = 8) '
                . 'or (`first_name` = \'Ibrahim\' and `last_name` = \'BinAlshikh\'))', $schema->getLastQuery());
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
        
        $this->assertEquals('select * from `users` where `id` = 7 and `id` = 8 or `id` = 88', $schema->getLastQuery());
        $schema->orWhere('first-name', '=', 'Ibrahim');
        
        $this->assertEquals('select * from `users` where `id` = 7 and `id` = 8 or `id` = 88 or `first_name` = \'Ibrahim\'', $schema->getLastQuery());
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
                . 'where `id` = 7 '
                . 'or `first_name` = \'Ibrahim\' and `last_name` = \'BinAlshikh\'', $schema->getLastQuery());
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
        $this->assertEquals('select * from `users` where `id` = 7 and `id` = 8', $schema->getLastQuery());
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
        $this->assertEquals('select * from `users` where (`id` = 7 and (`id` = 8 and `id` = 100 and `first_name` = \'44\'))', $schema->getLastQuery());
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
        $this->assertEquals('select * from `users` where `id` = 7 and `id` = 8', $schema->getLastQuery());
        $schema->orWhere('first-name', '=', 'Ibrahim');
        $this->assertEquals('select * from `users` where `id` = 7 and `id` = 8 or `first_name` = \'Ibrahim\'', $schema->getLastQuery());
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
                . '`id` = 2 or '
                . '`id` != 9 or '
                . '`id` = 10 and '
                . '`id` = 30 and '
                . '`first_name` != \'Ibr\'', $schema->getLastQuery());
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
                . '`id` = 2 or '
                . '`id` != 9 or '
                . '`id` = 10 and '
                . '`id` = 30 and '
                . '`first_name` != \'Ibr\')', $schema->getLastQuery());
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
                . '`id` = 2 or '
                . '`id` != 9 or '
                . '`id` = 10 and '
                . '`id` = 30 and '
                . '`first_name` != \'Ibr\')', $schema->getLastQuery());
    }
    
    public function testDelete00() {
        $schema = new MySQLTestSchema();
        $schema->table('users')
                ->delete()
                ->where('id', '=', 88);
        $this->assertEquals("delete from `users` where `id` = 88", $schema->getLastQuery());
        $schema->where('id', '=', 55);
        $this->assertEquals("delete from `users` where `id` = 88 and `id` = 55", $schema->getLastQuery());
        $schema->orWhere('id', '!=', '8');
        $this->assertEquals("delete from `users` where `id` = 88 and `id` = 55 or `id` != 8", $schema->getLastQuery());
    }
    public function testDelete04() {
        $schema = new MySQLTestSchema();
        $q = $schema->table('users');
        
               $q ->delete()
        ->orWhere(
                $q->orWhere('first-name', '=', 'Ibrahim')
                ->andWhere('last-name', '=', 'BinAlshikh'));
        $this->assertEquals("delete from `users` where (`first_name` = 'Ibrahim' and `last_name` = 'BinAlshikh')", $schema->getLastQuery());
    }
    public function testDelete03() {
        $schema = new MySQLTestSchema();
        $q = $schema->table('users')
                ->delete()
                ->where('id', '=', 88);
        $this->assertEquals("delete from `users` where `id` = 88", $schema->getLastQuery());
        $q->where('id', '=', 55);
        $this->assertEquals("delete from `users` where `id` = 88 and `id` = 55", $schema->getLastQuery());
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
        $this->assertEquals("select `id` as `user_id`, `first_name` from `users`"
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
        $this->assertEquals("select `id` as `user_id`, `first_name` from `users` where `id` != 44"
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
        $this->assertEquals("select `id` as `user_id`, `first_name` from `users` where `id` != 44"
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
            [
                'id' => 8,
                'first-name' => 'Ibrahim',
                'last-name' => 'BinAlshikh'
            ],
            [
                'id' => 9,
                'first-name' => 'Web',
                'last-name' => 'DB'
            ],
        ]);
        $this->assertEquals("insert into `users` (`id`, `first_name`, `last_name`) values (8, 'Ibrahim', 'BinAlshikh');\n"
                . "insert into `users` (`id`, `first_name`, `last_name`) values (9, 'Web', 'DB');", $schema->getLastQuery());
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
        $this->assertEquals("update users_tasks set `details` = 'OKKKKKKKk', set `last_updated` = '$date'", $schema->getLastQuery());
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
        $this->assertEquals("update users_tasks set `details` = 'OKKKKKKKk', set `last_updated` = '$date' where `task_id` = 77", $schema->getLastQuery());
        $q->andWhere('user-id', '=', 6);
        $this->assertEquals("update users_tasks set `details` = 'OKKKKKKKk', set `last_updated` = '$date' "
                . "where `task_id` = 77 and `user_id` = 6", $schema->getLastQuery());
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
    public function testDropTable00($schema) {
        $this->expectException(DatabaseException::class);
        $this->expectExceptionMessage("1146 - Table 'testing_db.users_privileges' doesn't exist");
        $schema->table('users_privileges')->select()->execute();
        $this->assertEquals(0, $schema->getLastResultSet()->getRowsCount());
        $schema->table('users_privileges')->drop()->execute();
        $schema->table('users_privileges')->select()->execute();
    }
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
        $this->assertEquals('delete from `users` where `id` = '.$row['id'], $schema->getLastQuery());
        $schema->execute();
        $schema->select()->execute();
        $this->assertEquals(0, $schema->getLastResultSet()->getRowsCount());
    }
}
