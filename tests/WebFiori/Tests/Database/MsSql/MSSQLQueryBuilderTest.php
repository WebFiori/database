<?php

namespace WebFiori\Tests\Database\MsSql;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use WebFiori\Database\ConnectionInfo;
use WebFiori\Database\Database;
use WebFiori\Database\DatabaseException;
use WebFiori\Database\Expression;
use WebFiori\Database\MsSql\MSSQLConnection;
use WebFiori\Tests\Database\MsSql\MSSQLTestSchema;

/**
 * Description of MSSQLQueryBuilderTest
 *
 * @author Ibrahim
 */
class MSSQLQueryBuilderTest extends TestCase{
    /**
     * @test
     */
    public function testAggregate00() {
        $schema = new MSSQLTestSchema();
        $q = $schema->getQueryGenerator();
        $q->table('users')->select([
            'id' => [
                'aggregate' => 'max'
            ]
        ]);
        $this->assertEquals('select max([users].[id]) from [users]', $q->getQuery());
    }
    /**
     * @test
     */
    public function testAggregate01() {
        $schema = new MSSQLTestSchema();
        $q = $schema->getQueryGenerator();
        $q->table('users')->select([
            'id' => [
                'aggregate' => 'avg',
                'as' => 'id_avg'
            ]
        ]);
        $this->assertEquals('select avg([users].[id]) as [id_avg] from [users]', $q->getQuery());
    }
    /**
     * @test
     */
    public function testAggregate02() {
        $schema = new MSSQLTestSchema();
        $q = $schema->getQueryGenerator();
        $q->table('users')->selectMax('id');
        $this->assertEquals('select max([users].[id]) as [max] from [users]', $q->getQuery());
    }
    /**
     * @test
     */
    public function testAggregate03() {
        $schema = new MSSQLTestSchema();
        $q = $schema->getQueryGenerator();
        $q->table('users')->selectMin('id');
        $this->assertEquals('select min([users].[id]) as [min] from [users]', $q->getQuery());
    }
    /**
     * @test
     */
    public function testAggregate04() {
        $schema = new MSSQLTestSchema();
        $q = $schema->getQueryGenerator();
        $q->table('users')->selectAvg('id');
        $this->assertEquals('select avg([users].[id]) as [avg] from [users]', $q->getQuery());
    }
    /**
     * @test
     */
    public function testAggregate05() {
        $schema = new MSSQLTestSchema();
        $q = $schema->getQueryGenerator();
        $q->table('users')->selectCount();
        $this->assertEquals('select count(*) as count from [users]', $q->getQuery());
    }
    /**
     * @test
     */
    public function testAggregate06() {
        $schema = new MSSQLTestSchema();
        $q = $schema->getQueryGenerator();
        $q->table('users')->selectCount('id');
        $this->assertEquals('select count([users].[id]) as [count] from [users]', $q->getQuery());
    }
    /**
     * @test
     */
    public function testRenameCol00() {
        $schema = new MSSQLTestSchema();
        $schema->getTable('users')->getColByKey('id')->setName('user_id');
        
        $queryBuilder = $schema->getQueryGenerator();
        $queryBuilder->table('users')->renameCol('id');
        $this->assertEquals("exec sp_rename 'users.[id]', 'user_id', 'COLUMN'", $schema->getLastQuery());
    }
    /**
     * @test
     */
    public function testRenameCol01() {
        $this->expectException(DatabaseException::class);
        $this->expectExceptionMessage("The table users has no column with key 'not-exist'");
        $schema = new MSSQLTestSchema();
        $schema->table('users')->renameCol('not-exist');
        $this->assertEquals("exec sp_rename 'users.[not_exist]', 'user_id', 'COLUMN'", $schema->getLastQuery());
    }
    /**
     * @test
     */
    public function testRenameCol02() {
        $schema = new MSSQLTestSchema();
        $schema->table('users')->renameCol('id');
        $this->assertEquals("exec sp_rename 'users.[id]', 'id', 'COLUMN'", $schema->getLastQuery());
    }
    /**
     * @test
     */
    public function testAddColumn00() {
        $schema = new MSSQLTestSchema();
        $q = $schema->table('users_tasks');
        $q->addCol('details', null);
        $this->assertEquals("alter table [users_tasks] add [details] [varchar](1500) not null", $schema->getLastQuery());
    }
    /**
     * @test
     */
    public function testAddColumn01() {
        $schema = new MSSQLTestSchema();
        $q = $schema->table('users_tasks');
        $q->addCol('details', 'first');
        $this->assertEquals("alter table [users_tasks] add [details] [varchar](1500) not null", $schema->getLastQuery());
    }
    /**
     * @test
     */
    public function testAddColumn02() {
        $schema = new MSSQLTestSchema();
        $q = $schema->table('users_tasks');
        $q->addCol('details', 'is-finished');
        $this->assertEquals("alter table [users_tasks] add [details] [varchar](1500) not null", $schema->getLastQuery());
    }
    /**
     * @test
     */
    public function testAddColumn03() {
        $this->expectException(DatabaseException::class);
        $schema = new MSSQLTestSchema();
        $q = $schema->table('users_tasks');
        $q->addCol('details-y', 'not-exist');
        $this->assertEquals("alter table [users_tasks] add [details] [varchar](1500) not null", $schema->getLastQuery());
    }
        /**
     * @test
     * @depends testInsert04
     */
    public function testTransaction00() {
        $schema = new MSSQLTestSchema();
        $schema->transaction(function (Database $db) {
            $q = $db->table('users');
            $q->insert([
                'first-name' => 'IbrahimX',
                'last-name' => 'BinAlshikhX',
                'age' => 33
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
                'user_id' => 104,
                'created_on' => $tasks[0]['created_on'],
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
        $schema = new MSSQLTestSchema();
        $schema->transaction(function (Database $db, int $uId) {
            $db->table('users_tasks')
                ->insert([
                    'user-id' => $uId,
                    'details' => 'This another task.',
                ])->execute();
            
        }, [$userId]);
        $tasks = $schema->table('users_tasks')->select()->where('user_id', $userId)->execute()->getRows();
        $this->assertEquals([
            [
                'task_id' => 1,
                'user_id' => 104,
                'created_on' => $tasks[0]['created_on'],
                'last_updated' => null,
                'is_finished' => 0,
                'details' => 'This task is about testing if transactions work as intended.',
            ], [
                'task_id' => 2,
                'user_id' => 104,
                'created_on' => $tasks[0]['created_on'],
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
                'user_id' => 104,
                'created_on' => $tasks[0]['created_on'],
                'last_updated' => null,
                'is_finished' => 0,
                'details' => 'This task is about testing if transactions work as intended.',
            ], [
                'task_id' => 2,
                'user_id' => 104,
                'created_on' => $tasks[0]['created_on'],
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
        $schema = new MSSQLTestSchema();
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
            $this->assertEquals("515 - The statement has been terminated due to following: [Microsoft][ODBC Driver 18 for SQL Server][SQL Server]Cannot insert the value NULL into column 'age', table 'testing_db.dbo.users'; column does not allow nulls. INSERT fails.", $ex->getMessage());
            $this->assertEquals(515, $ex->getCode());
            $user = $schema->table('users')->select()->where('id', $userId)->execute()->getRows()[0];
            $this->assertEquals([
                'id' => 104,
                'first_name' => 'IbrahimX',
                'last_name' => 'BinAlshikhX',
                'age' => 33
            ], $user);
        }
        
    }
    /**
     * @test
     */
    public function testAddFk00() {
        $schema = new MSSQLTestSchema();
        $schema->table('users_tasks')->addForeignKey('user_task_fk');
        $this->assertEquals("alter table [users_tasks] add constraint user_task_fk foreign key ([user_id]) references [users] ([id]) on update no action on delete no action;", $schema->getLastQuery());
    }
    /**
     * @test
     */
    public function testAddFk01() {
        $schema = new MSSQLTestSchema();
        $this->expectException(DatabaseException::class);
        $this->expectExceptionMessage("No such foreign key: 'xyz'.");
        $schema->table('users_tasks')->addForeignKey('xyz');
    }
    /**
     * @test
     */
    public function testAddPrimaryKey00() {
        $schema = new MSSQLTestSchema();
        $schema->table('users')->addPrimaryKey('my_key', ['id']);
        $this->assertEquals('alter table [users] add constraint my_key primary key clustered ([id]);', $schema->getLastQuery());
        $schema->table('users')->addPrimaryKey('my_key', ['first-name', 'last-name']);
        $this->assertEquals('alter table [users] add constraint my_key primary key clustered ([first_name], [last_name]);', $schema->getLastQuery());
    }
    /**
     * @test
     */
    public function testDropCol00() {
        $schema = new MSSQLTestSchema();
        $schema->table('users')->dropCol('id');
        $this->assertEquals('alter table [users] drop column [id];', $schema->getLastQuery());
        $schema->table('users')->dropCol('first-name ');
        $this->assertEquals('alter table [users] drop column [first_name];', $schema->getLastQuery());
    }
    /**
     * @test
     */
    public function testDropCol01() {
        $schema = new MSSQLTestSchema();
        $schema->table('users')->dropCol('not-exist');
        $this->assertEquals('alter table [users] drop column [not_exist];', $schema->getLastQuery());
    }
    /**
     * @test
     */
    public function testDropFk00() {
        $schema = new MSSQLTestSchema();
        $schema->table('users_tasks')->dropForeignKey('user_task_fk');
        $this->assertEquals("alter table [users_tasks] drop constraint user_task_fk;", $schema->getLastQuery());
    }
    /**
     * @test
     */
    public function testDropPrimaryKey00() {
        $schema = new MSSQLTestSchema();
        $schema->table('users')->dropPrimaryKey('pk');
        $this->assertEquals('alter table [users] drop constraint pk', $schema->getLastQuery());
    }
    /**
     * @test
     */
    public function testWhereIn00() {
        $schema = new MSSQLTestSchema();
        $schema->table('users_tasks')->select()->whereIn('task-id', [7,"9","100"]);
        $this->assertEquals("select * from [users_tasks] where [users_tasks].[task_id] in(?, ?, ?)", $schema->getLastQuery()); 
    }
    /**
     * @test
     */
    public function testWhereIn01() {
        $schema = new MSSQLTestSchema();
        $schema->table('users_tasks')->select()->whereNotIn('task-id', [7,"9","100"]);
        $this->assertEquals("select * from [users_tasks] where [users_tasks].[task_id] not in(?, ?, ?)", $schema->getLastQuery()); 
        $this->assertEquals([
                7,
                '9',
                '100',
        ], $schema->getQueryGenerator()->getBindings());
    }
    /**
     * @test
     */
    public function testWhereIn02() {
        $schema = new MSSQLTestSchema();
        $schema->table('users')->select()->whereNotIn('first-name', [7,"9","100"]);
        $this->assertEquals("select * from [users] where [users].[first_name] not in(?, ?, ?)", $schema->getLastQuery()); 

        $this->assertEquals([
                7,
                '9',
                '100'
        ], $schema->getQueryGenerator()->getBindings());
    }
    /**
     * @test
     */
    public function testCreateTables() {
        $schema = new MSSQLTestSchema();
        $schema->createTables();
        $this->assertEquals("if not exists (select * from sysobjects where name='users' and xtype='U')\n"
                . "create table [users] (\n"
                . "    [id] [int] identity(1,1) not null,\n"
                . "    [first_name] [nvarchar](15) not null,\n"
                . "    [last_name] [nvarchar](20) not null,\n"
                . "    [age] [int] not null,\n"
                . "    constraint users_pk primary key clustered([id]) on [PRIMARY]\n"
                . ")\n"
                . "\n"
                . "if not exists (select * from sysobjects where name='users_privileges' and xtype='U')\n"
                . "create table [users_privileges] (\n"
                . "    [id] [int] not null,\n"
                . "    [can_edit_price] [bit] not null default 0,\n"
                . "    [can_change_username] [bit] not null,\n"
                . "    [can_do_anything] [bit] not null,\n"
                . "    constraint users_privileges_pk primary key clustered([id]) on [PRIMARY],\n"
                . "    constraint user_privilege_fk foreign key ([id]) references [users] ([id]) on update no action on delete no action\n"
                . ")\n"
                . "\n"
                . "if not exists (select * from sysobjects where name='users_tasks' and xtype='U')\n"
                . "create table [users_tasks] (\n"
                . "    [task_id] [int] identity(1,1) not null,\n"
                . "    [user_id] [int] not null,\n"
                . "    [created_on] [datetime2](0) not null default getdate(),\n"
                . "    [last_updated] [datetime2](0) null,\n"
                . "    [is_finished] [bit] not null default 0,\n"
                . "    [details] [varchar](1500) not null,\n"
                . "    constraint users_tasks_pk primary key clustered([task_id]) on [PRIMARY],\n"
                . "    constraint user_task_fk foreign key ([user_id]) references [users] ([id]) on update no action on delete no action\n"
                . ")\n"
                . "\n"
                . "if not exists (select * from sysobjects where name='profile_pics' and xtype='U')\n"
                . "create table [profile_pics] (\n"
                . "    [user_id] [int] not null,\n"
                . "    [pic] [binary](1) not null,\n"
                . "    constraint profile_pics_pk primary key clustered([user_id]) on [PRIMARY],\n"
                . "    constraint user_profile_pic_fk foreign key ([user_id]) references [users] ([id]) on update no action on delete no action\n"
                . ")"
                , $schema->getLastQuery());
    }
    /**
     * 
     * @param MSSQLTestSchema $schema
     * @depends testSetConnection00
     */
    public function testCreateTable($schema) {
        if (PHP_MAJOR_VERSION == 5) {
            $this->markTestSkipped('PHP 5 has no MSSQL driver in selected setup.');
            return;
        }
        $schema->table('users')->createTable();
        $this->assertEquals("if not exists (select * from sysobjects where name='users' and xtype='U')\n"
                . "create table [users] (\n"
                . "    [id] [int] identity(1,1) not null,\n"
                . "    [first_name] [nvarchar](15) not null,\n"
                . "    [last_name] [nvarchar](20) not null,\n"
                . "    [age] [int] not null,\n"
                . "    constraint users_pk primary key clustered([id]) on [PRIMARY]\n"
                . ")"
                , $schema->getLastQuery());
        $schema->execute();
        
        $schema->table('users_privileges')->createTable();
        $this->assertEquals("if not exists (select * from sysobjects where name='users_privileges' and xtype='U')\n"
                . "create table [users_privileges] (\n"
                . "    [id] [int] not null,\n"
                . "    [can_edit_price] [bit] not null default 0,\n"
                . "    [can_change_username] [bit] not null,\n"
                . "    [can_do_anything] [bit] not null,\n"
                . "    constraint users_privileges_pk primary key clustered([id]) on [PRIMARY],\n"
                . "    constraint user_privilege_fk foreign key ([id]) references [users] ([id]) on update no action on delete no action\n"
                . ")"
                , $schema->getLastQuery());
        $schema->execute();
        
        $schema->table('users_tasks')->createTable();
        $this->assertEquals("if not exists (select * from sysobjects where name='users_tasks' and xtype='U')\n"
                . "create table [users_tasks] (\n"
                . "    [task_id] [int] identity(1,1) not null,\n"
                . "    [user_id] [int] not null,\n"
                . "    [created_on] [datetime2](0) not null default getdate(),\n"
                . "    [last_updated] [datetime2](0) null,\n"
                . "    [is_finished] [bit] not null default 0,\n"
                . "    [details] [varchar](1500) not null,\n"
                . "    constraint users_tasks_pk primary key clustered([task_id]) on [PRIMARY],\n"
                . "    constraint user_task_fk foreign key ([user_id]) references [users] ([id]) on update no action on delete no action\n"
                . ")"
                , $schema->getLastQuery());
        $schema->execute();
        
        $schema->table('profile_pics')->createTable();
        $this->assertEquals("if not exists (select * from sysobjects where name='profile_pics' and xtype='U')\n"
                . "create table [profile_pics] (\n"
                . "    [user_id] [int] not null,\n"
                . "    [pic] [binary](1) not null,\n"
                . "    constraint profile_pics_pk primary key clustered([user_id]) on [PRIMARY],\n"
                . "    constraint user_profile_pic_fk foreign key ([user_id]) references [users] ([id]) on update no action on delete no action\n"
                . ")"
                , $schema->getLastQuery());
        $schema->execute();
        
        return $schema;
    }
    public function testDelete00() {
        $schema = new MSSQLTestSchema();
        $schema->table('users')
                ->delete()
                ->where('id', 88);
        $this->assertEquals("delete from [users] where [users].[id] = ?", $schema->getLastQuery());
        $schema->where('id', 55);
        $this->assertEquals("delete from [users] where [users].[id] = ? and [users].[id] = ?", $schema->getLastQuery());
        $schema->orWhere('id', '8', '!=');
        $this->assertEquals("delete from [users] where [users].[id] = ? and [users].[id] = ? or [users].[id] != ?", $schema->getLastQuery());
    }
    public function testDelete04() {
        $schema = new MSSQLTestSchema();
        $q = $schema->table('users');
        
               $q ->delete()
        ->orWhere(
                $q->orWhere('first-name', 'Ibrahim')
                ->andWhere('last-name', 'BinAlshikh'));
        $this->assertEquals("delete from [users] where ([users].[first_name] = ? and [users].[last_name] = ?)", $schema->getLastQuery());
    }
    public function testDelete03() {
        $schema = new MSSQLTestSchema();
        $q = $schema->table('users')
                ->delete()
                ->where('id', 88);
        $this->assertEquals("delete from [users] where [users].[id] = ?", $schema->getLastQuery());
        $q->where('id', 55);
        $this->assertEquals("delete from [users] where [users].[id] = ? and [users].[id] = ?", $schema->getLastQuery());
    }
    /**
     * @test
     */
    public function testDelete01() {
        $schema = new MSSQLTestSchema();
        $schema->table('users')
                ->delete()
                ->where('x-id', 88);
        $this->assertEquals("delete from [users] where [users].[x_id] = ?", $schema->getLastQuery());
        $schema->table('users')
                ->delete()
                ->where('x-id', '88');
        $this->assertEquals("delete from [users] where [users].[x_id] = ?", $schema->getLastQuery());
    }
    /**
     * @test
     * @param MSSQLTestSchema $schema
     */
    public function testInsert03() {
        $schema = new MSSQLTestSchema();
        $schema->createTables()->execute();
        // Clear table first to ensure clean state
        $schema->table('users')->delete()->execute();
        // Reset identity to start from 1
        $schema->setQuery("DBCC CHECKIDENT ('users', RESEED, 1)")->execute();
        $schema->table('users')->insert([
            'first-name' => 'Ibrahim',
            'last-name' => 'BinAlshikh',
            'age' => 28
        ])->execute();
        $schema->table('users')->select()->execute();
        $resultSet = $schema->getLastResultSet();
        $this->assertEquals(1, $resultSet->getRowsCount());
        $this->assertEquals([
            [
                'id' => 1,
                'first_name' => 'Ibrahim',
                'last_name' => 'BinAlshikh',
                'age' => 28
            ]
        ], $resultSet->getRows());
        return $schema;
    }
    /**
     * @test
     * @param MSSQLTestSchema $schema
     * @depends testInsert03
     */
    public function testDropRecord00(MSSQLTestSchema $schema) {
        $row = $schema->getLastResultSet()->getRows()[0];
        $schema->table('users')->delete()->where('id', $row['id']);
        $this->assertEquals('delete from [users] where [users].[id] = ?', $schema->getLastQuery());
        $schema->execute();
        $schema->table('users')->select()->execute();
        $this->assertEquals(0, $schema->getLastResultSet()->getRowsCount());
        return $schema;
    }
    /**
     * 
     * @test
     * @param MSSQLTestSchema $schema
     * @depends testDropRecord00
     */
    public function testInsert04(MSSQLTestSchema $schema) {
        // Clear any existing records with IDs 100, 101
        $schema->table('users')->delete()->where('id', 100)->execute();
        $schema->table('users')->delete()->where('id', 101)->execute();
        
        $schema->setQuery('set identity_insert users on;')->execute();
        $schema->table('users')->insert([
            'cols' => [
                'id','first-name','last-name','age'
            ],
            'values' => [
                [100,'Ali','Hassan',16],
                [101,'Dabi','Jona',19]
            ]
        ]);
        $this->assertEquals("insert into [users] ([id], [first_name], [last_name], [age])\nvalues\n"
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
            if ($row['id'] == 103 || $row['id'] == 1) {
                $this->assertEquals('Ibrahim', $row['first_name']);
            }
        }
        return $schema;
    }
    /**
     * @test
     */
    public function testInsert00() {
        $schema = new MSSQLTestSchema();
        $q = $schema->table('users');
        $q->insert([
            'id' => 8,
            'first-name' => 'Ibrahim',
            'last-name' => 'BinAlshikh'
        ]);
        $this->assertEquals("insert into [users] ([id], [first_name], [last_name]) values (?, ?, ?);", $schema->getLastQuery());
    }
    /**
     * @test
     */
    public function testInsert01() {
        $schema = new MSSQLTestSchema();
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
        $this->assertEquals("insert into [users] ([id], [first_name], [last_name])\nvalues\n"
                . "(?, ?, ?),\n"
                . "(?, ?, ?);", $schema->getLastQuery());
    }
    /**
     * @test
     */
    public function testInsert02() {
        $schema = new MSSQLTestSchema();
        $q = $schema->table('users_tasks');
        $q->insert([
            'user-id' => 6,
            'details' => 'OK task'
        ]);
        $this->assertEquals("insert into [users_tasks] "
                . "([user_id], [details], [created_on], [is_finished]) "
                . "values (?, ?, ?, ?);", $schema->getLastQuery());
        $q->insert([
            'user-id' => 6,
            'details' => 'OK task',
            'created-on' => '2020-10-16 00:00:00',
            'is-finished' => true
        ]);
        $this->assertEquals("insert into [users_tasks] "
                . "([user_id], [details], [created_on], [is_finished]) "
                . "values (?, ?, ?, ?);", $schema->getLastQuery());
    }
    /**
     * @test
     */
    public function testInsert05() {
        $schema = new MSSQLTestSchema();
        $q = $schema->table('users_tasks');
        $q->insert([
            'user-id' => null,
            'details' => 'OK task'
        ]);
        $this->assertEquals("insert into [users_tasks] "
                . "([user_id], [details], [created_on], [is_finished]) "
                . "values (?, ?, ?, ?);", $schema->getLastQuery());
    }
    /**
     * @test
     */
    public function testInsert06() {
        $this->expectException(DatabaseException::class);
        $this->expectExceptionMessage("207 - Statement(s) could not be prepared due to the following: [Microsoft][ODBC Driver 18 for SQL Server][SQL Server]Invalid column name 'not_exist'.");
        $schema = new MSSQLTestSchema();
        $q = $schema->table('users_tasks');
        $q->insert([
            'user-id' => null,
            'details' => 'OK task',
            'not-exist' => 7
        ]);
        $this->assertEquals("insert into [users_tasks] "
                . "([user_id], [details], [not_exist], [created_on], [is_finished]) "
                . "values (?, ?, ?, ?, ?);", $schema->getLastQuery());
        $q->execute();
    }
    /**
     * @test
     */
    public function testLeft00() {
        $this->expectException(DatabaseException::class);
        $schema = new MSSQLTestSchema();
        $schema->table('userss_tasks')->whereLeft('details', 3, '=', 'hello');
    }
    /**
     * @test
     */
    public function testLeft01() {
        $schema = new MSSQLTestSchema();
        $schema->table('users_tasks')->select()->whereLeft('details', 2, '=', 'hello');
        $this->assertEquals("select * from [users_tasks] where left([users_tasks].[details], 2) = ?", $schema->getLastQuery());
    }
    /**
     * @test
     */
    public function testLeft02() {
        $schema = new MSSQLTestSchema();
        $schema->table('users_tasks')->select()->whereLeft('details', 8, '*', 'good');
        $this->assertEquals("select * from [users_tasks] where left([users_tasks].[details], 8) = ?", $schema->getLastQuery());
    }
    /**
     * @test
     */
    public function testLeft03() {
        $schema = new MSSQLTestSchema();
        $schema->table('users_tasks')->select()->whereLeft('details', 8, '!=', 'good');
        $this->assertEquals("select * from [users_tasks] where left([users_tasks].[details], 8) != ?", $schema->getLastQuery());
    }
    /**
     * @test
     */
    public function testLeft04() {
        $schema = new MSSQLTestSchema();
        $schema->table('users_tasks')->select()->whereLeft('details', 8, 'in', 'good');
        $this->assertEquals("select * from [users_tasks] where left([users_tasks].[details], 8) in(?)", $schema->getLastQuery());
    }
    /**
     * @test
     */
    public function testLeft05() {
        $schema = new MSSQLTestSchema();
        $q = $schema->getQueryGenerator();
        $q->table('users_tasks')->select()
                ->whereLeft('details', 8, 'not in', ['good']);
        $this->assertEquals("select * from [users_tasks] where left([users_tasks].[details], 8) not in(?)", $schema->getLastQuery());
        $q->andWhere('user-id', 9);
        $this->assertEquals("select * from [users_tasks] where left([users_tasks].[details], 8) not in(?) and [users_tasks].[user_id] = ?", $schema->getLastQuery());
    }
    /**
     * @test
     */
    public function testLeft06() {
        $schema = new MSSQLTestSchema();
        $schema->table('users_tasks')->select()->whereLeft('details', 8, 'not in', ['good', 'bad']);
        $this->assertEquals("select * from [users_tasks] where left([users_tasks].[details], 8) not in(?, ?)", $schema->getLastQuery());
    }
    /**
     * @test
     */
    public function testLeft07() {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The value must be of type string since the condition is \'=\'.');
        $schema = new MSSQLTestSchema();
        $schema->table('users_tasks')->select()->whereLeft('details', 8, '=', ['good', 'bad']);
    }
    /**
     * @test
     */
    public function testLeft08() {
        $schema = new MSSQLTestSchema();
        $q = $schema->getQueryGenerator();
        $q->table('users_tasks')->select()->whereBetween('created-on', '2020-01-01', '2020-04-01');
        $this->assertEquals('select * from [users_tasks] where ([users_tasks].[created_on] between ? and ?)', $q->getQuery());
        $q->whereLeft('details', 5, '=', 'ok ok', 'or');
        $this->assertEquals('select * from [users_tasks] where ([users_tasks].[created_on] between ? and ?) or left([users_tasks].[details], 5) = ?', $q->getQuery());
    }
    /**
     * @test
     */
    public function testLike00() {
        $schema = new MSSQLTestSchema();
        $schema->table('users_tasks')->select()->whereLike('task-id', 9);
        $this->assertEquals("select * from [users_tasks]", $schema->getLastQuery()); 
    }
    /**
     * @test
     */
    public function testLike01() {
        $schema = new MSSQLTestSchema();
        $schema->table('users')->select()->whereLike('first-name', '%Ibra%');
        $this->assertEquals("select * from [users] where [users].[first_name] like ?", $schema->getLastQuery()); 
    }
    /**
     * @test
     */
    public function testLike02() {
        $schema = new MSSQLTestSchema();
        $schema->table('users')->select()->whereNotLike('first-name', '%Ibra%');
        $this->assertEquals("select * from [users] where [users].[first_name] not like ?", $schema->getLastQuery()); 
    }
    /**
     * @test
     */
    public function testLike03() {
        $schema = new MSSQLTestSchema();
        $schema->table('users_tasks')->select()->whereNotLike('first-naome', '%Ibra%');
        $this->assertEquals("select * from [users_tasks] where [users_tasks].[first_naome] not like ?", $schema->getLastQuery());
    }
    /**
     * @test
     */
    public function testRight01() {
        $schema = new MSSQLTestSchema();
        $schema->table('users_tasks')->select()->whereRight('details', 2, '=', 'hello');
        $this->assertEquals("select * from [users_tasks] where right([users_tasks].[details], 2) = ?", $schema->getLastQuery());
    }
    /**
     * @test
     */
    public function testRight02() {
        $schema = new MSSQLTestSchema();
        $schema->table('users_tasks')->select()->whereRight('details', 8, '*', 'good');
        $this->assertEquals("select * from [users_tasks] where right([users_tasks].[details], 8) = ?", $schema->getLastQuery());
    }
    /**
     * @test
     */
    public function testRight03() {
        $schema = new MSSQLTestSchema();
        $schema->table('users_tasks')->select()->whereRight('details', 8, '!=', 'good');
        $this->assertEquals("select * from [users_tasks] where right([users_tasks].[details], 8) != ?", $schema->getLastQuery());
    }
    /**
     * @test
     */
    public function testRight04() {
        $schema = new MSSQLTestSchema();
        $schema->table('users_tasks')->select()->whereRight('details', 8, 'in', 'good');
        $this->assertEquals("select * from [users_tasks] where right([users_tasks].[details], 8) in(?)", $schema->getLastQuery());
    }
    /**
     * @test
     */
    public function testRight05() {
        $schema = new MSSQLTestSchema();
        $schema->table('users_tasks')->select()->whereRight('details', 8, 'not in', ['good']);
        $this->assertEquals("select * from [users_tasks] where right([users_tasks].[details], 8) not in(?)", $schema->getLastQuery());
    }
    /**
     * @test
     */
    public function testRight06() {
        $schema = new MSSQLTestSchema();
        $schema->table('users_tasks')->select()->whereRight('details', 8, 'not in', ['good', 'bad']);
        $this->assertEquals("select * from [users_tasks] where right([users_tasks].[details], 8) not in(?, ?)", $schema->getLastQuery());
    }
    /**
     * @test
     */
    public function testRight07() {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The value must be of type string since the condition is \'!=\'.');
        $schema = new MSSQLTestSchema();
        $schema->table('users_tasks')->select()->whereLeft('details', 8, '!=', ['good', 'bad']);
    }
    /**
     * @test
     */
    public function testSelectWithWhere013() {
        $schema = new MSSQLTestSchema();
        $bulder = $schema->table('users')->select()->where('id', 66);
        $this->assertEquals('select * from [users] where [users].[id] = ?', $schema->getLastQuery());
        $bulder->groupBy('first-name');
        $this->assertEquals('select * from [users] where [users].[id] = ? group by [users].[first_name]', $schema->getLastQuery());
        $bulder->groupBy(['first-name','last-name']);
        $this->assertEquals('select * from [users] where [users].[id] = ? group by [users].[first_name], [users].[last_name]', $schema->getLastQuery());
        $bulder->orderBy(['last-name'=>'a']);
        $this->assertEquals('select * from [users] where [users].[id] = ? group by [users].[first_name], [users].[last_name] order by [users].[last_name] asc', $schema->getLastQuery());
    }
    /**
     * @test
     */
    public function testSelectWithWhere001() {
        $schema = new MSSQLTestSchema();
        $schema->table('users')->select()->where(
            $schema->where('id', 7)
        );
        $this->assertEquals('select * from [users] where [users].[id] = ?', $schema->getLastQuery());
        $schema->orWhere('first-name', 'Ibrahim');
        $this->assertEquals('select * from [users] where [users].[id] = ? or [users].[first_name] = ?', $schema->getLastQuery());
        $schema->clear();
    }
    
    /**
     * @test
     */
    public function testSelectWithWhere003() {
        $schema = new MSSQLTestSchema();
        $q = $schema->table('users')->select();
        $q->where(
            $q->where(
                $q->where('id', 7)
            )
        );
        $this->assertEquals('select * from [users] where [users].[id] = ?', $schema->getLastQuery());
        $schema->orWhere('first-name', 'Ibrahim');
        $this->assertEquals('select * from [users] where [users].[id] = ? or [users].[first_name] = ?', $schema->getLastQuery());
        $schema->clear();
    }
    
    /**
     * @test
     */
    public function testSelectWithWhere010() {
        $schema = new MSSQLTestSchema();
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
        
        $this->assertEquals('select * from [users] '
                . 'where (([users].[id] = ? or [users].[id] = ?) '
                . 'or ([users].[first_name] = ? and [users].[last_name] = ?))', $schema->getLastQuery());
    }
    /**
     * @test
     */
    public function testSelectWithWhere004() {
        $schema = new MSSQLTestSchema();
        $q = $schema->table('users')->select();
        
        $q->where(
            $q->where('id', 7)
        )->where('id', 8)
         ->orWhere('id', 88);
        
        $this->assertEquals('select * from [users] where [users].[id] = ? and [users].[id] = ? or [users].[id] = ?', $schema->getLastQuery());
        $schema->orWhere('first-name', 'Ibrahim');
        
        $this->assertEquals('select * from [users] where [users].[id] = ? '
                . 'and [users].[id] = ? or [users].[id] = ? or [users].[first_name] = ?', $schema->getLastQuery());
    }
    /**
     * @test
     */
    public function testSelectWithWhere002() {
        $schema = new MSSQLTestSchema();
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
        $this->assertEquals('select * from [users] '
                . 'where [users].[id] = ? '
                . 'or [users].[first_name] = ? and [users].[last_name] = ?', $schema->getLastQuery());
    }
    /**
     * @test
     * 
     */
    public function testSelectWithWhere011() {
        $schema = new MSSQLTestSchema();
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
        $this->assertEquals('select * from [users] where [users].[id] = ? and [users].[id] = ?', $schema->getLastQuery());
    }
    /**
     * @test
     * 
     */
    public function testSelectWithWhere012() {
        $schema = new MSSQLTestSchema();
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
        $this->assertEquals('select * from [users] where [users].[id] is null and [users].[id] is not null', $schema->getLastQuery());
    }
    /**
     * @test
     */
    public function testSelectWithWhere000() {
        $schema = new MSSQLTestSchema();
        $bulder = $schema->table('users')->select()->where('id', 66);
        $this->assertEquals('select * from [users] where [users].[id] = ?', $schema->getLastQuery());
        $bulder->getTable()->getSelect()->addWhere(new Expression('year(x) in(300)'));
        $bulder->groupBy([
            'first-name',
            'last-name'
        ]);
        $this->assertEquals('select * from [users] where [users].[id] = ? and year(x) in(300) group by [users].[first_name], [users].[last_name]', $schema->getLastQuery());
    }
    /**
     * @test
     * 
     */
    public function testSelectWithWhere009() {
        $schema = new MSSQLTestSchema();
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
        $this->assertEquals('select * from [users] where ([users].[id] = ? and ([users].[id] = ? and [users].[id] = ? and [users].[first_name] = ?))', $schema->getLastQuery());
    }
    
    /**
     * @test
     */
    public function testSelectWithWhere005() {
        $schema = new MSSQLTestSchema();
        $q = $schema->table('users')->select();
        
        //5
        $q->where(
            $q->where(
                $q->where('id', 7)
            )->where(
                $q->where('id', 8)
            )
        );
        $this->assertEquals('select * from [users] where [users].[id] = ? and [users].[id] = ?', $schema->getLastQuery());
        $schema->orWhere('first-name', 'Ibrahim');
        $this->assertEquals('select * from [users] where [users].[id] = ? and [users].[id] = ? or [users].[first_name] = ?', $schema->getLastQuery());
    }
    /**
     * @test
     */
    public function testSelectWithWhere006() {
        $schema = new MSSQLTestSchema();
        $q = $schema->table('users')->select();
        
        $q->where('id', 2)
                ->where('id', 9, '!=', 'or')
                ->orWhere('id', 10)
                ->andWhere('id', 30)
                ->andWhere('first-name', 'Ibr', '!=');
        $this->assertEquals('select * from [users] where '
                . '[users].[id] = ? or '
                . '[users].[id] != ? or '
                . '[users].[id] = ? and '
                . '[users].[id] = ? and '
                . '[users].[first_name] != ?', $schema->getLastQuery());
    }
    /**
     * @test
     */
    public function testSelectWithWhere007() {
        $schema = new MSSQLTestSchema();
        $q = $schema->table('users')->select();
        
        $q->where($q->where('id', 2)
                ->where('id', 9,'!=',  'or')
                ->orWhere('id', 10)
                ->andWhere('id', 30)
                ->andWhere('first-name', 'Ibr', '!='));
        $this->assertEquals('select * from [users] where ('
                . '[users].[id] = ? or '
                . '[users].[id] != ? or '
                . '[users].[id] = ? and '
                . '[users].[id] = ? and '
                . '[users].[first_name] != ?)', $schema->getLastQuery());
    }
    /**
     * @test
     */
    public function testSelectWithWhere008() {
        $schema = new MSSQLTestSchema();
        $q = $schema->table('users')->select();
        
        $q->where($q->where($q->where('id', 2)
                ->where('id', 9, '!=','or')
                ->orWhere('id',10)
                ->andWhere('id', 30)
                ->andWhere('first-name', 'Ibr', '!=')));
        $this->assertEquals('select * from [users] where ('
                . '[users].[id] = ? or '
                . '[users].[id] != ? or '
                . '[users].[id] = ? and '
                . '[users].[id] = ? and '
                . '[users].[first_name] != ?)', $schema->getLastQuery());
    }
    

    /**
     * @test
     * 
     * @return MSSQLTestSchema Description
     */
    public function testSetConnection00() {
        if (PHP_MAJOR_VERSION == 5) {
            $this->markTestSkipped('PHP 5 has no MSSQL driver in selected setup.');
        } else {
            $connInfo = new ConnectionInfo('mssql','sa', getenv('SA_SQL_SERVER_PASSWORD'), 'testing_db', SQL_SERVER_HOST, 1433, [
                'TrustServerCertificate' => 'true'
            ]);
            $conn = new MSSQLConnection($connInfo);
            $schema = new MSSQLTestSchema();
            $schema->setConnection($conn);
            $this->assertTrue(true);
            return $schema;
        }
    }
    /**
     * @test
     * 
     * @return MSSQLTestSchema Description
     */
    public function testSetConnection01() {
        $this->expectException(DatabaseException::class);
        $connInfo = new ConnectionInfo('mssql', 'root', '12345', 'testing_db', '127.0.0.1');
        $conn = new MSSQLConnection($connInfo);
        $schema = new MSSQLTestSchema();
        $schema->setConnection($conn);
        return $schema;
    }
    /**
     * @test
     */
    public function testUpdate00() {
        $schema = new MSSQLTestSchema();
        $q = $schema->table('users_tasks');
        
        $q->update([
            'details' => 'OKKKKKKKk'
        ]);
        $date = date('Y-m-d H:i:s');
        $this->assertEquals("update [users_tasks] set [details] = ?, [last_updated] = ?", $schema->getLastQuery());
    }
    /**
     * @test
     */
    public function testUpdate01() {
        $schema = new MSSQLTestSchema();
        $q = $schema->table('users_tasks');
        
        $q->update([
            'details' => 'OKKKKKKKk'
        ])->where('task-id', 77);
        $date = date('Y-m-d H:i:s');
        $this->assertEquals("update [users_tasks] set [details] = ?, [last_updated] = ? where [users_tasks].[task_id] = ?", $schema->getLastQuery());
        $q->andWhere('user-id', 6);
        $this->assertEquals("update [users_tasks] set [details] = ?, [last_updated] = ? "
                . "where [users_tasks].[task_id] = ? and [users_tasks].[user_id] = ?", $schema->getLastQuery());
    }
     /**
     * @test
     */
    public function testUpdate02() {
        $schema = new MSSQLTestSchema();
        $q = $schema->table('users_tasks');
        
        $q->update([
            'details' => null
        ])->where('task-id', 77);
        $date = date('Y-m-d H:i:s');
        $this->assertEquals("update [users_tasks] set [details] = null, [last_updated] = ? where [users_tasks].[task_id] = ?", $schema->getLastQuery());
        $q->andWhere('user-id', 6);
        $this->assertEquals("update [users_tasks] set [details] = null, [last_updated] = ? "
                . "where [users_tasks].[task_id] = ? and [users_tasks].[user_id] = ?", $schema->getLastQuery());
    }
     /**
     * @test
     */
    public function testUpdate03() {
        $schema = new MSSQLTestSchema();
        $q = $schema->table('users_tasks');
        
        $q->update([
            'details' => null
        ])->where('last-updated', '2021-07-13');
        $date = date('Y-m-d H:i:s');
        $this->assertEquals("update [users_tasks] set [details] = null, "
                . "[last_updated] = ? where [users_tasks].[last_updated] = ?", $schema->getLastQuery());
        $q->andWhere('user-id', 6);
        $this->assertEquals("update [users_tasks] set [details] = null, [last_updated] = ? "
                . "where [users_tasks].[last_updated] = ? and [users_tasks].[user_id] = ?", $schema->getLastQuery());
    }
    /**
     * @test
     */
    public function testWhereBetween00() {
        $schema = new MSSQLTestSchema();
        $schema->table('users_tasks')->select()->whereBetween('task-id', 0, 33);
        $this->assertEquals('select * from [users_tasks] where ([users_tasks].[task_id] between ? and ?)', $schema->getLastQuery());
        $schema->andWhere('user-id', 88);
        $this->assertEquals('select * from [users_tasks] where ([users_tasks].[task_id] between ? and ?) and [users_tasks].[user_id] = ?', $schema->getLastQuery());
    }
    /**
     * @test
     */
    public function testWhereBetween01() {
        $schema = new MSSQLTestSchema();
        $schema->table('users_tasks')->select()->whereBetween('task-idx', 0, 33);
        $this->assertEquals("select * from [users_tasks] where ([users_tasks].[task_idx] between ? and ?)", $schema->getLastQuery());
        $schema->table('users_tasks')->select()->whereBetween('task-idx', '0', '33');
        $this->assertEquals("select * from [users_tasks] where ([users_tasks].[task_idx] between ? and ?)", $schema->getLastQuery());
    }
    /**
     * @test
     */
    public function testWhereBetween02() {
        $schema = new MSSQLTestSchema();
        $this->expectException(DatabaseException::class);
        $schema->table('users_tasks')->whereBetween('task-idx', 0, 33);
    }
    /**
     * @test
     */
    public function testWhereBetween03() {
        $schema = new MSSQLTestSchema();
        $schema->table('users_tasks')->select()->whereNotBetween('task-id', 0, 33);
        $this->assertEquals('select * from [users_tasks] where ([users_tasks].[task_id] not between ? and ?)', $schema->getLastQuery());
        $schema->getQueryGenerator()->andWhere('user-id', 88);
        $this->assertEquals('select * from [users_tasks] where ([users_tasks].[task_id] not between ? and ?) and [users_tasks].[user_id] = ?', $schema->getLastQuery());
    }
    /**
     * @test
     */
    public function testWhereBetween04() {
        $schema = new MSSQLTestSchema();
        $schema->table('users_tasks')->select()->whereNotBetween('task-id', 0, 33);
        $this->assertEquals('select * from [users_tasks] where ([users_tasks].[task_id] not between ? and ?)', $schema->getLastQuery());
    }
    /**
     * @test
     */
    public function testWhereBetween05() {
        $schema = new MSSQLTestSchema();
        $schema->table('users_tasks')->select()->whereBetween('last-updated', '2020-01-02 01:30:00', '2020-02-15');
        $this->assertEquals('select * from [users_tasks] where ([users_tasks].[last_updated] between ? and ?)', $schema->getLastQuery());
    }
    /**
     * @test
     */
    public function testAfreggate07() {
        $schema = new MSSQLTestSchema();
        $schema->table('reports_list')
                ->select(['function', new Expression('count(*) as count')])
                ->orderBy(['count'])
                ->groupBy('function');
        $this->assertEquals('select [reports_list].[function], count(*) as count from [reports_list] group by [reports_list].[function] order by [reports_list].[count]', $schema->getLastQuery());
    }
}
