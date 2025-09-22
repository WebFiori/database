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
namespace WebFiori\Database;

use Countable;
use Iterator;
use ReturnTypeWillChange;
/**
 * Container for database query results with iteration and mapping capabilities.
 * 
 * This class represents a collection of records returned from SELECT queries.
 * It implements Iterator and Countable interfaces, providing:
 * - Iteration over result records
 * - Counting of total records
 * - Mapping records to objects or transformed data
 * - Array-like access to individual records
 * database after executing a query like a 'select' query.
 *
 * @author Ibrahim
 * 
 */
class ResultSet implements Countable, Iterator {
    /**
     * @var int
     */
    private $cursorPos;
    /**
     * @var array
     */
    private $orgResultRows;
    /**
     * Creates new instance of the class.
     * 
     * @param array $resultArr An array that holds set values.
     * 
     */
    /**
     * Create a new result set with optional initial data.
     * 
     * @param array $resultArr Array of records to initialize the result set with.
     */
    public function __construct(array $resultArr = []) {
        $this->setData($resultArr);
    }
    /**
     * Reset the values in the set to default values.
     * 
     */
    public function clearSet() {
        $this->cursorPos = 0;
        $this->orgResultRows = [];
    }
    /**
     * Return the number of mapped rows in the set.
     * 
     * @return int If no result returned by MySQL server, the method will return -1. If 
     * the executed query returned 0 rows, the method will return 0. Note that 
     * if the mapping function returned other than an array, the method will 
     * always return 0.
     * 
     */
    public function count() : int {
        return $this->getRowsCount();
    }
    #[ReturnTypeWillChange]
    /**
     * Returns the element which exist at current cursor location in the 
     * mapped result.
     * 
     * @return mixed Note that if the mapping function did not return an array, 
     * the method will always return null.
     * 
     */
    public function current() {
        return $this->getRows()[$this->cursorPos];
    }
    /**
     * Filter the records of the result set using a custom callback.
     * 
     * @param callable $filterFunction A PHP function that must return true for
     * the records that will be included. The first argument of the
     * function will always be the record/value that will be mapped. In case
     * of database records, this will be an associative array. The indices
     * are names of columns as they appear in the database. The second
     * argument is the index of the record/value and, the last value will
     * be the original set of records as an array.
     * 
     * @param array $mapArgs Any additional arguments that the developer
     * would like to pass to filtering function.
     * 
     * @return ResultSet The method will return an object of type ResultSet
     * that holds the filtered records.
     */
    public function filter(callable $filterFunction, array $mapArgs = []) : ResultSet {
        $result = [];
        $index = 0;
        $records = $this->getRows();

        foreach ($records as $record) {
            $args = array_merge([$record, $index, $records], $mapArgs);
            $include = call_user_func_array($filterFunction, $args);

            if ($include === true) {
                $result[] = $record;
            }
        }

        return new ResultSet($result);
    }
    /**
     * Returns an array which contains all original records in the set before 
     * mapping.
     * 
     * @return array An array which contains all records in the set.
     * 
     */
    public function getRows() : array {
        return $this->orgResultRows;
    }
    /**
     * Return the number of original rows in the set.
     * 
     * @return int Number of original rows in the set before executing the 
     * mapping function.
     * 
     */
    public function getRowsCount() : int {
        return count($this->orgResultRows);
    }
    #[ReturnTypeWillChange]
    /**
     * Return the key of the current record.
     * 
     * @return int|null Returns an integer on success, or null on failure.
     * 
     */
    public function key() {
        return $this->cursorPos;
    }
    /**
     * Map the records of the result set using a custom callback.
     * 
     * @param callable $mappingFunction A PHP function. The first argument of the
     * function will always be the record/value that will be mapped. In case
     * of database records, this will be an associative array. The indices
     * are names of columns as they appear in the database. The second
     * argument is the index of the record/value and, the last value will
     * be the original set of records as an array.
     * 
     * @param array $mapArgs Any additional arguments that the developer
     * would like to pass to mapping function.
     * 
     * @return ResultSet The method will return an object of type ResultSet
     * that holds the mapped records.
     */
    public function map(callable $mappingFunction, array $mapArgs = []) : ResultSet {
        $result = [];
        $index = 0;
        $records = $this->getRows();

        foreach ($records as $record) {
            $args = array_merge([$record, $index, $records], $mapArgs);
            $result[] = call_user_func_array($mappingFunction, $args);
        }

        return new ResultSet($result);
    }
    #[ReturnTypeWillChange]
    /**
     * Move forward to next record.
     * 
     */
    public function next() {
        $this->cursorPos++;
    }
    #[ReturnTypeWillChange]
    /**
     * Rewind the Iterator to the first record.
     * 
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
     * Returns an array that represents the set.
     * 
     * This method is an alias for the method ResultSet::getRows().
     * 
     * @return array An array that represents the set.
     */
    public function toArray() : array {
        return $this->getRows();
    }
    /**
     * Checks if current position is valid in the iterator.
     * 
     * @return bool Returns true on success or false on failure.
     * 
     */
    public function valid() : bool {
        return $this->key() < $this->count();
    }
}
