<?php

/**
 * This file is licensed under MIT License.
 * 
 * Copyright (c) 2024 Ibrahim BinAlshikh
 * 
 * For more information on the license, please visit: 
 * https://github.com/WebFiori/.github/blob/main/LICENSE
 * 
 */
namespace webfiori\database;

/**
 * A class which is used to hold all supported data types by different database engines.
 *
 * @author Ibrahim
 */
class DataType {
    /**
     * Integer data type. Applicable to:
     * <ul>
     * <li>MySQL</li>
     * <li>SQL Server</li>
     * </ul>
     */
    const INT = 'int';
    /**
     * Character data type. Applicable to:
     * <ul>
     * <li>MySQL</li>
     * <li>SQL Server</li>
     * </ul>
     */
    const CHAR = 'char';
    /**
     * Character data type. Applicable to:
     * <ul>
     * <li>MySQL</li>
     * <li>SQL Server</li>
     * </ul>
     */
    const VARCHAR = 'varchar';
    /**
     * Timestamp data type. Applicable to:
     * <ul>
     * <li>MySQL</li>
     * </ul>
     */
    const TIMESTAMP = 'timestamp';
    /**
     * Blob data type. Applicable to:
     * <ul>
     * <li>MySQL</li>
     * </ul>
     */
    const BLOB_TINY = 'tinyblob';
    /**
     * Blob data type. Applicable to:
     * <ul>
     * <li>MySQL</li>
     * </ul>
     */
    const BLOB = 'blob';
    /**
     * Blob data type. Applicable to:
     * <ul>
     * <li>MySQL</li>
     * </ul>
     */
    const BLOB_MEDIUM = 'mediumblob';
    /**
     * Blob data type. Applicable to:
     * <ul>
     * <li>MySQL</li>
     * </ul>
     */
    const BLOB_LONG = 'longblob';
    /**
     * Date-time data type. Applicable to:
     * <ul>
     * <li>MySQL</li>
     * <li>SQL Server (use datetime2)</li>
     * </ul>
     */
    const DATETIME = 'datetime';
    /**
     * Text data type. Applicable to:
     * <ul>
     * <li>MySQL</li>
     * </ul>
     */
    const TEXT = 'text';
    /**
     * Text data type. Applicable to:
     * <ul>
     * <li>MySQL</li>
     * </ul>
     */
    const TEXT_MEDIUM = 'mediumtext';
    /**
     * Decimal data type. Applicable to:
     * <ul>
     * <li>MySQL</li>
     * <li>SQL Server</li>
     * </ul>
     */
    const DECIMAL = 'decimal';
    /**
     * Double data type. Applicable to:
     * <ul>
     * <li>MySQL</li>
     * </ul>
     */
    const DOUBLE = 'double';
    /**
     * Character data type. Applicable to:
     * <ul>
     * <li>MySQL</li>
     * <li>SQL Server</li>
     * </ul>
     */
    const FLOAT = 'float';
    /**
     * Character data type. Applicable to:
     * <ul>
     * <li>MySQL</li>
     * <li>SQL Server</li>
     * </ul>
     */
    const BOOL = 'bool';
    /**
     * Bit data type. Applicable to:
     * <ul>
     * <li>MySQL</li>
     * <li>SQL Server</li>
     * </ul>
     */
    const BIT = 'bit';
    
    /**
     * Big integer data type. Applicable to:
     * <ul>
     * <li>SQL Server</li>
     * </ul>
     */
    const BIGINT = 'bigint';
    /**
     * nvarchar data type. Applicable to:
     * <ul>
     * <li>SQL Server</li>
     * </ul>
     */
    const NVARCHAR = 'nvarchar';
    /**
     * nchar data type. Applicable to:
     * <ul>
     * <li>SQL Server</li>
     * </ul>
     */
    const NCHAR = 'nchar';
    /**
     * Binary data type. Applicable to:
     * <ul>
     * <li>SQL Server</li>
     * </ul>
     */
    const BINARY = 'binary';
    /**
     * Var binary data type. Applicable to:
     * <ul>
     * <li>SQL Server</li>
     * </ul>
     */
    const VARBINARY = 'varbinary';
    /**
     * Date data type. Applicable to:
     * <ul>
     * <li>SQL Server</li>
     * </ul>
     */
    const DATE = 'date';
    /**
     * Date time 2 data type. Applicable to:
     * <ul>
     * <li>SQL Server</li>
     * </ul>
     */
    const DATETIME2 = 'datetime2';
    /**
     * Time data type. Applicable to:
     * <ul>
     * <li>SQL Server</li>
     * </ul>
     */
    const TIME = 'time';
    /**
     * Money data type. Applicable to:
     * <ul>
     * <li>SQL Server</li>
     * </ul>
     */
    const MONEY = 'money';

}
