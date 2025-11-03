<?php

/**
 * This file is licensed under MIT License.
 * 
 * Copyright (c) 2025 WebFiori Framework
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
 * Container for multiple database result sets.
 * 
 * This class represents a collection of ResultSet objects returned from 
 * multi-result queries such as stored procedures or multi-statement queries.
 * It implements Iterator and Countable interfaces for easy traversal.
 *
 * @author Ibrahim
 */
class MultiResultSet implements Countable, Iterator {
    /**
     * @var int Current position in the result sets array
     */
    private $cursorPos;
    
    /**
     * @var ResultSet[] Array of ResultSet objects
     */
    private $resultSets;
    
    /**
     * Creates new instance of MultiResultSet.
     * 
     * @param array $resultSets Array of arrays or ResultSet objects
     */
    public function __construct(array $resultSets = []) {
        $this->cursorPos = 0;
        $this->resultSets = [];
        
        foreach ($resultSets as $resultData) {
            $this->addResultSet($resultData);
        }
    }
    
    /**
     * Add a result set to the collection.
     * 
     * @param array|ResultSet $resultData Array of records or ResultSet object
     */
    public function addResultSet($resultData): void {
        if ($resultData instanceof ResultSet) {
            $this->resultSets[] = $resultData;
        } else {
            $this->resultSets[] = new ResultSet($resultData);
        }
    }
    
    /**
     * Get the number of result sets.
     * 
     * @return int Number of result sets
     */
    public function count(): int {
        return count($this->resultSets);
    }
    
    /**
     * Get the current ResultSet object.
     * 
     * @return ResultSet|null Current ResultSet or null if invalid position
     */
    #[ReturnTypeWillChange]
    public function current() {
        return $this->valid() ? $this->resultSets[$this->cursorPos] : null;
    }
    
    /**
     * Get a specific result set by index.
     * 
     * @param int $index Index of the result set
     * @return ResultSet|null ResultSet at the specified index or null if not found
     */
    public function getResultSet(int $index): ?ResultSet {
        return isset($this->resultSets[$index]) ? $this->resultSets[$index] : null;
    }
    
    /**
     * Get all result sets.
     * 
     * @return ResultSet[] Array of all ResultSet objects
     */
    public function getResultSets(): array {
        return $this->resultSets;
    }
    
    /**
     * Get the current cursor position.
     * 
     * @return int Current position
     */
    #[ReturnTypeWillChange]
    public function key() {
        return $this->cursorPos;
    }
    
    /**
     * Move to the next result set.
     */
    #[ReturnTypeWillChange]
    public function next(): void {
        $this->cursorPos++;
    }
    
    /**
     * Reset cursor to the first result set.
     */
    #[ReturnTypeWillChange]
    public function rewind(): void {
        $this->cursorPos = 0;
    }
    
    /**
     * Get total number of records across all result sets.
     * 
     * @return int Total number of records
     */
    public function getTotalRecordCount(): int {
        $total = 0;
        foreach ($this->resultSets as $resultSet) {
            $total += $resultSet->getRowsCount();
        }
        return $total;
    }
    
    /**
     * Check if current position is valid.
     * 
     * @return bool True if current position is valid
     */
    public function valid(): bool {
        return $this->cursorPos >= 0 && $this->cursorPos < count($this->resultSets);
    }
}
