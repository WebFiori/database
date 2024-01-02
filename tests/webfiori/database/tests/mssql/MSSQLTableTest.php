<?php
namespace webfiori\database\tests\mssql;

use PHPUnit\Framework\TestCase;
use webfiori\database\ColOption;
use webfiori\database\DataType;
use webfiori\database\mssql\MSSQLColumn;
use webfiori\database\mssql\MSSQLTable;
use webfiori\database\mysql\MySQLTable;
use webfiori\database\Table;
/**
 * Description of MSSQLTableTest
 *
 * @author Ibrahim
 */
class MSSQLTableTest extends TestCase {
    /**
     * @test
     */
    public function testAddColumn00() {
        $table = new MSSQLTable();
        $this->assertTrue($table->addColumn('new-col', new MSSQLColumn()));
        $this->assertFalse($table->addColumn('new-col-2', new MSSQLColumn()));
        $this->assertTrue($table->addColumn('new-col-2', new MSSQLColumn('col_2', 'varchar')));
        $this->assertFalse($table->addColumn('new-col-2', new MSSQLColumn('col_3', 'varchar')));
        $this->assertTrue($table->addColumn('new col-2', new MSSQLColumn('new col-2', 'varchar')));
        
        return $table;
    }
    /**
     * @test
     */
    public function testAddColumn01() {
        $table = new MSSQLTable();
        $this->assertTrue($table->addColumn(' new-col ', new MSSQLColumn()));
        $this->assertTrue($table->addColumn('valid key', new MSSQLColumn('col_2')));
        $this->assertFalse($table->addColumn('-', new MSSQLColumn('col_2')));
        $this->assertFalse($table->addColumn('--', new MSSQLColumn('col_2')));

        return $table;
    }
    /**
     * @test
     */
    public function testAddColumn02() {
        $table = new MSSQLTable();
        $table->addColumns([
            'id' => new MSSQLColumn('col-01'),
            'name' => new MSSQLColumn('col-02', 'int'),
            'Other Col' => []
        ]);
        $this->assertTrue($table->hasColumnWithKey('id'));
        $this->assertTrue($table->hasColumnWithKey('name'));
        $this->assertTrue($table->hasColumnWithKey('Other Col'));
        $this->assertTrue($table->hasColumn('col-01'));
        $this->assertTrue($table->hasColumn('col-02'));
        $this->assertTrue($table->hasColumn('Other Col'));

        return $table;
    }
    /**
     * @test
     */
    public function testConstructor01() {
        $table = new MSSQLTable('valid_name');
        $this->assertEquals('[valid_name]',$table->getName());
        $this->assertEquals('valid_name',$table->getNormalName());
    }
    /**
     * @test
     */
    public function testConstructor02() {
        $table = new MSSQLTable('    another_Valid_Name    ');
        $this->assertNull($table->getOldName());
        $this->assertEquals('[another_Valid_Name]',$table->getName());
        $table->setName('new-name');
        $this->assertEquals('[another_Valid_Name]', $table->getOldName());
    }
    /**
     * @test
     */
    public function testFK1() {
        $table = new MSSQLTable('users');
        $table->addColumns([
            'user-id' => [
                ColOption::TYPE => DataType::INT,
                ColOption::SIZE => 11,
                ColOption::PRIMARY => true,
                ColOption::AUTO_INCREMENT => true
            ],
            'email' => [
                ColOption::TYPE => DataType::VARCHAR,
                ColOption::SIZE => 256,
                ColOption::UNIQUE => true
            ],
            'username' => [
                ColOption::TYPE => DataType::VARCHAR,
                ColOption::SIZE => 20,
                ColOption::UNIQUE => true
            ],
            'password' => [
                ColOption::TYPE => DataType::VARCHAR,
                ColOption::SIZE => 256
            ],
            'created-on' => [
                ColOption::TYPE => DataType::DATETIME2,
                ColOption::DEFAULT => 'now',
            ],
        ]);
        $table2 = new MSSQLTable('t');
        $table2->addColumns([
            'user-id' => [
                ColOption::TYPE => DataType::INT,
                ColOption::SIZE => 11,
                ColOption::PRIMARY => true,
                ColOption::AUTO_INCREMENT => true
            ],
            'email' => [
                ColOption::TYPE => DataType::VARCHAR,
                ColOption::SIZE => 256,
                ColOption::UNIQUE => true
            ],
        ]);
        $table2->addReference($table, ['user-id', 'email'], 'fk_ok', 'cascade', 'cascade');
        $key = $table2->getForeignKey('fk_ok');
        $this->assertEquals(2, count($key->getSourceCols()));
        $this->assertEquals("if not exists (select * from sysobjects where name='t' and xtype='U')\n"
                . "create table [t] (\n"
                . "    [user_id] [int] not null,\n"
                . "    [email] [varchar](256) not null,\n"
                . "    constraint t_pk primary key clustered([user_id]) on [PRIMARY],\n"
                . "    constraint fk_ok foreign key ([user_id], [email]) references [users] ([user_id], [email]) on update cascade on delete cascade,\n"
                . "    constraint AK_t unique (email)\n"
                . ")\n"
                . "", $table2->toSQL());
    }
    /**
     * @test
     */
    public function testFK2() {
        $table = new MSSQLTable('users');
        $table->addColumns([
            'user-id' => [
                ColOption::TYPE => DataType::INT,
                ColOption::SIZE => 11,
                ColOption::PRIMARY => true,
                ColOption::AUTO_INCREMENT => true
            ],
            'email' => [
                ColOption::TYPE => DataType::VARCHAR,
                ColOption::SIZE => 256,
                ColOption::UNIQUE => true
            ],
            'username' => [
                ColOption::TYPE => DataType::VARCHAR,
                ColOption::SIZE => 20,
                ColOption::UNIQUE => true
            ],
            'password' => [
                ColOption::TYPE => DataType::VARCHAR,
                ColOption::SIZE => 256
            ],
            'created-on' => [
                ColOption::TYPE => DataType::DATETIME2,
                ColOption::DEFAULT => 'now',
            ],
        ]);
        
        $table2 = new MSSQLTable('t');
        $table2->addColumns([
            'user-id-super' => [
                ColOption::TYPE => DataType::INT,
                ColOption::SIZE => 11,
                ColOption::PRIMARY => true,
            ],
            'email-x' => [
                ColOption::TYPE => DataType::VARCHAR,
                ColOption::SIZE => 256,
                ColOption::UNIQUE => true
            ],
        ]);
        $table2->addReference($table, [
            'user-id-super' => 'user-id','email-x' =>  'email'], 'fk_ok', 'cascade', 'cascade');
        $key = $table2->getForeignKey('fk_ok');
        $this->assertEquals(2, count($key->getSourceCols()));
        $this->assertEquals("if not exists (select * from sysobjects where name='t' and xtype='U')\n"
                . "create table [t] (\n"
                . "    [user_id_super] [int] not null,\n"
                . "    [email_x] [varchar](256) not null,\n"
                . "    constraint t_pk primary key clustered([user_id_super]) on [PRIMARY],\n"
                . "    constraint fk_ok foreign key ([user_id_super], [email_x]) references [users] ([user_id], [email]) on update cascade on delete cascade,\n"
                . "    constraint AK_t unique (email_x)\n"
                . ")\n"
                . "", $table2->toSQL());
    }
    public function testFk3() {
        $t1 = new MSSQLTable('users');
        $t1->addColumns([
            'id' => [
                ColOption::TYPE => DataType::INT,
                ColOption::PRIMARY => true,
                ColOption::IDENTITY => true
            ],
            'name' => [
                ColOption::TYPE => DataType::NVARCHAR,
                ColOption::SIZE => 128,
                ColOption::UNIQUE => true
            ],
            'email' => [
                ColOption::TYPE => DataType::VARCHAR,
                ColOption::SIZE => 256,
                ColOption::UNIQUE => true
            ]
        ]);
        $this->assertEquals("if not exists (select * from sysobjects where name='users' and xtype='U')\n"
                . "create table [users] (\n"
                . "    [id] [int] identity(1,1) not null,\n"
                . "    [name] [nvarchar](128) not null,\n"
                . "    [email] [varchar](256) not null,\n"
                . "    constraint users_pk primary key clustered([id]) on [PRIMARY],\n"
                . "    constraint AK_users unique (name, email)\n"
                . ")\n", $t1->toSQL());
        $t2 = new MSSQLTable('locations');
        $t2->addColumns([
            'id' => [
                ColOption::TYPE => DataType::INT,
                ColOption::PRIMARY => true
            ],
            'name' => [
                ColOption::TYPE => DataType::NVARCHAR,
                ColOption::SIZE => 128
            ],
            'added-by' => [
                ColOption::TYPE => DataType::INT,
                ColOption::FK => [
                    ColOption::FK_TABLE => $t1,
                    ColOption::FK_COL => 'id',
                    ColOption::FK_NAME => 'added_by_fk'
                ]
            ]
        ]);
        $this->assertEquals("if not exists (select * from sysobjects where name='locations' and xtype='U')\n"
                . "create table [locations] (\n"
                . "    [id] [int] not null,\n"
                . "    [name] [nvarchar](128) not null,\n"
                . "    [added_by] [int] not null,\n"
                . "    constraint locations_pk primary key clustered([id]) on [PRIMARY],\n"
                . "    constraint added_by_fk foreign key ([added_by]) references [users] ([id]) on update set null on delete set null\n"
                . ")\n", $t2->toSQL());
        $t3 = new MSSQLTable('user_location');
        $t3->addColumns([
            'user-id' => [
                ColOption::TYPE => DataType::INT,
                ColOption::PRIMARY => true
            ],
            'location' => [
                ColOption::TYPE => DataType::INT,
                ColOption::PRIMARY => true
            ]
        ]);
        $t3->addReference($t1, ['user-id' => 'id'], 'user_loc_fk');
        $t3->addReference($t2, ['location' => 'id'], 'loc_fk');
        $this->assertEquals(2, $t3->getForeignKeysCount());
        $this->assertEquals("if not exists (select * from sysobjects where name='user_location' and xtype='U')\n"
                . "create table [user_location] (\n"
                . "    [user_id] [int] not null,\n"
                . "    [location] [int] not null,\n"
                . "    constraint user_location_pk primary key clustered([user_id], [location]) on [PRIMARY],\n"
                . "    constraint user_loc_fk foreign key ([user_id]) references [users] ([id]) on update set null on delete set null,\n"
                . "    constraint loc_fk foreign key ([location]) references [locations] ([id]) on update set null on delete set null\n"
                . ")\n", $t3->toSQL());
    }
    public function testMap00() {
        $table = new MSSQLTable('users');
        $table->addColumns([
            'user-id' => [
                ColOption::TYPE => DataType::INT,
                ColOption::SIZE => 11,
                ColOption::PRIMARY => true,
                ColOption::AUTO_INCREMENT => true
            ],
            'email' => [
                ColOption::TYPE => DataType::VARCHAR,
                ColOption::SIZE => 256,
                ColOption::UNIQUE => true
            ],
            'username' => [
                ColOption::TYPE => DataType::VARCHAR,
                ColOption::SIZE => 20,
                ColOption::UNIQUE => true
            ],
            'password' => [
                ColOption::TYPE => DataType::VARCHAR,
                ColOption::SIZE => 256
            ],
            'created-on' => [
                ColOption::TYPE => DataType::DATETIME2,
                ColOption::DEFAULT => 'now',
            ],
        ]);
        $table2 = new MSSQLTable('t');
        $table2->addColumns([
            'user-id' => [
                ColOption::TYPE => DataType::INT,
                ColOption::SIZE => 11,
                ColOption::PRIMARY => true,
                ColOption::AUTO_INCREMENT => true
            ],
            'email' => [
                ColOption::TYPE => DataType::VARCHAR,
                ColOption::SIZE => 256,
                ColOption::UNIQUE => true
            ],
        ]);
        $table2->addReference($table, ['user-id', 'email'], 'fk_ok', 'cascade', 'cascade');
        $key = $table2->getForeignKey('fk_ok');
        $this->assertEquals(2, count($key->getSourceCols()));
        $this->assertEquals("if not exists (select * from sysobjects where name='t' and xtype='U')\n"
                . "create table [t] (\n"
                . "    [user_id] [int] not null,\n"
                . "    [email] [varchar](256) not null,\n"
                . "    constraint t_pk primary key clustered([user_id]) on [PRIMARY],\n"
                . "    constraint fk_ok foreign key ([user_id], [email]) references [users] ([user_id], [email]) on update cascade on delete cascade,\n"
                . "    constraint AK_t unique (email)\n"
                . ")\n"
                . "", $table2->toSQL());
        
        $mappedInstance = Table::map('mysql', $table2);
        $this->assertTrue($mappedInstance instanceof MySQLTable);
        $this->assertEquals('`t`', $mappedInstance->getName());
        $this->assertEquals("create table if not exists `t` (\n"
                . "    `user_id` int not null unique,\n"
                . "    `email` varchar(256) not null unique collate utf8mb4_unicode_520_ci,\n"
                . "    constraint `t_pk` primary key (`user_id`),\n"
                . "    constraint `fk_ok` foreign key (`user_id`, `email`) references `users` (`user_id`, `email`) on update cascade on delete cascade\n"
                . ")\n"
                . "engine = InnoDB\n"
                . "default charset = utf8mb4\n"
                . "collate = utf8mb4_unicode_520_ci;", $mappedInstance->toSQL());
    }
    /**
     * 
     * @test
     */
    public function testGetColByIndex() {
        $table = new MSSQLTable();
        $table->addColumns([
            'user-id' => [
                ColOption::TYPE => DataType::INT,
                ColOption::SIZE => 11,
                ColOption::PRIMARY => true
            ],
            'username' => [
                ColOption::SIZE => 20,
                ColOption::UNIQUE => true
            ],
            'email' => [
                ColOption::SIZE => 150,
                ColOption::UNIQUE => true
            ],
            'password' => [
                ColOption::SIZE => 64
            ]
        ]);
        $this->assertEquals(4, $table->getColsCount());
        $col00 = $table->getColByIndex(0);
        $this->assertEquals('[user_id]',$col00->getName());
        $this->assertEquals('int',$col00->getDatatype());
        $this->assertEquals(11,$col00->getSize());
        $this->assertTrue($col00->isPrimary());
        $this->assertEquals(1, $table->getPrimaryKeyColsCount());
        
        $col01 = $table->getColByIndex(2);
        $this->assertEquals('mixed',$col01->getDatatype());
        $this->assertEquals(150,$col01->getSize());
        $this->assertFalse($col01->isPrimary());
        $this->asserttrue($col01->isUnique());

        $col02 = $table->getColByIndex(6);
        $this->assertNull($col02);
    }
    /**
     * @test
     */
    public function testIdentity00() {
        $t1 = new MSSQLTable('users');
        $t1->addColumns([
            'id' => [
                ColOption::TYPE => DataType::INT,
                ColOption::PRIMARY => true,
                ColOption::IDENTITY => true
            ],
            'name' => [
                ColOption::TYPE => DataType::NVARCHAR,
                ColOption::SIZE => 128,
                ColOption::UNIQUE => true
            ],
            'email' => [
                ColOption::TYPE => DataType::VARCHAR,
                ColOption::SIZE => 256,
                ColOption::UNIQUE => true
            ],
            'age' => [
                ColOption::TYPE => DataType::INT,
                ColOption::IDENTITY => true
            ]
        ]);
        $this->assertEquals("if not exists (select * from sysobjects where name='users' and xtype='U')\n"
                . "create table [users] (\n"
                . "    [id] [int] identity(1,1) not null,\n"
                . "    [name] [nvarchar](128) not null,\n"
                . "    [email] [varchar](256) not null,\n"
                . "    constraint users_pk primary key clustered([id]) on [PRIMARY],\n"
                . "    constraint AK_users unique (name, email)\n"
                . ")\n", $t1->toSQL());
    }
    /**
     * 
     * @param MSSQLTable $table
     * @depends testAddColumn00
     */
    public function testHasCol00($table) {
        $this->assertTrue($table->hasColumnWithKey('new-col'));
        $this->assertTrue($table->hasColumnWithKey(' new-col '));
        $this->assertTrue($table->hasColumnWithKey('new-col-2'));
        $this->assertTrue($table->hasColumnWithKey('new col-2'));
        $col = $table->getColByKey('new col-2');
        $this->assertEquals('[new col-2]', $col->getName());
    }
    /**
     * 
     * @param MySQLTable $table
     * @depends testAddColumn00
     */
    public function testHasCol01($table) {
        $this->assertTrue($table->hasColumnWithKey('new-col'));
        $this->assertFalse($table->hasColumnWithKey('invalid key'));
        
    }
    /**
     * @test
     */
    public function testPrimaryKey00() {
        $table = new MSSQLTable('hello');
        $table->addColumns([
            'id-col'=>[
                ColOption::PRIMARY => true,
                ColOption::SIZE => 3
            ]
        ]);
        $this->assertEquals(1, $table->getPrimaryKeyColsCount());
        return $table;
    }
    /**
     * @test
     * @param MSSQLTable $table
     * @depends testPrimaryKey00
     */
    public function testPrimaryKey01($table) {
        $table->addColumns([
            'id-col-2'=>[
                ColOption::PRIMARY => true
            ]
        ]);
        $this->assertFalse($table->getColByKey('id-col')->isUnique());
        $this->assertFalse($table->getColByKey('id-col-2')->isUnique());
        $this->assertEquals(2, $table->getPrimaryKeyColsCount());
        return $table;
    }
    /**
     * @test
     */
    public function testRemoveColFromRef00() {
        $table = new MSSQLTable('active_or_not');
        $table->addColumns([
            'user-id' => [
                ColOption::SIZE => 15
            ],
            'username' => [
                ColOption::SIZE => 50
            ],
            'is-active' => [
                ColOption::TYPE => DataType::BOOL
            ]
        ]);
        $table2 = new MSSQLTable('user_info');
        $table2->addColumns([
            'user-id' => [
                ColOption::SIZE => 15
            ],
            'first-name' => [
                ColOption::SIZE => '50'
            ],
            'last-name' => [
                ColOption::SIZE => '50'
            ]
        ]);
        $table2->addReference($table, [
            'user-id',
            'first-name' => 'username'
            ], 'hello_fk');
        $key = $table2->getForeignKey('hello_fk');
        $this->assertEquals(2, count($key->getOwnerCols()));
        $this->assertFalse($key->removeReference('username'));
        $this->assertTrue($key->removeReference('first-name'));
        $this->assertEquals(1, count($table2->getForeignKey('hello_fk')->getSourceCols()));
    }
    /**
     * @test
     */
    public function testRemoveRef00() {
        $table = new MSSQLTable();
        $table->addColumns([
            'user-id' => [
                ColOption::SIZE => 15
            ],
            'is-active' => [
                ColOption::TYPE => DataType::BOOL
            ]
        ]);
        $this->assertNull($table->removeReference('not-exist'));
    }
    /**
     * @test
     */
    public function testRemoveRef01() {
        $table = new MSSQLTable('active_or_not');
        $table->addColumns([
            'user-id' => [
                ColOption::SIZE => 15
            ],
            'is-active' => [
                ColOption::TYPE => DataType::BOOL
            ]
        ]);
        $table2 = new MSSQLTable('user_info');
        $table2->addColumns([
            'user-id' => [
                ColOption::SIZE => 15
            ],
            'first-name' => [
                ColOption::SIZE => '50'
            ],
            'last-name' => [
                ColOption::SIZE => '50'
            ]
        ]);
        $table->addReference($table2, ['user-id'], 'hello_fk');
        $this->assertEquals(1, $table->getForeignKeysCount());
        $this->assertNull($table->removeReference('not-exist'));
        $obj = $table->removeReference('hello_fk');
        $this->assertEquals('hello_fk', $obj->getKeyName());
        $this->assertEquals(0, $table->getForeignKeysCount());
    }
    /**
     * @test
     */
    public function testWithBoolCol00() {
        $table = new MSSQLTable();
        $table->addColumns([
            'user-id' => [
                ColOption::SIZE => 15
            ],
            'is-active' => [
                ColOption::TYPE => DataType::BOOL
            ]
        ]);
        $this->assertEquals('boolean',$table->getColByKey('is-active')->getDatatype());
    }
}
