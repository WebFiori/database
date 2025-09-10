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
namespace WebFiori\Database;

/**
 * A class which is used to build insert SQL queries for diffrent database engines.
 *
 * @author Ibrahim
 */
abstract class InsertBuilder {
    /**
     * 
     * @var array
     */
    private $cols;
    /**
     * 
     * @var array
     */
    private $data;
    /**
     * 
     * @var array
     */
    private $defaultVals;
    /**
     * 
     * @var string
     */
    private $paramPlaceholder;
    /**
     * 
     * @var string
     */
    private $query;
    /**
     * 
     * @var array
     */
    private $queryParams;
    /**
     * 
     * @var Table
     */
    private $table;
    /**
     * 
     * @var array
     */
    private $vals;
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
     * Returns an array that holds default values for columns that was not
     * specified in the insert.
     * 
     * @return array The indices of the array are columns names and the value
     * of each index is the default value.
     */
    public function getDefaultValues() : array {
        return $this->defaultVals;
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
     * Returns the generated insert query.
     * 
     * @return string The generated SQL query.
     */
    public function getQuery() : string {
        return $this->query;
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
    /**
     * Returns an array that holds sub-associative arrays which has original
     * passed values.
     * 
     * @return array The array will hold sub-associative arrays. The indices
     * of sub-associative arrays are columns keys and each index will have
     * the value of the column.
     */
    public function getRawValues() : array {
        return $this->vals;
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
    public function insert(array $colsAndVals, ?Table $table = null) {
        if ($table !== null) {
            $this->table = $table;
        }
        $this->data = $colsAndVals;
        $this->build();
    }
    /**
     * Construct the array of values which will be used in binding.
     * 
     * The method must be implemented in a way that it returns a structured
     * array of values and bindings based on how the database driver
     * binds values in prepared query.
     */
    abstract function parseValues(array $values);
    /**
     * 
     * @param array $arr
     */
    public function setQueryParams(array $arr) {
        $this->queryParams = $arr;
    }
    private function build() {
        $this->queryParams = [];
        $this->cols = [];
        $this->vals = [];
        $this->defaultVals = [];
        $this->query = 'insert into '.$this->getTable()->getName();
        $colsAndVals = $this->data;
        $this->initValsArr();

        if (isset($colsAndVals['cols']) && isset($colsAndVals['values'])) {
            $this->query .= ' '.$this->buildColsArr()."\nvalues\n";
            $values = trim(str_repeat('?, ', count($this->cols)),', ');
            $multiVals = trim(str_repeat('('.$values."),\n", count($this->vals)), ",\n");
            $this->query .= $multiVals.';';
        } else {
            $this->query .= ' '.$this->buildColsArr();
            $this->cols = array_merge($this->cols, array_keys($this->defaultVals));
            $values = trim(str_repeat('?, ', count($this->cols)),', ');
            $this->query .= ' values ('.$values.');';
        }
        $toPass = [];

        foreach ($this->getRawValues() as $arr) {
            $toPass[] = array_merge($arr, $this->getDefaultValues());
        }
        $this->parseValues($toPass);
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

        return '('.implode(', ', $colsArr).')';
    }
    private function checkColDefault(string $type, string $key, $defaultVal) {
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
    /**
     * Verify and check the columns with no values but have a default value.
     * 
     * The main aim of this method is to place default values for the columns
     * which have no value provided.
     * 
     * @param array $columnsWithVals
     * @param array $colsArr
     */
    private function checkColsWithNoVals(array $columnsWithVals, array &$colsArr) {
        foreach ($this->getTable()->getColsKeys() as $key) {
            if (!in_array($key, $columnsWithVals)) {
                $colObj = $this->getTable()->getColByKey($key);
                $defaultVal = $colObj->getDefault();

                if ($defaultVal !== null) {
                    $colsArr[] = $colObj->getName();
                    $this->checkColDefault($colObj->getDatatype(), $key, $defaultVal);
                }
            }
        }
    }
    private function initValsArr() {
        $colsAndVals = $this->data;

        if (isset($colsAndVals['cols']) && isset($colsAndVals['values'])) {
            $cols = $colsAndVals['cols'];
            $tempVals = $colsAndVals['values'];
            $temp = [];
            $topIndex = 0;

            foreach ($tempVals as $valsArr) {
                $index = 0;
                $temp[] = [];

                foreach ($cols as $colKey) {
                    $temp[$topIndex][$colKey] = $valsArr[$index];
                    $index++;
                }
                $topIndex++;
            }
            $this->vals = $temp;
            $this->cols = $cols;
        } else {
            $this->cols = array_keys($colsAndVals);
            $this->vals = [$colsAndVals];
        }
    }
}
