<?php

use WebFiori\Database\MySql\MySQLTable;
use WebFiori\Database\DataType;
use WebFiori\Database\ColOption;

/**
 * A custom table class that extends MySQLTable
 */
class UserTable extends MySQLTable {
    
    public function __construct() {
        parent::__construct('users_extended');
        
        // Add columns using the fluent interface
        $this->addColumns([
            'id' => [
                ColOption::TYPE => DataType::INT,
                ColOption::SIZE => 11,
                ColOption::PRIMARY => true,
                ColOption::AUTO_INCREMENT => true
            ],
            'username' => [
                ColOption::TYPE => DataType::VARCHAR,
                ColOption::SIZE => 50,
                ColOption::NULL => false
            ],
            'email' => [
                ColOption::TYPE => DataType::VARCHAR,
                ColOption::SIZE => 150,
                ColOption::NULL => false
            ],
            'full_name' => [
                ColOption::TYPE => DataType::VARCHAR,
                ColOption::SIZE => 100
            ],
            'is_active' => [
                ColOption::TYPE => DataType::BOOL,
                ColOption::DEFAULT => true
            ],
            'created_at' => [
                ColOption::TYPE => DataType::TIMESTAMP,
                ColOption::DEFAULT => 'current_timestamp'
            ]
        ]);
    }
}
