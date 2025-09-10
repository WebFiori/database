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
namespace WebFiori\Database\MySql;

use WebFiori\Database\Column;
use WebFiori\Database\InsertBuilder;

/**
 * A class which is used to construct insert query for MySQL database.
 *
 * @author Ibrahim
 */
class MySQLInsertBuilder extends InsertBuilder {
    public function parseValues(array $values) {
        $index = 0;
        $queryParams = [
            'bind' => '',
            'values' => []
        ];

        foreach ($values as $valsArr) {
            $queryParams['values'][] = [];

            foreach ($valsArr as $col => $val) {
                $colObj = $this->getTable()->getColByKey($col);
                $colType = $colObj->getDatatype();
                $queryParams['values'][$index][] = $val;

                if ($colType == 'int' || $colType == 'bit' || in_array($colType, Column::BOOL_TYPES)) {
                    $queryParams['bind'] .= 'i';
                } else if ($colType == 'decimal' || $colType == 'float') {
                    $queryParams['bind'] .= 'd';
                } else {
                    $queryParams['bind'] .= 's';
                }
            }
            $index++;
        }
        $this->setQueryParams($queryParams);
    }
}
