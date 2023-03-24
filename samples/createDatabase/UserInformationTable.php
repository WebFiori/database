<?php

use webfiori\database\mysql\MySQLTable;

class UserInformationTable extends MySQLTable {
    public function __construct() {
        parent::__construct('users_information');

        $this->addColumns([
            'id' => [
                'type' => 'int',
                'size' => 5,
                'primary' => true,
                'auto-inc' => true
            ],
            'first-name' => [
                'type' => 'varchar',
                'size' => 15
            ],
            'last-name' => [
                'type' => 'varchar',
                'size' => 15
            ],
            'email' => [
                'type' => 'varchar',
                'size' => 128
            ]
        ]);
    }
}
