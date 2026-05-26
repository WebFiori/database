<?php

/**
 * This file is licensed under MIT License.
 * 
 * Copyright (c) 2026-present WebFiori Framework
 * 
 * For more information on the license, please visit: 
 * https://github.com/WebFiori/.github/blob/main/LICENSE
 * 
 */
namespace WebFiori\Database\Sqlite;

use WebFiori\Database\AbstractQuery;
use WebFiori\Database\Column;
use WebFiori\Database\DatabaseException;

/**
 * A class which is used to build SQL queries for SQLite databases.
 * 
 * SQLite-specific behaviors:
 * - Uses double-quote identifier quoting
 * - LIMIT/OFFSET for pagination (appended to SELECT queries)
 * - Does not support ALTER TABLE MODIFY COLUMN or ADD/DROP PRIMARY KEY
 * - Supports ALTER TABLE RENAME COLUMN (SQLite 3.25+)
 * - Supports ALTER TABLE ADD COLUMN
 *
 * @author Ibrahim
 */
class SQLiteQuery extends AbstractQuery {
    /**
     * Array of bound parameter values for prepared statements.
     * 
     * @var array
     */
    private array $bindings = [];

    /**
     * Creates a new SQLite query builder instance.
     */
    public function __construct() {
        parent::__construct();
    }

    /**
     * Adds a value to the bindings array for prepared statement execution.
     * 
     * @param Column $col The column that the value belongs to.
     * @param mixed $value The value to bind.
     */
    public function addBinding(Column $col, $value) {
        $this->bindings[] = $value;
    }

    /**
     * Builds a query to add a column to the associated table.
     * 
     * SQLite supports ADD COLUMN but with restrictions (no PRIMARY KEY,
     * UNIQUE, or default value of CURRENT_TIME/CURRENT_DATE/CURRENT_TIMESTAMP
     * unless the column is nullable).
     * 
     * @param string $colKey The key of the column as defined in the table.
     * @param string|null $location Not used for SQLite. Included for interface compatibility.
     * 
     * @return SQLiteQuery The same instance for method chaining.
     * 
     * @throws DatabaseException If no column with the given key exists in the table.
     */
    public function addCol(string $colKey, ?string $location = null) {
        $tblName = $this->getTable()->getName();
        $colToAdd = $this->getTable()->getColByKey($colKey);

        if (!($colToAdd instanceof Column)) {
            throw new DatabaseException("The table '$tblName' has no column with key '$colKey'.");
        }

        $this->setQuery('alter table '.$tblName.' add column '.$colToAdd->asString());

        return $this;
    }

    /**
     * Not supported in SQLite.
     * 
     * SQLite does not support adding primary key constraints after table creation.
     * 
     * @param string $pkName The name of the primary key constraint.
     * @param array $pkCols Array of column keys that form the primary key.
     * 
     * @throws DatabaseException Always thrown since SQLite does not support this operation.
     */
    public function addPrimaryKey(string $pkName, array $pkCols) {
        throw new DatabaseException('SQLite does not support ALTER TABLE ADD PRIMARY KEY.');
    }

    /**
     * Creates and returns a copy of the query builder.
     * 
     * The copy includes the linked table and schema but not the current
     * query string or bindings.
     * 
     * @return AbstractQuery A new SQLiteQuery instance with the same table and schema.
     */
    public function copyQuery(): AbstractQuery {
        $copy = new SQLiteQuery();
        $copy->setTable($this->getTable(), false);
        $copy->setSchema($this->getSchema());

        return $copy;
    }

    /**
     * Not supported in SQLite.
     * 
     * SQLite does not support dropping primary key constraints.
     * 
     * @param string|null $pkName The name of the primary key to drop.
     * 
     * @throws DatabaseException Always thrown since SQLite does not support this operation.
     */
    public function dropPrimaryKey(?string $pkName = null) {
        throw new DatabaseException('SQLite does not support ALTER TABLE DROP PRIMARY KEY.');
    }

    /**
     * Returns the array of bound parameter values.
     * 
     * @return array The values that will be bound to the prepared statement placeholders.
     */
    public function getBindings(): array {
        return $this->bindings;
    }

    /**
     * Returns the generated SQL query string.
     * 
     * For SELECT queries with a limit set, LIMIT and OFFSET clauses are appended.
     * 
     * @return string The complete SQL query string.
     */
    public function getQuery() {
        $query = parent::getQuery();

        if ($this->getLastQueryType() == 'select' && $this->getLimit() > 0) {
            $query .= ' LIMIT '.$this->getLimit();

            if ($this->getOffset() > 0) {
                $query .= ' OFFSET '.$this->getOffset();
            }
        }

        return $query;
    }

    /**
     * Constructs an INSERT query for the associated table.
     * 
     * @param array $colsAndVals An associative array of column keys to values,
     * or an array with 'cols' and 'values' keys for multi-row insert.
     * 
     * @return AbstractQuery The same instance for method chaining.
     */
    public function insert(array $colsAndVals): AbstractQuery {
        $this->setInsertBuilder(new SQLiteInsertBuilder($this->getTable(), $colsAndVals));

        return $this;
    }

    /**
     * Not supported in SQLite.
     * 
     * SQLite does not support ALTER TABLE MODIFY COLUMN. To change a column's
     * type or constraints, the table must be recreated.
     * 
     * @param string $colKey The key of the column to modify.
     * @param string|null $location Not used.
     * 
     * @throws DatabaseException Always thrown since SQLite does not support this operation.
     */
    public function modifyCol($colKey, ?string $location = null) {
        throw new DatabaseException('SQLite does not support ALTER TABLE MODIFY COLUMN.');
    }

    /**
     * Builds a query to rename a column in the associated table.
     * 
     * Requires SQLite 3.25.0 or later. The column must have its old name
     * set via {@see Column::setName()} before calling this method.
     * 
     * @param string $colKey The key of the column to rename.
     * 
     * @return SQLiteQuery The same instance for method chaining.
     * 
     * @throws DatabaseException If the column doesn't exist or old name is not set.
     */
    public function renameCol($colKey) {
        $colObj = $this->getTable()->getColByKey($colKey);
        $tblName = $this->getTable()->getName();

        if (!$colObj instanceof Column) {
            throw new DatabaseException("The table $tblName has no column with key '$colKey'.");
        }

        $oldName = $colObj->getOldName();
        $newName = $colObj->getNormalName();

        $this->setQuery("alter table $tblName rename column \"$oldName\" to \"$newName\"");

        return $this;
    }

    /**
     * Resets all parameter bindings.
     */
    public function resetBinding() {
        $this->bindings = [];
    }

    /**
     * Sets the parameter bindings array.
     * 
     * @param array $binding The new bindings array.
     * @param string $merge How to merge with existing bindings:
     * - 'none': Replace existing bindings (default)
     * - 'first': Prepend new bindings before existing
     * - 'end': Append new bindings after existing
     */
    public function setBindings(array $binding, string $merge = 'none') {
        if ($merge == 'first') {
            $this->bindings = array_merge($binding, $this->bindings);
        } else if ($merge == 'end') {
            $this->bindings = array_merge($this->bindings, $binding);
        } else {
            $this->bindings = $binding;
        }
    }

    /**
     * Constructs an UPDATE query for the associated table.
     * 
     * Columns with null values will use 'column = null' in the SET clause.
     * Non-null values use prepared statement placeholders and are added to bindings.
     * 
     * @param array $newColsVals An associative array where keys are column keys
     * and values are the new values to set.
     * 
     * @return SQLiteQuery The same instance for method chaining.
     */
    public function update(array $newColsVals): AbstractQuery {
        $updateArr = [];
        $tblName = $this->getTable()->getName();

        foreach ($newColsVals as $colKey => $newVal) {
            $colObj = $this->getTable()->getColByKey($colKey);

            if ($colObj === null) {
                $this->getTable()->addColumns([$colKey => []]);
                $colObj = $this->getTable()->getColByKey($colKey);
            }

            $colName = $colObj->getName();

            if ($newVal === null) {
                $updateArr[] = "$colName = null";
            } else {
                $updateArr[] = "$colName = ?";
                $this->addBinding($colObj, $newVal);
            }
        }

        $query = "update $tblName set ".implode(', ', $updateArr);
        $this->setQuery($query);

        return $this;
    }
}
