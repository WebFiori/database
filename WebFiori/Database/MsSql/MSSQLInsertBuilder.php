<?php

/**
 * This file is licensed under MIT License.
 * 
 * Copyright (c) 2023-present WebFiori Framework
 * 
 * For more information on the license, please visit: 
 * https://github.com/WebFiori/.github/blob/main/LICENSE
 * 
 */
namespace WebFiori\Database\MsSql;

use WebFiori\Database\Query\InsertBuilder;

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

                if ($colObj !== null && in_array($colObj->getDatatype(), ['binary', 'varbinary'])) {
                    $arr[] = [$val, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STREAM(SQLSRV_ENC_BINARY), SQLSRV_SQLTYPE_VARBINARY('max')];
                } else {
                    $arr[] = $val;
                }
            }
            $index++;
        }
        $this->setQueryParams($arr);
    }
}
