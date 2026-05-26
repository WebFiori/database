<?php

/**
 * This file is licensed under MIT License.
 * 
 * Copyright (c) 2019-present WebFiori Framework
 * 
 * For more information on the license, please visit: 
 * https://github.com/WebFiori/.github/blob/main/LICENSE
 * 
 */
namespace WebFiori\Database\Sqlite;

use WebFiori\Database\Column;
use WebFiori\Database\ColOption;
use WebFiori\Database\DataType;
use WebFiori\Database\Table;

/**
 * Represents a SQLite database table.
 * 
 * Generates CREATE TABLE statements using SQLite syntax:
 * - Double-quoted identifiers
 * - Type affinity (INTEGER, REAL, TEXT, BLOB)
 * - INTEGER PRIMARY KEY AUTOINCREMENT for auto-increment columns
 * - Inline FOREIGN KEY constraints
 * - No engine, charset, or collation clauses
 *
 * @author Ibrahim
 */
class SQLiteTable extends Table {
    /**
     * Creates a new SQLite table instance.
     * 
     * @param string $name The name of the table.
     */
    public function __construct(string $name = 'table') {
        parent::__construct($name);
    }

    /**
     * Adds multiple columns to the table from an options array.
     * 
     * Each entry in the array should be keyed by column name with an array
     * of options or a Column instance as the value. Supported options:
     * - ColOption::TYPE: The datatype (mapped to SQLite affinity)
     * - ColOption::SIZE: Column size (metadata only, not enforced by SQLite)
     * - ColOption::PRIMARY: Whether the column is a primary key
     * - ColOption::AUTO_INCREMENT: Whether the column auto-increments
     * - ColOption::NULL: Whether NULL values are allowed
     * - ColOption::UNIQUE: Whether the column has a unique constraint
     * - ColOption::DEFAULT: Default value for the column
     * - ColOption::COMMENT: Column comment (metadata only)
     * 
     * @param array $colsArr An associative array of column definitions.
     */
    public function addColumns(array $colsArr): Table {
        foreach ($colsArr as $key => $options) {
            if ($options instanceof Column) {
                $this->addColumn($key, $options);
            } else {
                $col = new SQLiteColumn($key);

                if (isset($options[ColOption::TYPE])) {
                    $col->setDatatype($options[ColOption::TYPE]);
                }

                if (isset($options[ColOption::SIZE])) {
                    $col->setSize((int) $options[ColOption::SIZE]);
                }

                if (isset($options[ColOption::PRIMARY]) && $options[ColOption::PRIMARY]) {
                    $col->setIsPrimary(true);
                }

                if (isset($options[ColOption::AUTO_INCREMENT]) && $options[ColOption::AUTO_INCREMENT]) {
                    $col->setIsAutoInc(true);
                }

                if (isset($options[ColOption::NULL]) && $options[ColOption::NULL]) {
                    $col->setIsNull(true);
                }

                if (isset($options[ColOption::UNIQUE]) && $options[ColOption::UNIQUE]) {
                    $col->setIsUnique(true);
                }

                if (isset($options[ColOption::DEFAULT])) {
                    $col->setDefault($options[ColOption::DEFAULT]);
                }

                if (isset($options[ColOption::COMMENT])) {
                    $col->setComment($options[ColOption::COMMENT]);
                }

                $this->addColumn($key, $col);
            }
        }

        return $this;
    }

    /**
     * Returns the table name with double-quote quoting for SQLite.
     * 
     * @return string The quoted table name (e.g., "users").
     */
    public function getName(): string {
        return SQLiteColumn::doubleQuote(parent::getName());
    }

    /**
     * Returns the SQL CREATE TABLE statement for this table.
     * 
     * The generated SQL uses SQLite syntax with:
     * - CREATE TABLE IF NOT EXISTS
     * - Inline column definitions
     * - Composite PRIMARY KEY constraint (unless single INTEGER PRIMARY KEY AUTOINCREMENT)
     * - FOREIGN KEY constraints
     * 
     * @return string The complete CREATE TABLE SQL statement.
     */
    public function toSQL(): string {
        $queryStr = 'create table if not exists '.$this->getName()." (\n";
        $queryStr .= $this->createColumnsString();

        $pk = $this->createPKString();

        if (!empty($pk)) {
            $queryStr .= ",\n".$pk;
        }

        $fk = $this->createFKString();

        if (!empty($fk)) {
            $queryStr .= ",\n".$fk;
        }

        $queryStr .= "\n)";

        return $queryStr;
    }

    /**
     * Builds the column definitions portion of the CREATE TABLE statement.
     * 
     * @return string Comma-separated column definitions, each on its own line.
     */
    private function createColumnsString(): string {
        $cols = $this->getCols();
        $parts = [];

        foreach ($cols as $colObj) {
            $parts[] = '    '.$colObj->asString();
        }

        return implode(",\n", $parts);
    }

    /**
     * Builds the PRIMARY KEY constraint clause.
     * 
     * Returns an empty string if:
     * - The table has no primary key columns
     * - The table has a single INTEGER PRIMARY KEY AUTOINCREMENT column
     *   (handled inline in the column definition)
     * 
     * @return string The PRIMARY KEY constraint or empty string.
     */
    private function createPKString(): string {
        $pkCount = $this->getPrimaryKeyColsCount();

        if ($pkCount == 0) {
            return '';
        }

        // Single INTEGER PRIMARY KEY AUTOINCREMENT is handled inline
        if ($pkCount == 1) {
            $keys = $this->getPrimaryKeyColsKeys();
            $col = $this->getColByKey($keys[0]);

            if ($col instanceof SQLiteColumn && $col->isAutoInc()) {
                return '';
            }
        }

        $pkCols = [];

        foreach ($this->getPrimaryKeyColsKeys() as $key) {
            $pkCols[] = $this->getColByKey($key)->getName();
        }

        return '    primary key ('.implode(', ', $pkCols).')';
    }

    /**
     * Builds the FOREIGN KEY constraint clauses.
     * 
     * Each foreign key is rendered as:
     * foreign key (local_cols) references ref_table (ref_cols) on update/delete action
     * 
     * @return string The foreign key constraints or empty string if none exist.
     */
    private function createFKString(): string {
        $fkParts = [];

        foreach ($this->getForeignKeys() as $fkObj) {
            $sourceCols = [];

            foreach ($fkObj->getSourceCols() as $colObj) {
                $colObj->setWithTablePrefix(false);
                $sourceCols[] = $colObj->getName();
            }

            $targetCols = [];

            foreach ($fkObj->getOwnerCols() as $colObj) {
                $colObj->setWithTablePrefix(false);
                $targetCols[] = $colObj->getName();
            }

            $fk = '    foreign key ('.implode(', ', $targetCols).') '
                .'references '.$fkObj->getSourceName().' ('.implode(', ', $sourceCols).')';

            if ($fkObj->getOnUpdate() !== null) {
                $fk .= ' on update '.$fkObj->getOnUpdate();
            }

            if ($fkObj->getOnDelete() !== null) {
                $fk .= ' on delete '.$fkObj->getOnDelete();
            }

            $fkParts[] = $fk;
        }

        return implode(",\n", $fkParts);
    }

    /**
     * Maps a requested datatype to SQLite's type affinity.
     * 
     * @param string $type The requested type (e.g., 'int', 'varchar', 'datetime').
     * 
     * @return string The SQLite affinity type (integer, real, text, or blob).
     */
    private function mapType(string $type): string {
        $type = strtolower($type);

        return match (true) {
            in_array($type, ['int', 'bigint', 'bit', 'bool', 'boolean']) => 'integer',
            in_array($type, ['float', 'double', 'decimal', 'money']) => 'real',
            in_array($type, ['blob', 'binary', 'varbinary', 'mediumblob', 'longblob', 'tinyblob']) => 'blob',
            default => 'text',
        };
    }
}
