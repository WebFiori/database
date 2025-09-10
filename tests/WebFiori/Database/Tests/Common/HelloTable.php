<?php

namespace WebFiori\Database\tests\common;

use WebFiori\Database\mysql\MySQLTable;
use WebFiori\Database\mysql\MySQLColumn;
/**
 * Description of HelloTable
 *
 * @author Ibrahim
 */
class HelloTable extends MySQLTable {
    public function __construct() {
        parent::__construct('hello');
        $this->addColumn('user_id', new MySQLColumn('user_id', 'int', 11));
        $this->addColumn('username', new MySQLColumn('username', 'varchar', 15));
        $this->addColumn('pass', new MySQLColumn('password', 'varchar', 64));
    }
}
