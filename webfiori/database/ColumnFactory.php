<?php
namespace webfiori\database;

use webfiori\database\DatabaseException;
use webfiori\database\mssql\MSSQLColumn;
use webfiori\database\mysql\MySQLColumn;

/**
 * A factory class for creating column objects.
 *
 * @author Ibrahim
 */
class ColumnFactory {
    public static function create($database, $name, $options = []) {
        if (!in_array($database, ConnectionInfo::SUPPORTED_DATABASES)) {
            throw new DatabaseException('Not support database: '.$database);
        }
        if ($database == 'mssql') {
            $col = new MSSQLColumn($name);
        } else if ($database == 'mysql') {
            $col = new MySQLColumn($name);
        }
        if (isset($options['datatype'])) {
            $datatype = $options['datatype'];
        } else {
            if (isset($options['type'])) {
                $datatype = $options['type'];
            } else {
                $datatype = $col instanceof MySQLColumn ? 'varchar' : 'nvarchar';
            }
        }
        $col->setDatatype($datatype);
        $size = isset($options['size']) ? intval($options['size']) : 1;
        $col->setSize($size);

        self::_primaryCheck($col, $options);
        self::_extraAttrsCheck($col, $options);
        
        return $col;
    }
    
    /**
     * 
     * @param MSSQLColumn $col
     * @param array $options
     */
    private static function _extraAttrsCheck(&$col, $options) {
        $scale = isset($options['scale']) ? intval($options['scale']) : 2;
        $col->setScale($scale);

        if (isset($options['default'])) {
            $col->setDefault($options['default']);
        }

        if (isset($options['is-unique'])) {
            $col->setIsUnique($options['is-unique']);
        }
        
        if (isset($options['unique'])) {
            $col->setIsUnique($options['unique']);
        }

        //the 'not null' or 'null' must be specified or it will cause query 
        //or it will cause query error.
        $isNull = isset($options['is-null']) ? $options['is-null'] : false;
        $col->setIsNull($isNull);

        if (isset($options['auto-update'])) {
            $col->setAutoUpdate($options['auto-update']);
        }

        if (isset($options['comment'])) {
            $col->setComment($options['comment']);
        }

        if (isset($options['validator'])) {
            $col->setCustomFilter($options['validator']);
        }
    }
    /**
     * 
     * @param MSSQLColumn $col
     * @param array $options
     */
    private static function _primaryCheck(&$col, $options) {
        $isPrimary = isset($options['primary']) ? $options['primary'] : false;

        if (!$isPrimary) {
            $isPrimary = isset($options['is-primary']) ? $options['is-primary'] : false;
        }
        $col->setIsPrimary($isPrimary);
        
        if ($isPrimary && isset($options['auto-inc']) && $col instanceof MySQLColumn) {
            $col->setIsAutoInc($options['auto-inc']);
            $col->setIsNull(true);
        }
    }
}
