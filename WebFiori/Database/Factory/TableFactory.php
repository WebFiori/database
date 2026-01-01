<?php

/**
 * This file is licensed under MIT License.
 * 
 * Copyright (c) 2025-present WebFiori Framework
 * 
 * For more information on the license, please visit: 
 * https://github.com/WebFiori/.github/blob/main/LICENSE
 * 
 */
namespace WebFiori\Database\Factory;

use WebFiori\Database\MsSql\MSSQLTable;
use WebFiori\Database\MySql\MySQLTable;
use WebFiori\Database\Table;
use WebFiori\Database\DatabaseException;
use WebFiori\Database\ConnectionInfo;

/**
 *
 * @author Ibrahim
 */
class TableFactory {
    public static function create(string $database, string $name, array $cols = []) : Table {
        if (!in_array($database, ConnectionInfo::SUPPORTED_DATABASES)) {
            throw new DatabaseException('Not support database: '.$database);
        }

        if ($database == 'mssql') {
            $table = new MSSQLTable($name);
        } else {
            $table = new MySQLTable($name);
        }

        foreach ($cols as $name => $options) {
            if ($options instanceof Column) {
                $table->addColumn($name, ColumnFactory::map($database, $options));
            } else {
                $table->addColumn($name, ColumnFactory::create($database, $name, $options));
            }
        }

        return $table;
    }
    public static function map(string $to, Table $table) : Table {
        if ($table instanceof MySQLTable) {
            $from = 'mysql';
        } else if ($table instanceof MSSQLTable) {
            $from = 'mssql';
        }

        if ($from == $to) {
            return $table;
        }

        return self::create($to, $table->getName(), $table->getCols());
    }
}
