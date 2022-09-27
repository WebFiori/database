<?php
/**
 * This file is licensed under MIT License.
 * 
 * Copyright (c) 2019 Ibrahim BinAlshikh
 * 
 * For more information on the license, please visit: 
 * https://github.com/WebFiori/.github/blob/main/LICENSE
 * 
 */
namespace webfiori\database;

use Countable;
use Iterator;
/**
 * A class which is used to represent a data set which was fetched from the 
 * database after executing a query like a 'select' query.
 *
 * @author Ibrahim
 * 
 * @version 1.0
 */
class ResultSet implements Countable, Iterator {
    private $cursorPos;
    private $dataChangedBeforeMapping;
    private $mapArgs;
    private $mappingFunction;
    private $orgResultRows;
    private $resultRows;
    /**
     * Creates new instance of the class.
     * 
     * @param array $resultArr An array that holds original result set.
     * 
     * @param callable $mappingFunction A PHP function which is used to modify 
     * original result set and shape it as needed. The method can have two 
     * arguments, first one is the original data set and the second is an optional 
     * array of arguments.
     * 
     * @param array $mapArgs An optional array of arguments to pass on to the 
     * mapping function.
     */
    public function __construct(array $resultArr = [], callable $mappingFunction = null, array $mapArgs = []) {
        $this->setData($resultArr);
        $this->mapArgs = $mapArgs;

        if ($mappingFunction === null) {
            $this->setMappingFunction(function ($record)
            {
                return $record;
            }, $this->mapArgs);
        } else {
            $this->mappingFunction = $mappingFunction;
        }
    }
    /**
     * Reset the values in the set to default values.
     * 
     * @since 1.0
     */
    public function clearSet() {
        $this->cursorPos = 0;
        $this->orgResultRows = [];
        $this->resultRows = [];
        $this->dataChangedBeforeMapping = true;
    }
    /**
     * Return the number of mapped rows in the set.
     * 
     * @return int If no result returned by MySQL server, the method will return -1. If 
     * the executed query returned 0 rows, the method will return 0. Note that 
     * if the mapping function returned other than an array, the method will 
     * always return 0.
     * 
     * @since 1.0
     */
    public function count() : int {
        return $this->getMappedRowsCount();
    }
    #[\ReturnTypeWillChange]
    /**
     * Returns the element which exist at current cursor location in the 
     * mapped result.
     * 
     * @return mixed Note that if the mapping function did not return an array, 
     * the method will always return null.
     * 
     * @since 1.0
     */
    public function current() {
        return $this->getMappedRows()[$this->cursorPos];
    }
    /**
     * Returns the records which was generated after calling the map 
     * function.
     * 
     * 
     * @return mixed The return value of this method will depend on how the 
     * developer implemented the mapping function. By default, the method will
     * return an array that holds fetched records information.
     * 
     * @since 1.0
     */
    public function getMappedRows() : array {
        if (!$this->dataChangedBeforeMapping) {
            return $this->resultRows;
        }
        $result = [];
        $index = 0;
        $records = $this->getRows();
        
        foreach ($records as $record) {
            $args = array_merge([$record, $index, $records], $this->mapArgs);
            $result[] = call_user_func_array($this->mappingFunction, $args);
        }

        $this->resultRows = $result;
        $this->dataChangedBeforeMapping = false;

        return $result;
    }
    /**
     * Returns the number of records which was generated after calling the map 
     * function.
     * 
     * The number of records might be less or more based on how the developer 
     * have implemented the mapping function. Note that if the mapping function 
     * did not return an array, the method will return 1.
     * 
     * @return int Number of records after mapping.
     * 
     * @since 1.0
     */
    public function getMappedRowsCount() : int {
        return count($this->getMappedRows());
    }
    /**
     * Returns an array which contains all original records in the set before 
     * mapping.
     * 
     * @return array An array which contains all records in the set.
     * 
     * @since 1.0
     */
    public function getRows() {
        return $this->orgResultRows;
    }
    /**
     * Return the number of original rows in the set.
     * 
     * @return int Number of original rows in the set before executing the 
     * mapping function.
     * 
     * @since 1.0
     */
    public function getRowsCount() : int {
        return count($this->orgResultRows);
    }
    #[\ReturnTypeWillChange]
    /**
     * Return the key of the current record.
     * 
     * @return int|null Returns an integer on success, or null on failure.
     * 
     * @since 1.0
     */
    public function key() {
        return $this->cursorPos;
    }
    /**
     * Map the records of the result set using a mapping function.
     * 
     * @param callable $mappingFunction A PHP function. The first argument of the
     * function will always be an associative array that represents the
     * record. The second argument will always be the index of the record.
     * the third argument will be an array of sub-arrays that holds
     * raw data.
     * 
     * @param array $mapArgs Any additional arguments that the developer
     * would like to pass to mapping function.
     * 
     * @return array The method will return an array of mapped records.
     */
    public function map(callable $mappingFunction, array $mapArgs = []) : array {
        $this->setMappingFunction($mappingFunction, $mapArgs);

        return $this->getMappedRows();
    }
    #[\ReturnTypeWillChange]
    /**
     * Move forward to next record.
     * 
     * @since 1.0
     */
    public function next() {
        $this->cursorPos++;
    }
    #[\ReturnTypeWillChange]
    /**
     * Rewind the Iterator to the first record.
     * 
     * @since 1.0
     */
    public function rewind() {
        $this->cursorPos = 0;
    }
    /**
     * Sets the data at which the set will use in its operations.
     * 
     * @param array $records An array that represents the records.
     */
    public function setData(array $records) {
        $this->clearSet();
        $this->orgResultRows = $records;
    }
    /**
     * Sets a custom callback which can be used to process result set and 
     * map the records as desired.
     * 
     * @param callable $callback A PHP function. The first argument of the
     * function will always be an associative array that represents the
     * record. The second argument will always be the index of the record.
     * the third argument will be an array of sub-arrays that holds
     * raw data.
     * 
     * @param array $otherParams An array that holds extra arguments which can 
     * be passed to the mapping function.
     * 
     * @return boolean If the function is set, the method will return true. 
     * If not, the method will return false.
     * 
     * @since 1.0
     */
    public function setMappingFunction(callable $callback, array $otherParams = []) {
        $this->mapArgs = $otherParams;
        $this->mappingFunction = $callback;
        $this->dataChangedBeforeMapping = true;
    }
    /**
     * Checks if current position is valid in the iterator.
     * 
     * @return boolean Returns true on success or false on failure.
     * 
     * @since 1.0
     */
    public function valid() : bool {
        return $this->key() < $this->count();
    }
}
