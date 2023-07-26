<?php

namespace webfiori\database\tests\common;

use PHPUnit\Framework\TestCase;
use webfiori\database\mssql\MSSQLInsertBuilder;
use webfiori\database\mssql\MSSQLTable;
use webfiori\database\mysql\MySQLInsertBuilder;
use webfiori\database\mysql\MySQLTable;

/**
 * Description of InsertBuilderTest
 *
 */
class InsertBuilderTest extends TestCase {
    /**
     * @test
     */
    public function test00() {
        $table = new MySQLTable('users');
        $table->addColumns([
            'user-id' => [
                'type' => 'int',
                'size' => 11,
                'primary' => true,
                'auto-inc' => true
            ],
            'email' => [
                'type' => 'varchar',
                'size' => 256,
                'is-unique' => true
            ],
            'username' => [
                'type' => 'varchar',
                'size' => 20,
                'is-unique' => true
            ],
            'password' => [
                'type' => 'varchar',
                'size' => 256
            ],
            'age' => [
                'type' => 'decimal'
            ],
            'created-on' => [
                'type' => 'timestamp',
                'default' => 'now()',
            ],
            'is-active' => [
                'type' => 'bool',
                'default' => true
            ]
        ]);
        
        $helper = new MySQLInsertBuilder($table, [
            'user-id' => 1
        ]);
        
        $this->assertEquals('insert into `users` (`user_id`, `created_on`, `is_active`) values (?, ?, ?);', $helper->getQuery());
        $this->assertEquals([
            'bind' => 'isi',
            'values' => [
                [1,
                date('Y-m-d H:i:s'),
                1]
            ]
        ], $helper->getQueryParams());
        $helper->insert([
            'user-id' => 1,
            'email' => 'test@example.com',
            'username' => 'warrior',
            'password' => 'abcd',
            'age' => 33.3,
        ]);
        
        $this->assertEquals('insert into `users` (`user_id`, `email`, `username`, `password`, `age`, `created_on`, `is_active`) values (?, ?, ?, ?, ?, ?, ?);', $helper->getQuery());
        $this->assertEquals([
            'bind' => 'isssdsi',
            'values' => [
                [1,
                'test@example.com',
                'warrior',
                'abcd',
                33.3,
                date('Y-m-d H:i:s'),
                1]
            ]
        ], $helper->getQueryParams());
    }
    /**
     * @test
     */
    public function test01() {
        $table = new MySQLTable('users');
        $table->addColumns([
            'user-id' => [
                'type' => 'int',
                'size' => 11,
                'primary' => true,
                'auto-inc' => true
            ],
            'email' => [
                'type' => 'varchar',
                'size' => 256,
                'is-unique' => true
            ],
            'username' => [
                'type' => 'varchar',
                'size' => 20,
                'is-unique' => true
            ],
            'password' => [
                'type' => 'varchar',
                'size' => 256
            ],
            'age' => [
                'type' => 'decimal'
            ],
            'created-on' => [
                'type' => 'timestamp',
                'default' => 'now()',
            ],
            'is-active' => [
                'type' => 'bool',
                'default' => true
            ]
        ]);
        
        $helper = new MySQLInsertBuilder($table, [
            'cols' => [
                'user-id'
            ],
            'values' => [
                [1],[3],[4]
            ]
        ]);
        
        $this->assertEquals("insert into `users` (`user_id`, `created_on`, `is_active`)\nvalues\n(?),\n(?),\n(?);", $helper->getQuery());
        $this->assertEquals([
            'bind' => 'isiisiisi',
            'values' => [
                [1,
                date('Y-m-d H:i:s'),
                1],
                [3,
                date('Y-m-d H:i:s'),
                1],
                [4,
                date('Y-m-d H:i:s'),
                1]
            ]
        ], $helper->getQueryParams());
        
    }
    /**
     * @test
     */
    public function test03() {
        $table = new MSSQLTable('users');
        $table->addColumns([
            'user-id' => [
                'type' => 'int',
                'size' => 11,
                'primary' => true,
                'identity' => true
            ],
            'email' => [
                'type' => 'varchar',
                'size' => 256,
            ],
            'username' => [
                'type' => 'varchar',
                'size' => 20,
            ],
            'password' => [
                'type' => 'varchar',
                'size' => 256
            ],
            'age' => [
                'type' => 'decimal'
            ],
            'created-on' => [
                'type' => 'datetime2',
                'default' => 'now()',
            ],
            'is-active' => [
                'type' => 'bool',
                'default' => true
            ]
        ]);
        
        $helper = new MSSQLInsertBuilder($table, [
            'user-id' => 1
        ]);
        
        $this->assertEquals('insert into [users] ([user_id], [created_on], [is_active]) values (?, ?, ?);', $helper->getQuery());
        $this->assertEquals([
            [1, 1, 2, 4],
            [date('Y-m-d H:i:s'), 1, 5, 58734173],
            [1, 1, 2, -7]
        ], $helper->getQueryParams());
        $helper->insert([
            'user-id' => 1,
            'email' => 'test@example.com',
            'username' => 'warrior',
            'password' => 'abcd',
            'age' => 33.3,
        ]);
        
        $this->assertEquals('insert into [users] ([user_id], [email], [username], [password], [age], [created_on], [is_active]) values (?, ?, ?, ?, ?, ?, ?);', $helper->getQuery());
        $encoded = $helper->getQueryParams()[1][2];
        $this->assertEquals([
            [1, 1, 2, 4],
            ['test@example.com', 1, $encoded, 12 ],
            ['warrior', 1, $encoded, 12],
            ['abcd', 1, $encoded, 12],
            [33.3, 1, 3, 3],
            [date('Y-m-d H:i:s'), 1, 5, 58734173],
            [1, 1, 2, -7],
        ], $helper->getQueryParams());
    }
}
