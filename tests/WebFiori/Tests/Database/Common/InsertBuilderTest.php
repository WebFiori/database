<?php

namespace WebFiori\Tests\Database\Common;

use PHPUnit\Framework\TestCase;
use WebFiori\Database\ColOption;
use WebFiori\Database\DataType;
use WebFiori\Database\MsSql\MSSQLInsertBuilder;
use WebFiori\Database\MsSql\MSSQLTable;
use WebFiori\Database\MySql\MySQLInsertBuilder;
use WebFiori\Database\MySql\MySQLTable;

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
            'age' => [
                ColOption::TYPE => 'decimal'
            ],
            'created-on' => [
                ColOption::TYPE => DataType::TIMESTAMP,
                ColOption::DEFAULT => 'now()',
            ],
            'is-active' => [
                ColOption::TYPE => DataType::BOOL,
                ColOption::DEFAULT => true
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
            'age' => [
                ColOption::TYPE => 'decimal'
            ],
            'created-on' => [
                ColOption::TYPE => DataType::TIMESTAMP,
                ColOption::DEFAULT => 'now()',
            ],
            'is-active' => [
                ColOption::TYPE => DataType::BOOL,
                ColOption::DEFAULT => true
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
                ColOption::TYPE => DataType::INT,
                ColOption::SIZE => 11,
                ColOption::PRIMARY => true,
                ColOption::IDENTITY => true
            ],
            'email' => [
                ColOption::TYPE => DataType::VARCHAR,
                ColOption::SIZE => 256,
            ],
            'username' => [
                ColOption::TYPE => DataType::VARCHAR,
                ColOption::SIZE => 20,
            ],
            'password' => [
                ColOption::TYPE => DataType::VARCHAR,
                ColOption::SIZE => 256
            ],
            'age' => [
                ColOption::TYPE => 'decimal'
            ],
            'created-on' => [
                ColOption::TYPE => DataType::DATETIME2,
                ColOption::DEFAULT => 'now()',
            ],
            'is-active' => [
                ColOption::TYPE => DataType::BOOL,
                ColOption::DEFAULT => true
            ]
        ]);
        
        $helper = new MSSQLInsertBuilder($table, [
            'user-id' => 1
        ]);
        
        $this->assertEquals('insert into [users] ([user_id], [created_on], [is_active]) values (?, ?, ?);', $helper->getQuery());
        $this->assertEquals([
            1,
            date('Y-m-d H:i:s'),
            1
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
            1,
            'test@example.com',
            'warrior',
            'abcd',
            33.3,
            date('Y-m-d H:i:s'),
            1
        ], $helper->getQueryParams());
    }
}
