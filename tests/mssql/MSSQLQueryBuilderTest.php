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
     * 
     * @return MSSQLTestSchema Description
     */
    public function testSetConnection00() {
        $connInfo = new ConnectionInfo('mssql','sa', '1234567890', 'testing_db', 'localhost\SQLEXPRESS');
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
}
