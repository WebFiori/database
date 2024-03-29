<?php

use webfiori\database\ColOption;
use webfiori\database\DataType;
use webfiori\database\mysql\MySQLTable;

class UserInformationTable extends MySQLTable {
    public function __construct() {
        parent::__construct('users_information');

        $this->addColumns([
            'id' => [
                ColOption::TYPE => DataType::INT,
                ColOption::SIZE => 5,
                ColOption::PRIMARY => true,
                'auto-inc' => true
            ],
            'first-name' => [
                ColOption::TYPE => DataType::VARCHAR,
                ColOption::SIZE => 15
            ],
            'last-name' => [
                ColOption::TYPE => DataType::VARCHAR,
                ColOption::SIZE => 15
            ],
            'email' => [
                ColOption::TYPE => DataType::VARCHAR,
                ColOption::SIZE => 128
            ]
        ]);
    }
}
