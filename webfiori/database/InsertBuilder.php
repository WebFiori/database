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
namespace webfiori\database;

use webfiori\database\mssql\MSSQLTable;
use webfiori\database\mysql\MySQLTable;

/**
 * A class which is used to build insert SQL queries for diffrent database engines.
 *
 * @author Ibrahim
 */
class InsertBuilder {
    private $query;
    private $queryParams;
    private $paramPlaceholder;
    private $data;
    private $cols;
    private $vals;
    private $defaultVals;
    /**
     * 
     * @var Table
     */
    private $table;
    /**
     * Creates new instance of the class.
     * 
     * @param Table $table The table at which the statement is based on.
     * 
     * @param array $colsAndVals An array that holds the values that
     * will be inserted. The array can have two structures. If it is 
     * used to insert a single record, then the array must be associative. The
     * indices of the array are columns names and at each index is the value that
     * will be inserted. For multi-record insert, the array must have two
     * indices, 'cols' and 'values'. The index 'cols' is used to hold
     * columns names and the index 'values' is used to hold the records
     * that will be inserted as sub-arrays.
     */
    public function __construct(Table $table, array $colsAndVals) {
        $this->paramPlaceholder = '?';
        $this->table = $table;
        $this->data = $colsAndVals;
        
        $this->build();
    }
    /**
     * Construct an insert statement.
     * 
     * @param array $colsAndVals An array that holds the values that
     * will be inserted. The array can have two structures. If it is 
     * used to insert a single record, then the array must be associative. The
     * indices of the array are columns names and at each index is the value that
     * will be inserted. For multi-record insert, the array must have two
     * indices, 'cols' and 'values'. The index 'cols' is used to hold
     * columns names and the index 'values' is used to hold the records
     * that will be inserted as sub-arrays.
     * 
     * @param Table $table The table at which the insert query will be
     * based on.
     */
    public function insert(array $colsAndVals, Table $table = null) {
        if ($table !== null) {
            $this->table = $table;
        }
        $this->data = $colsAndVals;
        $this->build();
    }
    /**
     * Returns the character which is used as placeholder for building prepared
     * query.
     * 
     * @return string A string such as '?' or '$'.
     */
    public function getPlaceholder() : string {
        return $this->paramPlaceholder;
    }
    /**
     * Returns an array that holds the values which is used in binding with the
     * prepared query.
     * 
     * Depending on database engine, the structure of the array may differ.
     * 
     * @return array
     */
    public function getQueryParams() : array {
        return $this->queryParams;
    }
    private function build() {
        $this->queryParams = [
            'bind' => '',
            'values' => []
        ];
        $this->cols = [];
        $this->vals = [];
        $this->defaultVals = [];
        $this->query = 'insert into '.$this->getTable()->getName();
        $colsAndVals = $this->data;
        
        if (isset($colsAndVals['cols']) && isset($colsAndVals['values'])) {
            $this->cols = $colsAndVals['cols'];
            $this->vals = $colsAndVals['values'];
            $temp = [];
            $topIndex = 0;
            foreach ($this->vals as $valsArr) {
                $index = 0;
                $temp[] = [];
                foreach ($this->cols as $colKey) {
                    $temp[$topIndex][$colKey] = $valsArr[$index];
                    $index++;
                }
                $topIndex++;
            }
            $this->vals = $temp;
            
            $this->query .= ' '.$this->buildColsArr()."\nvalues\n";
            
            $values = trim(str_repeat('?, ', count($this->cols)),', ');
            $multiVals = trim(str_repeat('('.$values."),\n", count($this->vals)), ",\n");
            $this->query .= $multiVals.';';
        } else {
            $this->cols = array_keys($colsAndVals);
            $this->vals = [$colsAndVals];
            
            $this->query .= ' '.$this->buildColsArr();
            $this->cols = array_merge($this->cols, array_keys($this->defaultVals));
            $values = trim(str_repeat('?, ', count($this->cols)),', ');
            $this->query .= ' values ('.$values.');';
            
        }
        if ($this->getTable() instanceof MySQLTable) {
            $this->buildMySQLValues();
        } else if ($this->getTable() instanceof MSSQLTable) {
            $this->buildMSSQLValues();
        }
    }
    private function buildMSSQLValues() {
        $index = 0;
        $arr = [];
        
        foreach ($this->vals as $valsArr) {
            $valsArr = array_merge($valsArr, $this->defaultVals);
            foreach ($valsArr as $col => $val) {
                
                $colObj = $this->getTable()->getColByKey($col);
                $arr[] = array_merge([$val, SQLSRV_PARAM_IN], $colObj->getTypeArr());
                
            }
            $index++;
        }
        $this->queryParams = $arr;
    }
    private function buildMySQLValues() {
        $index = 0;
        foreach ($this->vals as $valsArr) {
            $this->queryParams['values'][] = [];
            $valsArr = array_merge($valsArr, $this->defaultVals);
            foreach ($valsArr as $col => $val) {
                $colObj = $this->getTable()->getColByKey($col);
                $colType = $colObj->getDatatype();
                $this->queryParams['values'][$index][] = $val;

                if ($colType == 'int' || $colType == 'bit' || in_array($colType, Column::BOOL_TYPES)) {
                    $this->queryParams['bind'] .= 'i';
                } else if ($colType == 'decimal' || $colType == 'float') {
                    $this->queryParams['bind'] .= 'd';
                } else {
                    $this->queryParams['bind'] .= 's';
                }
            }
            $index++;
        }
    }
    /**
     * Returns the generated insert query.
     * 
     * @return string The generated SQL query.
     */
    public function getQuery() : string {
        return $this->query;
    }
    /**
     * Returns the table instance at which the insert query is based on.
     * 
     * The goal of the table is to make sure that the binding between
     * the values and the data types of database columns is correct.
     * 
     * @return Table The table instance at which the insert query is based on.
     */
    public function getTable() : Table {
        return $this->table;
    }
    private function buildColsArr() {
        $colsArr = [];
        $colsStr = '';
        $table = $this->getTable();

        foreach ($this->cols as $colKey) {
            $colObj = $table->getColByKey($colKey);

            if ($colObj === null) {
                $table->addColumns([
                    $colKey => []
                ]);
                $colObj = $table->getColByKey($colKey);
            }
            $colObj->setWithTablePrefix(false);
            $colsArr[] = $colObj->getName();
        }
        $this->checkColsWithNoVals($this->cols, $colsArr);
        $colsStr = '('.implode(', ', $colsArr).')';

        return $colsStr;
    }
    private function checkColsWithNoVals(array $columnsWithVals, &$colsArr) {
        foreach ($this->getTable()->getColsKeys() as $key) {
            if (!in_array($key, $columnsWithVals)) {
                $colObj = $this->getTable()->getColByKey($key);
                $defaultVal = $colObj->getDefault();
                
                
                if ($defaultVal !== null) {
                    $colsArr[] = $colObj->getName();
                    $type = $colObj->getDatatype();
                    
                    if (in_array($type, Column::BOOL_TYPES)) {
                        $this->defaultVals[$key] = $defaultVal ? 1 : 0;
                    } else if ($defaultVal == 'now' || $defaultVal == 'current_timestamp' || $defaultVal == 'now()') {
                        if ($type == 'datetime2' || $type == 'timestamp' || $type == 'datetime') {
                            $this->defaultVals[$key] = date('Y-m-d H:i:s');
                        } else if ($type == 'time') {
                            $this->defaultVals[$key] = date('H:i:s');
                        } else if ($type == 'date') {
                            $this->defaultVals[$key] = date('Y-m-d');
                        }
                    } else {
                        $this->defaultVals[$key] = $defaultVal;
                    }
                }
            }
        }
    }
}
