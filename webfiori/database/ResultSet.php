<?php
/**
 * MIT License
 *
 * Copyright (c) 2019, WebFiori Framework.
 * 
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
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
    public function __construct(array $resultArr = [], $mappingFunction = null, array $mapArgs = []) {
        $this->setData($resultArr);
        $this->mapArgs = $mapArgs;

        if (!$this->setMappingFunction($mappingFunction)) {
            $this->setMappingFunction(function ($data)
            {
                return $data;
            }, $this->mapArgs);
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
        $args = array_merge([$this->getRows()], $this->mapArgs);
        $result = call_user_func_array($this->mappingFunction, $args);

        if (gettype($result) != 'array') {
            throw new DatabaseException('Map function is expected to return an array. '.gettype($result).' is returned.');
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
     * @param Closure $mappingFunction A PHP function. The first argument of the
     * function will always be the fetched raw records as an array. Each
     * index of the array will have the record as associative array,
     * 
     * @param array $mapArgs Any additional arguments that the developer
     * would like to pass to mapping function.
     * 
     * @return array The method will return an array of mapped records.
     */
    public function map($mappingFunction, array $mapArgs = []) : array {
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
     * @param Closure $callback A PHP function. The function will have one 
     * parameter which is the raw result set as an array.
     * 
     * @param array $otherParams An array that holds extra arguments which can 
     * be passed to the mapping function.
     * 
     * @return boolean If the function is set, the method will return true. 
     * If not, the method will return false.
     * 
     * @since 1.0
     */
    public function setMappingFunction($callback, array $otherParams = []) {
        if (is_callable($callback)) {
            $this->mapArgs = $otherParams;
            $this->mappingFunction = $callback;
            $this->dataChangedBeforeMapping = true;

            return true;
        }

        return false;
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
