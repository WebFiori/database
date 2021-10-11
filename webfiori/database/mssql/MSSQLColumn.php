<?php
namespace webfiori\database\mssql;

use webfiori\database\Column;
/**
 * A class that represents a column in MSSQL table.
 *
 * @author Ibrahim
 * 
 * @version 1.0
 */
class MSSQLColumn extends Column {
    public function __construct($name) {
        parent::__construct($name);
        $this->setSupportedTypes([
            'char',
            'nchar',
            'varchar',
            'nvarchar',
            'binary',
            'varbinary',
            'date',
            'datetime2',
            'time',
            'int',
            'money',
            'bit',
            'decimal',
            'float'
        ]);
    }
    public function asString() {
        
    }

    public function cleanValue($val) {
        
    }

}
