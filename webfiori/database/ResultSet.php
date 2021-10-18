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
    public function __construct(array $resultArr, $mappingFunction = null, array $mapArgs = []) {
        $this->orgResultRows = $resultArr;
        $this->resultRows = $resultArr;
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
    public function count() {
        if (gettype($this->getMappedRowsCount()) == 'array') {
            return $this->getMappedRowsCount();
        }

        return 0;
    }

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
        if (gettype($this->getMappedRows()) == 'array') {
            return $this->getMappedRows()[$this->cursorPos];
        }
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
    public function getMappedRows() {
        return $this->resultRows;
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
    public function getMappedRowsCount() {
        if (gettype($this->resultRows) == 'array') {
            return count($this->getMappedRows());
        } else {
            return 1;
        }
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
    public function getRowsCount() {
        return count($this->orgResultRows);
    }
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
     * Move forward to next record.
     * 
     * @since 1.0
     */
    public function next() {
        $this->cursorPos++;
    }
    /**
     * Rewind the Iterator to the first record.
     * 
     * @since 1.0
     */
    public function rewind() {
        $this->cursorPos = 0;
    }
    /**
     * Sets a custom callback which can be used to process result set and 
     * map the records to PHP objects as desired.
     * 
     * @param Closure $callback A PHP function. The function will have one 
     * parameter which is the raw result set as an array.
     * 
     * @return boolean If the function is set, the method will return true. 
     * If not, the method will return false.
     * 
     * @param array $otherParams An array that holds extra arguments which can 
     * be passed to the mapping function.
     * 
     * @since 1.0
     */
    public function setMappingFunction($callback, array $otherParams = []) {
        if (is_callable($callback)) {
            $this->mapArgs = $otherParams;
            $this->mappingFunction = $callback;
            $args = array_merge([$this->getRows()], $this->mapArgs);
            $result = call_user_func_array($this->mappingFunction, $args);

            if (gettype($result) == 'array') {
                $this->resultRows = $result;

                return true;
            }
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
    public function valid() {
        return $this->key() < $this->count();
    }
}
