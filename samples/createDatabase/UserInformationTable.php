<?php

use webfiori\database\ColOption;
use webfiori\database\mysql\MySQLTable;

class UserInformationTable extends MySQLTable {
    public function __construct() {
        parent::__construct('users_information');

        $this->addColumns([
            'id' => [
                ColOption::TYPE => 'int',
                ColOption::SIZE => 5,
                ColOption::PRIMARY => true,
                'auto-inc' => true
            ],
            'first-name' => [
                ColOption::TYPE => 'varchar',
                ColOption::SIZE => 15
            ],
            'last-name' => [
                ColOption::TYPE => 'varchar',
                ColOption::SIZE => 15
            ],
            'email' => [
                ColOption::TYPE => 'varchar',
                ColOption::SIZE => 128
            ]
        ]);
    }
}
