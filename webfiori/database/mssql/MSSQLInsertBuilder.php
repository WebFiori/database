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
namespace webfiori\database\mssql;

use webfiori\database\InsertBuilder;
use webfiori\database\Table;

/**
 * Description of MSSQLInsertBuilder
 *
 * @author Ibrahim
 */
class MSSQLInsertBuilder extends InsertBuilder {
    public function __construct(Table $table, array $colsAndVals) {
        parent::__construct($table, $colsAndVals);
    }

    public function parseValues(array $values) {
        $index = 0;
        $arr = [];

        foreach ($values as $valsArr) {

            foreach ($valsArr as $col => $val) {
                $colObj = $this->getTable()->getColByKey($col);
                $arr[] = array_merge([$val, SQLSRV_PARAM_IN], $colObj->getTypeArr());
            }
            $index++;
        }
        $this->setQueryParams($arr);
    }
}
