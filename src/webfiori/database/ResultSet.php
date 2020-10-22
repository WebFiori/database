<?php

namespace webfiori\database;

use Countable;
use Iterator;
/**
 * Description of ResultSet
 *
 * @author Ibrahim
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
     * Returns the number of rows which where generated after finished executing 
     * the mapping function.
     * 
     * @return int Number of rows which where generated after finished executing 
     * the mapping function on the original result.
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
     * @return type
     */
    public function current() {
        return $this->getMappedRows()[$this->cursorPos];
    }
    
    public function key() {
        return $this->cursorPos;
    }

    public function next() {
        $this->cursorPos++;
    }

    public function rewind() {
        $this->cursorPos = 0;
    }

    public function valid() {
        return $this->key() < $this->count();
    }

}
