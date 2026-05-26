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

use WebFiori\Database\Query\InsertBuilder;

/**
 * A class which is used to construct INSERT queries for SQLite databases.
 * 
 * SQLite uses positional placeholders (?) for prepared statements.
 * Values are stored as a flat array for sequential binding.
 *
 * @author Ibrahim
 */
class SQLiteInsertBuilder extends InsertBuilder {
    /**
     * Parses column values and prepares them for binding with SQLite prepared statements.
     * 
     * Values are stored as a flat array since SQLite uses positional (index-based)
     * parameter binding. The order matches the placeholder positions in the
     * generated INSERT query.
     * 
     * @param array $values An array of associative arrays where each sub-array
     * represents a row to insert. Keys are column names and values are the
     * data to insert.
     */
    public function parseValues(array $values) {
        $arr = [];

        foreach ($values as $valsArr) {
            foreach ($valsArr as $col => $val) {
                $arr[] = $val;
            }
        }

        $this->setQueryParams($arr);
    }
}
