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

/**
 * Represents a column in a SQLite database table.
 * 
 * SQLite uses type affinity rather than strict types. All types are mapped
 * to one of: INTEGER, REAL, TEXT, BLOB. The original requested type is
 * preserved for cross-engine migration metadata.
 * 
 * Special behavior:
 * - INTEGER PRIMARY KEY columns are automatically treated as rowid aliases
 *   and support AUTOINCREMENT.
 * - Boolean values are stored as INTEGER (0/1).
 * - Date/time values are stored as TEXT in ISO-8601 format.
 *
 * @author Ibrahim
 */
class SQLiteColumn extends Column {
    /**
     * Whether this column auto-increments.
     * 
     * @var bool
     */
    private bool $autoInc = false;

    /**
     * Creates a new SQLite column instance.
     * 
     * @param string $name The name of the column as it appears in the database.
     * 
     * @param string $datatype The datatype of the column. Will be mapped to
     * SQLite affinity (integer, real, text, blob).
     * 
     * @param int $size The size of the column. Not enforced by SQLite but
     * preserved for metadata.
     */
    public function __construct(string $name = 'col', string $datatype = 'text', int $size = 1) {
        parent::__construct($name);
        $this->setSize($size);
    }

    /**
     * Returns a string representation of the column for use in CREATE TABLE statements.
     * 
     * If the column is an auto-incrementing primary key, the output will be:
     * "col_name" integer primary key autoincrement
     * 
     * Otherwise, it includes NOT NULL, UNIQUE, and DEFAULT constraints as applicable.
     * 
     * @return string The column definition string.
     */
    public function asString(): string {
        $str = $this->getName().' '.$this->getDatatype();

        if ($this->isPrimary() && $this->autoInc) {
            $str .= ' primary key autoincrement';

            return $str;
        }

        if (!$this->isNull()) {
            $str .= ' not null';
        }

        if ($this->isUnique()) {
            $str .= ' unique';
        }

        if ($this->getDefault() !== null) {
            $default = $this->getDefault();

            if (is_string($default) && $default !== 'null') {
                $str .= " default '".$default."'";
            } else {
                $str .= ' default '.$default;
            }
        }

        return $str;
    }

    /**
     * Cleans and validates a value before it is used in a query.
     * 
     * Casts the value to the appropriate PHP type based on the column's
     * SQLite affinity type.
     * 
     * @param mixed $val The value to clean. Can be a single value or an array.
     * 
     * @return mixed The cleaned value(s).
     */
    public function cleanValue($val) {
        if (is_array($val)) {
            return array_map(fn($v) => $this->cleanSingle($v), $val);
        }

        return $this->cleanSingle($val);
    }

    /**
     * Returns the column name with proper quoting for SQLite.
     * 
     * SQLite uses double quotes for identifier quoting. The parent's getName()
     * returns "tableName.colName" when table prefix is enabled, which is then
     * split and each part individually quoted.
     * 
     * @return string The quoted column name (e.g., "col" or "table"."col").
     */
    public function getName(): string {
        return self::doubleQuote(parent::getName());
    }

    /**
     * Wraps each dot-separated part of a string in double quotes.
     * 
     * @param string $str A string such as "table.column" or just "column".
     * 
     * @return string The quoted string (e.g., "table"."column").
     */
    public static function doubleQuote(string $str): string {
        $trimmed = trim($str);

        if (strlen($trimmed) != 0) {
            $exp = explode('.', $trimmed);
            $arr = [];

            foreach ($exp as $xStr) {
                $arr[] = '"'.trim(trim($xStr, '"'), '"').'"';
            }

            return implode('.', $arr);
        }

        return '';
    }

    /**
     * Returns the PHP type that corresponds to this column's SQLite affinity.
     * 
     * Mapping:
     * - integer → int
     * - real → float
     * - text, blob → string
     * 
     * If the column is nullable, '|null' is appended.
     * 
     * @return string The PHP type string (e.g., 'int', 'string|null').
     */
    public function getPHPType(): string {
        $type = $this->getDatatype();
        $nullStr = $this->isNull() ? '|null' : '';

        return match ($type) {
            'integer' => 'int'.$nullStr,
            'real' => 'float'.$nullStr,
            default => 'string'.$nullStr,
        };
    }

    /**
     * Checks if this column is set to auto-increment.
     * 
     * In SQLite, only INTEGER PRIMARY KEY columns can auto-increment.
     * 
     * @return bool True if the column auto-increments, false otherwise.
     */
    public function isAutoInc(): bool {
        return $this->autoInc;
    }

    /**
     * Sets whether this column should auto-increment.
     * 
     * Setting this to true will also force the column type to 'integer'
     * and mark it as primary, since SQLite only supports AUTOINCREMENT
     * on INTEGER PRIMARY KEY columns.
     * 
     * @param bool $bool True to enable auto-increment, false to disable.
     */
    public function setIsAutoInc(bool $bool) {
        $this->autoInc = $bool;

        if ($bool) {
            $this->setDatatype('integer');
            $this->setIsPrimary(true);
        }
    }

    /**
     * Sets the datatype of the column.
     * 
     * The provided type is mapped to SQLite's type affinity system:
     * - int, integer, bigint, bit, bool, boolean → integer
     * - real, float, double, decimal, money, numeric → real
     * - blob, binary, varbinary → blob
     * - Everything else (varchar, text, datetime, mixed, etc.) → text
     * 
     * @param string $type The requested datatype.
     */
    public function setDatatype(string $type): void {
        $normalized = strtolower(trim($type));

        $mapped = match (true) {
            in_array($normalized, ['int', 'integer', 'bigint', 'bit', 'bool', 'boolean']) => 'integer',
            in_array($normalized, ['real', 'float', 'double', 'decimal', 'money', 'numeric']) => 'real',
            in_array($normalized, ['blob', 'binary', 'varbinary']) => 'blob',
            default => 'text',
        };

        // Bypass parent validation — SQLite accepts any affinity
        $this->setSupportedTypes(['integer', 'real', 'text', 'blob']);
        parent::setDatatype($mapped);
    }

    /**
     * Cleans a single value based on the column's affinity type.
     * 
     * @param mixed $val The value to clean.
     * 
     * @return mixed The cleaned value cast to the appropriate PHP type,
     * or null if the input is null.
     */
    private function cleanSingle($val) {
        if ($val === null) {
            return null;
        }

        $type = $this->getDatatype();

        if ($type === 'integer') {
            return (int) $val;
        } elseif ($type === 'real') {
            return (float) $val;
        }

        return (string) $val;
    }
}
