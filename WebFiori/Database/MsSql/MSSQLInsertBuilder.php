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
namespace WebFiori\Database\MsSql;

use WebFiori\Database\InsertBuilder;

/**
 * A class which is used to construct insert query for MSSQL server.
 *
 * @author Ibrahim
 */
class MSSQLInsertBuilder extends InsertBuilder {
    public function parseValues(array $values) {
        $index = 0;
        $arr = [];

        foreach ($values as $valsArr) {
            foreach ($valsArr as $col => $val) {
                $colObj = $this->getTable()->getColByKey($col);
                $arr[] = $val;
            }
            $index++;
        }
        $this->setQueryParams($arr);
    }
}
