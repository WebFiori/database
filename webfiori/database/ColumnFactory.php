<?php
/**
 * This file is licensed under MIT License.
 * 
 * Copyright (c) 2019 Ibrahim BinAlshikh
 * 
 * For more information on the license, please visit: 
 * https://github.com/WebFiori/.github/blob/main/LICENSE
 * 
 */
namespace webfiori\database;

use webfiori\database\mssql\MSSQLColumn;
use webfiori\database\mysql\MySQLColumn;

/**
 * A factory class for creating column objects.
 *
 * @author Ibrahim
 */
class ColumnFactory {
    /**
     * Creates new instance class which represents a column in a database table.
     * 
     * @param string $database The name of DBMS that the column will be created
     * for. Supported DBMSs are 'mysql' and 'mssql'.
     * 
     * @param string $name The name of the column as it appears in the database.
     * 
     * @param array $options An associative array of options which can be used to
     * customize the column. Supported options are:
     * 
     * <ul>
     * <li>type: Datatype of the column.</li>
     * <li>size: If datatype supports size, this can be used to specify the size.</li>
     * <li>primary: A boolean. If set to true, the column will be treated as primary.</li>
     * <li>auto-inc: A boolean. Auto increment the value of primary column in case of insert (mysql Only)</li>
     * <li>identity: A boolean. Make the column an identity column (mssql only)</li>
     * <li>default: Sets a default value for the column in case of insert.</li>
     * <li>unique: Make the column act as unique index.</li>
     * <li>is-null: A boolean. If set to true, null values will be allowed for the column.</li>
     * <li>comment: A comment to include about the column.</li>
     * <li>validator: A custom PHP function that can be used as a filter before inserting.</li>
     * </ul>
     * 
     * @return Column
     * 
     * @throws DatabaseException
     */
    public static function create(string $database, string $name, array $options = []) : Column {
        if (!in_array($database, ConnectionInfo::SUPPORTED_DATABASES)) {
            throw new DatabaseException('Not support database: '.$database);
        }

        if ($database == 'mssql') {
            $col = new MSSQLColumn($name);
        } else {
            $col = new MySQLColumn($name);
        }

        if (isset($options['datatype'])) {
            $datatype = $options['datatype'];
        } else if (isset($options['type'])) {
            $datatype = $options['type'];
        } else {
            $datatype = 'mixed';
        }

        $col->setDatatype($datatype);
        $size = isset($options['size']) ? intval($options['size']) : 1;
        $col->setSize($size);

        self::primaryCheck($col, $options);
        self::columnAttributesCheck($col, $options);
        self::identityCheck($col, $options);

        return $col;
    }
    /**
     * Map a database column in one DBMS to another DBMS.
     * 
     * @param string $to The DBMS at which the column will be converted to.
     * 
     * @param Column $column The column that will be converted.
     * 
     * @return Column The method will return new instance which is compatible
     * with the new DBMS.
     */
    public static function map(string $to, Column $column) : Column {
        if ($column instanceof MySQLColumn) {
            $from = 'mysql';
        } else if ($column instanceof MSSQLColumn) {
            $from = 'mssql';
        }
        $optionsArr = [
            ColOption::TYPE => TypesMap::getType($from, $to, $column->getDatatype()),
            ColOption::DEFAULT => $column->getDefault(),
            ColOption::COMMENT => $column->getComment(),
            ColOption::PRIMARY => $column->isPrimary(),
            ColOption::NAME => $column->getName(),
            ColOption::SIZE => $column->getSize(),
            ColOption::SCALE => $column->getScale(),
            ColOption::NULL => $column->isNull(),
            ColOption::UNIQUE => $column->isUnique(),
            ColOption::VALIDATOR => $column->getCustomCleaner(),
            ColOption::AUTO_UPDATE => $column->isAutoUpdate()
        ];

        if ($column instanceof MSSQLColumn) {
            $optionsArr[ColOption::IDENTITY] = $column->isIdentity();
            //If its an identity in mssql, then it must be auto inc in MySQL.
            $optionsArr[ColOption::AUTO_INCREMENT] = $column->isIdentity();
        } else if ($column instanceof MySQLColumn) {
            $optionsArr[ColOption::AUTO_INCREMENT] = $column->isAutoInc();
            //If its auto inc in MySQL, it must be an identity in mssql.
            $optionsArr[ColOption::IDENTITY] = $column->isAutoInc();
        }

        return self::create($to, $column->getName(), $optionsArr);
    }
    /**
     * 
     * @param MSSQLColumn $col
     * @param array $options
     */
    private static function columnAttributesCheck(Column $col, array $options) {
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

        //the 'not null' or 'null' must be specified, or it will cause query,
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
     * @param Column $col
     * @param array $options
     */
    private static function identityCheck(Column $col, array $options) {
        if ($col instanceof MSSQLColumn) {
            $isIdentity = isset($options['identity']) ? $options['identity'] : false;

            if ($isIdentity === true) {
                $col->setIsIdentity(true);
            }
        }
    }

    /**
     * 
     * @param Column $col
     * @param array $options
     */
    private static function primaryCheck(Column $col, array $options) {
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
