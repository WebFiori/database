<?php
/**
 * This file is licensed under MIT License.
 * 
 * Copyright (c) 2023 Ibrahim BinAlshikh
 * 
 * For more information on the license, please visit: 
 * https://github.com/WebFiori/.github/blob/main/LICENSE
 * 
 */
namespace WebFiori\Database;

/**
 * A class that holds mapping of data types between different DBMSs.
 *
 * @author Ibrahim
 */
class TypesMap {
    /**
     * An associative array that holds the mapping.
     * 
     * The first level of the array are indices that represents main DBMS name. the
     * second level is a sub associative array that represents the DBMS at which first
     * level will be mapped to. Inside each array, are the types and the mapping.
     */
    const MAP = [
        'mysql' => [
            'mssql' => [
                'int' => 'int',
                'char' => 'char',
                'varchar' => 'varchar',
                'timestamp' => 'datetime2',
                'tinyblob' => 'binary',
                'blob' => 'binary',
                'mediumblob' => 'binary',
                'longblob' => 'binary',
                'datetime' => 'datetime2',
                'text' => 'nvarchar',
                'mediumtext' => 'nvarchar',
                'decimal' => 'decimal',
                'double' => 'float',
                'float' => 'float',
                'boolean' => 'boolean', 
                'bool' => 'bool',
                'bit' => 'bit'
            ]
        ],
        'mssql' => [
            'mysql' => [
                'int' => 'int',
                'bigint' => 'int',
                'varchar' => 'varchar',
                'nvarchar' => 'text',
                'char' => 'char',
                'nchar' => 'text',
                'binary' => 'blob',
                'varbinary' => 'blob',
                'date' => 'datetime',
                'datetime2' => 'datetime',
                'datetime' => 'datetime',
                'time' => 'varchar',
                'money' => 'decimal',
                'bit' => 'bit',
                'decimal' => 'decimal',
                'float' => 'float',
                'boolean' => 'boolean',
                'bool' => 'bool'
            ]
        ]
    ];
    /**
     * Return the representation of specific datatype from a DBMS in another one.
     * 
     * @param string $from The DBMS at which the type will be converted from.
     * 
     * @param string $to The DBMS at which the type will be converted to.
     * 
     * @param string $dataType The name of the data type as it appears in the
     * '$from' DBMS.
     * 
     * @return string If one of the given DMMSs is not supported, empty string
     * is returned. Also, if the given type is not supported by the '$from',
     * empty string is returned. Other than that, the representation of
     * the type in the other DBMS is returned as string.
     */
    public static function getType(string $from, string $to, string $dataType) : string {
        if (!isset(self::MAP[$from])) {
            return '';
        }

        if (!isset(self::MAP[$from][$to])) {
            return '';
        }

        if (!isset(self::MAP[$from][$to][$dataType])) {
            return '';
        }

        return self::MAP[$from][$to][$dataType];
    }
}
