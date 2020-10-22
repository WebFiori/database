<?php

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
class ResultSet implements Countable, Iterator{
    private $resultRows;
    private $orgResultRows;
    private $cursorPos;
    private $mappingFunction;

    public function __construct(array $resultArr, $mappingFunction = null) {
        $this->orgResultRows = $resultArr;
        $this->resultRows = $resultArr;
        
        if (!$this->setMappingFunction($mappingFunction)) {
            $this->setMappingFunction(function ($data) {
                return $data;
            });
        }
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
     * @since 1.0
     */
    public function setMappingFunction($callback) {
        if (is_callable($callback)) {
            $this->mappingFunction = $callback;
            $result = call_user_func_array($this->mappingFunction, [$this->getRows()]);
            
            if (gettype($result) == 'array') {
                $this->resultRows = $result;
                return true;
            }
        }
        return false;
    }
    /**
     * Returns the records which was generated after calling the map 
     * function.
     * 
     * 
     * @return array An array that holds the records which was generated after 
     * the mapping.
     * 
     * @since 1.0
     */
    public function getMappedRows() {
        return $this->resultRows;
    }
    /**
     * Returns an array which contains all records in the set.
     * 
     * @return array An array which contains all records in the set.
     * 
     * @since 1.0
     */
    public function getRows() {
        return $this->orgResultRows;
    }
    /**
     * Return the number of mapped rows in the set.
     * 
     * @return int If no result returned by MySQL server, the method will return -1. If 
     * the executed query returned 0 rows, the method will return 0.
     * 
     * @since 1.0
     */
    public function count() {
        return $this->getMappedRowsCount();
    }
    /**
     * Returns the number of records which was generated after calling the map 
     * function.
     * 
     * The number of records might be less or more based on how the developer 
     * have implemented the mapping function.
     * 
     * @return int Number of records after mapping.
     * 
     * @since 1.0
     */
    public function getMappedRowsCount() {
        return count($this->getMappedRows());
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
     * 
     * @return mixed|array
     * 
     * @since 1.0
     */
    public function current() {
        return $this->getMappedRows()[$this->cursorPos];
    }
    /**
     * 
     * @return int
     * 
     * @since 1.0
     */
    public function key() {
        return $this->cursorPos;
    }
    /**
     * @since 1.0
     */
    public function next() {
        $this->cursorPos++;
    }
    /**
     * 
     * @since 1.0
     */
    public function rewind() {
        $this->cursorPos = 0;
    }
    /**
     * 
     * @return boolean
     * 
     * @since 1.0
     */
    public function valid() {
        return $this->key() < $this->count();
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
}
