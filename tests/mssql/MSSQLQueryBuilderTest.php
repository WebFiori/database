<?php

namespace mssql;

use PHPUnit\Framework\TestCase;
use mssql\MSSQLTestSchema;
use webfiori\database\ConnectionInfo;
use webfiori\database\mssql\MSSQLConnection;
use webfiori\database\DatabaseException;
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
    public function testCreateTables() {
        $schema = new MSSQLTestSchema();
        $schema->createTables();
        $this->assertEquals("if not exists (select * from sysobjects where name='users' and xtype='U')\n"
                . "create table [users] (\n"
                . "    [id] [int] not null,\n"
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
                . "    constraint user_privilege_fk foreign key ([id]) references [users] ([id]) on update cascade on delete cascade\n"
                . ")\n"
                . "\n"
                . "if not exists (select * from sysobjects where name='users_tasks' and xtype='U')\n"
                . "create table [users_tasks] (\n"
                . "    [task_id] [int] not null,\n"
                . "    [user_id] [int] not null,\n"
                . "    [created_on] [datetime2] not null default getdate(),\n"
                . "    [last_updated] [datetime2] null,\n"
                . "    [is_finished] [bit] not null default 0,\n"
                . "    [details] [nvarchar](1500) not null,\n"
                . "    constraint users_tasks_pk primary key clustered([task_id]) on [PRIMARY],\n"
                . "    constraint user_task_fk foreign key ([user_id]) references [users] ([id]) on update cascade on delete cascade\n"
                . ")\n"
                . "\n"
                . "if not exists (select * from sysobjects where name='profile_pics' and xtype='U')\n"
                . "create table [profile_pics] (\n"
                . "    [user_id] [int] not null,\n"
                . "    [pic] [binary](1) not null,\n"
                . "    constraint profile_pics_pk primary key clustered([user_id]) on [PRIMARY],\n"
                . "    constraint user_profile_pic_fk foreign key ([user_id]) references [users] ([id]) on update cascade on delete cascade\n"
                . ")"
                , $schema->getLastQuery());
    }
    /**
     * 
     * @param MSSQLTestSchema $schema
     * @depends testSetConnection00
     */
    public function testCreateTable($schema) {
        $schema->table('users')->createTable();
        $this->assertEquals("if not exists (select * from sysobjects where name='users' and xtype='U')\n"
                . "create table [users] (\n"
                . "    [id] [int] not null,\n"
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
                . "    constraint user_privilege_fk foreign key ([id]) references [users] ([id]) on update cascade on delete cascade\n"
                . ")"
                , $schema->getLastQuery());
        $schema->execute();
        
        $schema->table('users_tasks')->createTable();
        $this->assertEquals("if not exists (select * from sysobjects where name='users_tasks' and xtype='U')\n"
                . "create table [users_tasks] (\n"
                . "    [task_id] [int] not null,\n"
                . "    [user_id] [int] not null,\n"
                . "    [created_on] [datetime2] not null default getdate(),\n"
                . "    [last_updated] [datetime2] null,\n"
                . "    [is_finished] [bit] not null default 0,\n"
                . "    [details] [nvarchar](1500) not null,\n"
                . "    constraint users_tasks_pk primary key clustered([task_id]) on [PRIMARY],\n"
                . "    constraint user_task_fk foreign key ([user_id]) references [users] ([id]) on update cascade on delete cascade\n"
                . ")"
                , $schema->getLastQuery());
        $schema->execute();
        
        $schema->table('profile_pics')->createTable();
        $this->assertEquals("if not exists (select * from sysobjects where name='profile_pics' and xtype='U')\n"
                . "create table [profile_pics] (\n"
                . "    [user_id] [int] not null,\n"
                . "    [pic] [binary](1) not null,\n"
                . "    constraint profile_pics_pk primary key clustered([user_id]) on [PRIMARY],\n"
                . "    constraint user_profile_pic_fk foreign key ([user_id]) references [users] ([id]) on update cascade on delete cascade\n"
                . ")"
                , $schema->getLastQuery());
        $schema->execute();
        
        return $schema;
    }
    public function testDelete00() {
        $schema = new MSSQLTestSchema();
        $schema->table('users')
                ->delete()
                ->where('id', '=', 88);
        $this->assertEquals("delete from [users] where [users].[id] = 88", $schema->getLastQuery());
        $schema->where('id', '=', 55);
        $this->assertEquals("delete from [users] where [users].[id] = 88 and [users].[id] = 55", $schema->getLastQuery());
        $schema->orWhere('id', '!=', '8');
        $this->assertEquals("delete from [users] where [users].[id] = 88 and [users].[id] = 55 or [users].[id] != 8", $schema->getLastQuery());
    }
    public function testDelete04() {
        $schema = new MSSQLTestSchema();
        $q = $schema->table('users');
        
               $q ->delete()
        ->orWhere(
                $q->orWhere('first-name', '=', 'Ibrahim')
                ->andWhere('last-name', '=', 'BinAlshikh'));
        $this->assertEquals("delete from [users] where ([users].[first_name] = 'Ibrahim' and [users].[last_name] = 'BinAlshikh')", $schema->getLastQuery());
    }
    public function testDelete03() {
        $schema = new MSSQLTestSchema();
        $q = $schema->table('users')
                ->delete()
                ->where('id', '=', 88);
        $this->assertEquals("delete from [users] where [users].[id] = 88", $schema->getLastQuery());
        $q->where('id', '=', 55);
        $this->assertEquals("delete from [users] where [users].[id] = 88 and [users].[id] = 55", $schema->getLastQuery());
    }
    /**
     * @test
     */
    public function testDelete01() {
        $this->expectException(DatabaseException::class);
        $schema = new MSSQLTestSchema();
        $schema->table('users')
                ->delete()
                ->where('x-id', '=', 88);
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
        $this->assertEquals("insert into [users] ([id], [first_name], [last_name]) values (8, 'Ibrahim', 'BinAlshikh');", $schema->getLastQuery());
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
        $this->assertEquals("insert into [users]\n([id], [first_name], [last_name])\nvalues\n"
                . "(8, 'Ibrahim', 'BinAlshikh'),\n"
                . "(9, 'Web', 'DB');", $schema->getLastQuery());
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
                . "values (6, 'OK task', '".date('Y-m-d H:i:s')."', 0);", $schema->getLastQuery());
        $q->insert([
            'user-id' => 6,
            'details' => 'OK task',
            'created-on' => '2020-10-16 00:00:00',
            'is-finished' => true
        ]);
        $this->assertEquals("insert into [users_tasks] "
                . "([user_id], [details], [created_on], [is_finished]) "
                . "values (6, 'OK task', '2020-10-16 00:00:00', 1);", $schema->getLastQuery());
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
                . "values (null, 'OK task', '".date('Y-m-d H:i:s')."', 0);", $schema->getLastQuery());
    }
    /**
     * @test
     */
    public function testLeft00() {
        $this->expectException(DatabaseException::class);
        $schema = new MSSQLTestSchema();
        $schema->table('userss_tasks')->whereLeft('details', '=', 'hello');
    }
    /**
     * @test
     */
    public function testLeft01() {
        $schema = new MSSQLTestSchema();
        $schema->table('users_tasks')->select()->whereLeft('details', 2, '=', 'hello');
        $this->assertEquals("select * from [users_tasks] where left([users_tasks].[details], 2) = 'hello'", $schema->getLastQuery());
    }
    /**
     * @test
     */
    public function testLeft02() {
        $schema = new MSSQLTestSchema();
        $schema->table('users_tasks')->select()->whereLeft('details', 8, '*', 'good');
        $this->assertEquals("select * from [users_tasks] where left([users_tasks].[details], 8) = 'good'", $schema->getLastQuery());
    }
    /**
     * @test
     */
    public function testLeft03() {
        $schema = new MSSQLTestSchema();
        $schema->table('users_tasks')->select()->whereLeft('details', 8, '!=', 'good');
        $this->assertEquals("select * from [users_tasks] where left([users_tasks].[details], 8) != 'good'", $schema->getLastQuery());
    }
    /**
     * @test
     */
    public function testLeft04() {
        $schema = new MSSQLTestSchema();
        $schema->table('users_tasks')->select()->whereLeft('details', 8, 'in', 'good');
        $this->assertEquals("select * from [users_tasks] where left([users_tasks].[details], 8) in('good')", $schema->getLastQuery());
    }
    /**
     * @test
     */
    public function testLeft05() {
        $schema = new MSSQLTestSchema();
        $q = $schema->getQueryGenerator();
        $q->table('users_tasks')->select()
                ->whereLeft('details', 8, 'not in', ['good']);
        $this->assertEquals("select * from [users_tasks] where left([users_tasks].[details], 8) not in('good')", $schema->getLastQuery());
        $q->andWhere('user-id', '=', 9);
        $this->assertEquals("select * from [users_tasks] where left([users_tasks].[details], 8) not in('good') and [users_tasks].[user_id] = 9", $schema->getLastQuery());
    }
    /**
     * @test
     */
    public function testLeft06() {
        $schema = new MSSQLTestSchema();
        $schema->table('users_tasks')->select()->whereLeft('details', 8, 'not in', ['good', 'bad']);
        $this->assertEquals("select * from [users_tasks] where left([users_tasks].[details], 8) not in('good', 'bad')", $schema->getLastQuery());
    }
    /**
     * @test
     */
    public function testLeft07() {
        $this->expectException(\InvalidArgumentException::class);
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
        $this->assertEquals('select * from [users_tasks] where ([users_tasks].[created_on] between \'2020-01-01 00:00:00\' and \'2020-04-01 00:00:00\')', $q->getQuery());
        $q->whereLeft('details', 5, '=', 'ok ok', 'or');
        $this->assertEquals('select * from [users_tasks] where ([users_tasks].[created_on] between \'2020-01-01 00:00:00\' and \'2020-04-01 00:00:00\') or left([users_tasks].[details], 5) = \'ok ok\'', $q->getQuery());
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
        $this->assertEquals("select * from [users] where [users].[first_name] like '%Ibra%'", $schema->getLastQuery()); 
    }
    /**
     * @test
     */
    public function testLike02() {
        $schema = new MSSQLTestSchema();
        $schema->table('users')->select()->whereNotLike('first-name', '%Ibra%');
        $this->assertEquals("select * from [users] where [users].[first_name] not like '%Ibra%'", $schema->getLastQuery()); 
    }
    /**
     * @test
     */
    public function testLike03() {
        $schema = new MSSQLTestSchema();
        $this->expectException(DatabaseException::class);
        $schema->table('users_tasks')->select()->whereNotLike('first-naome', '%Ibra%');
    }
    /**
     * @test
     */
    public function testRight01() {
        $schema = new MSSQLTestSchema();
        $schema->table('users_tasks')->select()->whereRight('details', 2, '=', 'hello');
        $this->assertEquals("select * from [users_tasks] where right([users_tasks].[details], 2) = 'hello'", $schema->getLastQuery());
    }
    /**
     * @test
     */
    public function testRight02() {
        $schema = new MSSQLTestSchema();
        $schema->table('users_tasks')->select()->whereRight('details', 8, '*', 'good');
        $this->assertEquals("select * from [users_tasks] where right([users_tasks].[details], 8) = 'good'", $schema->getLastQuery());
    }
    /**
     * @test
     */
    public function testRight03() {
        $schema = new MSSQLTestSchema();
        $schema->table('users_tasks')->select()->whereRight('details', 8, '!=', 'good');
        $this->assertEquals("select * from [users_tasks] where right([users_tasks].[details], 8) != 'good'", $schema->getLastQuery());
    }
    /**
     * @test
     */
    public function testRight04() {
        $schema = new MSSQLTestSchema();
        $schema->table('users_tasks')->select()->whereRight('details', 8, 'in', 'good');
        $this->assertEquals("select * from [users_tasks] where right([users_tasks].[details], 8) in('good')", $schema->getLastQuery());
    }
    /**
     * @test
     */
    public function testRight05() {
        $schema = new MSSQLTestSchema();
        $schema->table('users_tasks')->select()->whereRight('details', 8, 'not in', ['good']);
        $this->assertEquals("select * from [users_tasks] where right([users_tasks].[details], 8) not in('good')", $schema->getLastQuery());
    }
    /**
     * @test
     */
    public function testRight06() {
        $schema = new MSSQLTestSchema();
        $schema->table('users_tasks')->select()->whereRight('details', 8, 'not in', ['good', 'bad']);
        $this->assertEquals("select * from [users_tasks] where right([users_tasks].[details], 8) not in('good', 'bad')", $schema->getLastQuery());
    }
    /**
     * @test
     */
    public function testRight07() {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The value must be of type string since the condition is \'!=\'.');
        $schema = new MSSQLTestSchema();
        $schema->table('users_tasks')->select()->whereLeft('details', 8, '!=', ['good', 'bad']);
    }
    /**
     * @test
     */
    public function testSelectWithWhere000() {
        $schema = new MSSQLTestSchema();
        $bulder = $schema->table('users')->select()->where('id', '=', 66);
        $this->assertEquals('select * from [users] where [users].[id] = 66', $schema->getLastQuery());
        $bulder->groupBy('first-name');
        $this->assertEquals('select * from [users] where [users].[id] = 66 group by [users].[first_name]', $schema->getLastQuery());
        $bulder->groupBy(['first-name','last-name']);
        $this->assertEquals('select * from [users] where [users].[id] = 66 group by [users].[first_name], [users].[last_name]', $schema->getLastQuery());
        $bulder->orderBy(['last-name'=>'a']);
        $this->assertEquals('select * from [users] where [users].[id] = 66 group by [users].[first_name], [users].[last_name] order by [users].[last_name] asc', $schema->getLastQuery());
    }
    /**
     * @test
     */
    public function testSelectWithWhere001() {
        $schema = new MSSQLTestSchema();
        $schema->table('users')->select()->where(
            $schema->where('id', '=', 7)
        );
        $this->assertEquals('select * from [users] where [users].[id] = 7', $schema->getLastQuery());
        $schema->orWhere('first-name', '=', 'Ibrahim');
        $this->assertEquals('select * from [users] where [users].[id] = 7 or [users].[first_name] = \'Ibrahim\'', $schema->getLastQuery());
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
                $q->where('id', '=', 7)
            )
        );
        $this->assertEquals('select * from [users] where [users].[id] = 7', $schema->getLastQuery());
        $schema->orWhere('first-name', '=', 'Ibrahim');
        $this->assertEquals('select * from [users] where [users].[id] = 7 or [users].[first_name] = \'Ibrahim\'', $schema->getLastQuery());
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
            $q->where('id', '=', 7)
            ->orWhere('id', '=', 8)//This will create one big cond
        //4 
        )->orWhere(
                //3 Cond f_name = Ibr
            $q->where('first-name', '=', 'Ibrahim')
            ->andWhere('last-name', '=', 'BinAlshikh')//This will create one big cond
        );
        '((id = 7) and f_n = ibrahim)';
        $this->assertEquals('select * from [users] '
                . 'where (([users].[id] = 7 or [users].[id] = 8) '
                . 'or ([users].[first_name] = \'Ibrahim\' and [users].[last_name] = \'BinAlshikh\'))', $schema->getLastQuery());
    }
    /**
     * @test
     */
    public function testSelectWithWhere004() {
        $schema = new MSSQLTestSchema();
        $q = $schema->table('users')->select();
        
        $q->where(
            $q->where('id', '=', 7)
        )->where('id', '=', 8)
         ->orWhere('id', '=', 88);
        
        $this->assertEquals('select * from [users] where [users].[id] = 7 and [users].[id] = 8 or [users].[id] = 88', $schema->getLastQuery());
        $schema->orWhere('first-name', '=', 'Ibrahim');
        
        $this->assertEquals('select * from [users] where [users].[id] = 7 '
                . 'and [users].[id] = 8 or [users].[id] = 88 or [users].[first_name] = \'Ibrahim\'', $schema->getLastQuery());
    }
    /**
     * @test
     */
    public function testSelectWithWhere002() {
        $schema = new MSSQLTestSchema();
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
        $this->assertEquals('select * from [users] '
                . 'where [users].[id] = 7 '
                . 'or [users].[first_name] = \'Ibrahim\' and [users].[last_name] = \'BinAlshikh\'', $schema->getLastQuery());
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
                $q->where('id', '=', 7)
            //3 Expr (id = 7) and id = 8
            )->where('id', '=', 8)
        );
        // Expr(Expr(Cond) Cond)
        $this->assertEquals('select * from [users] where [users].[id] = 7 and [users].[id] = 8', $schema->getLastQuery());
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
                $q->where('id', '=', 7)
            //3 Expr (id = 7) and id = 8
            )->where('id', '=', 8)
             ->where('id', '=', 100)
             ->where('first-name', '=', 44)
        );
        $this->assertEquals('select * from [users] where ([users].[id] = 7 and ([users].[id] = 8 and [users].[id] = 100 and [users].[first_name] = \'44\'))', $schema->getLastQuery());
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
                $q->where('id', '=', 7)
            )->where(
                $q->where('id', '=', 8)
            )
        );
        $this->assertEquals('select * from [users] where [users].[id] = 7 and [users].[id] = 8', $schema->getLastQuery());
        $schema->orWhere('first-name', '=', 'Ibrahim');
        $this->assertEquals('select * from [users] where [users].[id] = 7 and [users].[id] = 8 or [users].[first_name] = \'Ibrahim\'', $schema->getLastQuery());
    }
    /**
     * @test
     */
    public function testSelectWithWhere006() {
        $schema = new MSSQLTestSchema();
        $q = $schema->table('users')->select();
        
        $q->where('id', '=', 2)
                ->where('id', '!=', 9, 'or')
                ->orWhere('id', '=', 10)
                ->andWhere('id', '=', 30)
                ->andWhere('first-name', '!=', 'Ibr');
        $this->assertEquals('select * from [users] where '
                . '[users].[id] = 2 or '
                . '[users].[id] != 9 or '
                . '[users].[id] = 10 and '
                . '[users].[id] = 30 and '
                . '[users].[first_name] != \'Ibr\'', $schema->getLastQuery());
    }
    /**
     * @test
     */
    public function testSelectWithWhere007() {
        $schema = new MSSQLTestSchema();
        $q = $schema->table('users')->select();
        
        $q->where($q->where('id', '=', 2)
                ->where('id', '!=', 9, 'or')
                ->orWhere('id', '=', 10)
                ->andWhere('id', '=', 30)
                ->andWhere('first-name', '!=', 'Ibr'));
        $this->assertEquals('select * from [users] where ('
                . '[users].[id] = 2 or '
                . '[users].[id] != 9 or '
                . '[users].[id] = 10 and '
                . '[users].[id] = 30 and '
                . '[users].[first_name] != \'Ibr\')', $schema->getLastQuery());
    }
    /**
     * @test
     */
    public function testSelectWithWhere008() {
        $schema = new MSSQLTestSchema();
        $q = $schema->table('users')->select();
        
        $q->where($q->where($q->where('id', '=', 2)
                ->where('id', '!=', 9, 'or')
                ->orWhere('id', '=', 10)
                ->andWhere('id', '=', 30)
                ->andWhere('first-name', '!=', 'Ibr')));
        $this->assertEquals('select * from [users] where ('
                . '[users].[id] = 2 or '
                . '[users].[id] != 9 or '
                . '[users].[id] = 10 and '
                . '[users].[id] = 30 and '
                . '[users].[first_name] != \'Ibr\')', $schema->getLastQuery());
    }
    

    /**
     * @test
     * 
     * @return MSSQLTestSchema Description
     */
    public function testSetConnection00() {
        $connInfo = new ConnectionInfo('mssql','sa', '1234567890', 'testing_db', 'localhost\\SQLEXPRESS');
        $conn = new MSSQLConnection($connInfo);
        $schema = new MSSQLTestSchema();
        $schema->setConnection($conn);
        $this->assertTrue(true);
        return $schema;
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
        $this->assertEquals("update [users_tasks] set [details] = 'OKKKKKKKk', [last_updated] = '$date'", $schema->getLastQuery());
    }
    /**
     * @test
     */
    public function testUpdate01() {
        $schema = new MSSQLTestSchema();
        $q = $schema->table('users_tasks');
        
        $q->update([
            'details' => 'OKKKKKKKk'
        ])->where('task-id', '=', 77);
        $date = date('Y-m-d H:i:s');
        $this->assertEquals("update [users_tasks] set [details] = 'OKKKKKKKk', [last_updated] = '$date' where [users_tasks].[task_id] = 77", $schema->getLastQuery());
        $q->andWhere('user-id', '=', 6);
        $this->assertEquals("update [users_tasks] set [details] = 'OKKKKKKKk', [last_updated] = '$date' "
                . "where [users_tasks].[task_id] = 77 and [users_tasks].[user_id] = 6", $schema->getLastQuery());
    }
     /**
     * @test
     */
    public function testUpdate02() {
        $schema = new MSSQLTestSchema();
        $q = $schema->table('users_tasks');
        
        $q->update([
            'details' => null
        ])->where('task-id', '=', 77);
        $date = date('Y-m-d H:i:s');
        $this->assertEquals("update [users_tasks] set [details] = null, [last_updated] = '$date' where [users_tasks].[task_id] = 77", $schema->getLastQuery());
        $q->andWhere('user-id', '=', 6);
        $this->assertEquals("update [users_tasks] set [details] = null, [last_updated] = '$date' "
                . "where [users_tasks].[task_id] = 77 and [users_tasks].[user_id] = 6", $schema->getLastQuery());
    }
     /**
     * @test
     */
    public function testUpdate03() {
        $schema = new MSSQLTestSchema();
        $q = $schema->table('users_tasks');
        
        $q->update([
            'details' => null
        ])->where('last-updated', '=', '2021-07-13');
        $date = date('Y-m-d H:i:s');
        $this->assertEquals("update [users_tasks] set [details] = null, "
                . "[last_updated] = '$date' where [users_tasks].[last_updated] = '2021-07-13 00:00:00'", $schema->getLastQuery());
        $q->andWhere('user-id', '=', 6);
        $this->assertEquals("update [users_tasks] set [details] = null, [last_updated] = '$date' "
                . "where [users_tasks].[last_updated] = '2021-07-13 00:00:00' and [users_tasks].[user_id] = 6", $schema->getLastQuery());
    }
    /**
     * @test
     */
    public function testWhereBetween00() {
        $schema = new MSSQLTestSchema();
        $schema->table('users_tasks')->select()->whereBetween('task-id', 0, 33);
        $this->assertEquals('select * from [users_tasks] where ([users_tasks].[task_id] between 0 and 33)', $schema->getLastQuery());
        $schema->andWhere('user-id', '=', 88);
        $this->assertEquals('select * from [users_tasks] where ([users_tasks].[task_id] between 0 and 33) and [users_tasks].[user_id] = 88', $schema->getLastQuery());
    }
    /**
     * @test
     */
    public function testWhereBetween01() {
        $schema = new MSSQLTestSchema();
        $this->expectException(DatabaseException::class);
        $schema->table('users_tasks')->select()->whereBetween('task-idx', 0, 33);
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
        $this->assertEquals('select * from [users_tasks] where ([users_tasks].[task_id] not between 0 and 33)', $schema->getLastQuery());
        $schema->getQueryGenerator()->andWhere('user-id', '=', 88);
        $this->assertEquals('select * from [users_tasks] where ([users_tasks].[task_id] not between 0 and 33) and [users_tasks].[user_id] = 88', $schema->getLastQuery());
    }
    /**
     * @test
     */
    public function testWhereBetween04() {
        $schema = new MSSQLTestSchema();
        $schema->table('users_tasks')->select()->whereNotBetween('task-id', 0, 33);
        $this->assertEquals('select * from [users_tasks] where ([users_tasks].[task_id] not between 0 and 33)', $schema->getLastQuery());
    }
    /**
     * @test
     */
    public function testWhereBetween05() {
        $schema = new MSSQLTestSchema();
        $schema->table('users_tasks')->select()->whereBetween('last-updated', '2020-01-02 01:30:00', '2020-02-15');
        $this->assertEquals('select * from [users_tasks] where ([users_tasks].[last_updated] between \'2020-01-02 01:30:00\' and \'2020-02-15 00:00:00\')', $schema->getLastQuery());
    }
    /**
     * @test
     */
}
