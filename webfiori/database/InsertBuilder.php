<?php

namespace webfiori\database;

use webfiori\database\mssql\MSSQLColumn;
use webfiori\database\mssql\MSSQLQuery;

/**
 * Description of InsertBuilder
 *
 * @author I.BINALSHIKH
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
     * 
     * @param Table $table
     * @param array $colsAndVals
     */
    public function __construct(Table $table, array $colsAndVals) {
        $this->paramPlaceholder = '?';
        $this->table = $table;
        $this->data = $colsAndVals;
        
        $this->build();
    }
    public function insert(array $colsAndVals, Table $table = null) {
        if ($table !== null) {
            $this->table = $table;
        }
        $this->data = $colsAndVals;
        $this->build();
    }
    public function getPlaceholder() : string {
        return $this->paramPlaceholder;
    }
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
    public function getQuery() : string {
        return $this->query;
    }
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
