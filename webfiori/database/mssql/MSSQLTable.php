<?php
namespace webfiori\database\mssql;
use webfiori\database\Table;
/**
 * A class that represents MSSQL table.
 *
 * @author Ibrahim
 * 
 * @version 1.0
 */
class MSSQLTable extends Table {
    public function __construct($name = 'new_table') {
        parent::__construct($name);
    }
    public function toSQL() {
        
    }

}
