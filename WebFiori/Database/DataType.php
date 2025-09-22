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
namespace WebFiori\Database;

/**
 * Constants for database-agnostic data types across different database systems.
 * 
 * This class provides standardized data type constants that work across
 * MySQL and MSSQL databases. The constants are mapped to appropriate
 * native types for each database system during query generation.
 *
 * @author Ibrahim
 */
class DataType {
    /**
     * Big integer data type. Applicable to:
     * <ul>
     * <li>SQL Server</li>
     * </ul>
     */
    const BIGINT = 'bigint';
    /**
     * Binary data type. Applicable to:
     * <ul>
     * <li>SQL Server</li>
     * </ul>
     */
    const BINARY = 'binary';
    /**
     * Bit data type. Applicable to:
     * <ul>
     * <li>MySQL</li>
     * <li>SQL Server</li>
     * </ul>
     */
    const BIT = 'bit';
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
    const BLOB_LONG = 'longblob';
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
    const BLOB_TINY = 'tinyblob';
    /**
     * Character data type. Applicable to:
     * <ul>
     * <li>MySQL</li>
     * <li>SQL Server</li>
     * </ul>
     */
    const BOOL = 'bool';
    /**
     * Character data type. Applicable to:
     * <ul>
     * <li>MySQL</li>
     * <li>SQL Server</li>
     * </ul>
     */
    const CHAR = 'char';
    /**
     * Date data type. Applicable to:
     * <ul>
     * <li>SQL Server</li>
     * </ul>
     */
    const DATE = 'date';
    /**
     * Date-time data type. Applicable to:
     * <ul>
     * <li>MySQL</li>
     * <li>SQL Server (use datetime2)</li>
     * </ul>
     */
    const DATETIME = 'datetime';
    /**
     * Date time 2 data type. Applicable to:
     * <ul>
     * <li>SQL Server</li>
     * </ul>
     */
    const DATETIME2 = 'datetime2';
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
     * Integer data type. Applicable to:
     * <ul>
     * <li>MySQL</li>
     * <li>SQL Server</li>
     * </ul>
     */
    const INT = 'int';
    /**
     * Money data type. Applicable to:
     * <ul>
     * <li>SQL Server</li>
     * </ul>
     */
    const MONEY = 'money';
    /**
     * nchar data type. Applicable to:
     * <ul>
     * <li>SQL Server</li>
     * </ul>
     */
    const NCHAR = 'nchar';
    /**
     * nvarchar data type. Applicable to:
     * <ul>
     * <li>SQL Server</li>
     * </ul>
     */
    const NVARCHAR = 'nvarchar';
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
     * Time data type. Applicable to:
     * <ul>
     * <li>SQL Server</li>
     * </ul>
     */
    const TIME = 'time';
    /**
     * Timestamp data type. Applicable to:
     * <ul>
     * <li>MySQL</li>
     * </ul>
     */
    const TIMESTAMP = 'timestamp';
    /**
     * Var binary data type. Applicable to:
     * <ul>
     * <li>SQL Server</li>
     * </ul>
     */
    const VARBINARY = 'varbinary';
    /**
     * Character data type. Applicable to:
     * <ul>
     * <li>MySQL</li>
     * <li>SQL Server</li>
     * </ul>
     */
    const VARCHAR = 'varchar';
}
