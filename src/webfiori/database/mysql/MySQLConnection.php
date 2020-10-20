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
namespace webfiori\database\mysql;

use mysqli;
use webfiori\database\AbstractQuery;
use webfiori\database\Connection;
use webfiori\database\ConnectionInfo;
/**
 * A class that represents a connection to MySQL server.
 * 
 * The main aim of this class is to manage the process of connecting to 
 * MySQL server and executing SQL queries.
 *
 * @author Ibrahim
 * 
 * @version 1.0
 */
class MySQLConnection extends Connection {
    private $currentRow;
    private $link;
    private $result;
    private $resultRows;
    public function __construct(ConnectionInfo $connInfo) {
        parent::__construct($connInfo);
        $this->resultRows = [];
    }

    public function connect() {
        $test = false;
        $connInfo = $this->getConnectionInfo();
        set_error_handler(function()
        {
        });
        $this->link = @mysqli_connect($connInfo->getHost(), 
                $connInfo->getUsername(), 
                $connInfo->getPassword(), 
                $connInfo->getDBName(), 
                $connInfo->getPort());
        restore_error_handler();

        if ($this->link instanceof mysqli) {
            $test = true;
            $this->link->set_charset("utf8");
            mysqli_query($this->link, "set character_set_client='utf8'");
            mysqli_query($this->link, "set character_set_results='utf8'");
        } else {
            $this->setErrCode(mysqli_connect_errno());
            $this->setErrMessage(mysqli_connect_error());
        }

        return $test;
    }
    /**
     * Returns the row which the class is pointing to in the result set.
     * 
     * @return array|null an associative array that represents a table row.  
     * If no results are fetched, the method will return null. 
     * 
     * @since 1.0
     */
    public function getRow() {
        if ($this->resultRows == null) {
            $this->getRows();
        }

        if (count($this->resultRows) != 0) {
            if ($this->currentRow == -1) {
                return $this->getRows()[0];
            } else {
                if ($this->currentRow < $this->rows()) {
                    return $this->getRows()[$this->currentRow];
                }
            }
        } else {
            return $this->_getRow();
        }

        return null;
    }
    /**
     * Returns an array which contains all fetched results from the database.
     * 
     * @return array An array which contains all fetched results from the database. 
     * Each row will be an associative array. The index will represents the 
     * column of the table.
     * 
     * @since 1.0
     */
    public function getRows() {
        if ($this->resultRows != null) {
            return $this->resultRows;
        }
        $execResult = $this->result;

        if (function_exists('mysqli_fetch_all')) {
            $rows = $execResult !== null ? mysqli_fetch_all($execResult, MYSQLI_ASSOC) : [];
        } else {
            $rows = [];

            if ($execResult !== null) {
                while ($row = $execResult->fetch_assoc()) {
                    $rows[] = $row;
                }
            }
        }

//        if ($this->getLastQuery()->getMappedEntity() !== null) {
//            $this->resultRows = [];
//
//            foreach ($rows as $row) {
//                $this->resultRows[] = $this->_map($row);
//            }
//        } else {
        $this->resultRows = $rows;
        //}

        return $this->resultRows;
    }
    /**
     * Return the number of rows returned by last query.
     * 
     * @return int If no result returned by MySQL server, the method will return -1. If 
     * the executed query returned 0 rows, the method will return 0.
     * 
     * @since 1.0
     */
    public function getRowsCount() {
        if ($this->result) {
            return count($this->getRows());
        }

        return -1;
    }
    /**
     * Returns the next row that was resulted from executing a query that has 
     * results.
     * 
     * @return array|null The next row in the result set. If no more rows are 
     * in the set, the method will return null.
     * 
     * @since 1.0
     */
    public function nextRow() {
        $this->currentRow++;
        $rows = $this->getRows();

        if (isset($rows[$this->currentRow])) {
            return $rows[$this->currentRow];
        }

        return null;
    }

    public function runQuery(AbstractQuery $query) {
        $this->setLastQuery($query);

        if ($query instanceof MySQLQuery) {
            if (!$query->isBlobInsertOrUpdate()) {
                mysqli_query($this->link, 'set collation_connection =\''.$query->getTable()->getCollation().'\'');
            }
        }
        $qType = $query->getLastQueryType();

        if ($qType == 'insert' || $qType == 'update') {
            return $this->_insertQuery();
        } else {
            if ($qType == 'select' || $qType == 'show'
           || $qType == 'describe') {
                return $this->_selectQuery();
            } else {
                return $this->_otherQuery();
            }
        }
    }
    /**
     * Helper method that is used to initialize the array of rows in case 
     * of first call to the method getRow()
     * 
     * @param type $retry
     * 
     * @return type
     */
    private function _getRow($retry = 0) {
        if (count($this->resultRows) != 0) {
            return $this->getRows()[0];
        } else {
            if ($retry == 1) {
                return null;
            } else {
                $this->getRows();
                $retry++;

                return $this->_getRow($retry);
            }
        }
    }
    private function _insertQuery() {
        $query = $this->getLastQuery();
        $retVal = false;
        $r = mysqli_query($this->link, $query->getQuery());

        if (!$r) {
            $this->setErrMessage($this->link->error);
            $this->setErrCode($this->link->errno);
            $this->result = null;
            $r = mysqli_multi_query($this->link, $query->getQuery());

            if ($r) {
                $this->setErrMessage('NO ERRORS');
                $this->setErrCode(0);
                $this->result = null;
                $retVal = true;
            }
        } else {
            $retVal = true;
        }
        $query->setIsBlobInsertOrUpdate(false);

        return $retVal;
    }
    private function _otherQuery() {
        $this->result = null;
        $query = $this->getLastQuery()->getQuery();
        $r = mysqli_query($this->link, $query);
        $retVal = false;

        if (!$r) {
            $this->setErrMessage($this->link->error);
            $this->setErrCode($this->link->errno);
            $this->result = null;
            $r = mysqli_multi_query($this->link, $query);

            if ($r) {
                $this->setErrMessage('NO ERRORS');
                $this->setErrCode(0);
                $this->result = null;
                $retVal = true;
            }
        } else {
            $this->setErrMessage('NO ERRORS');
            $this->setErrCode(0);
            $this->result = null;
            $this->getLastQuery()->setIsBlobInsertOrUpdate(false);

            $retVal = true;
        }
        $this->getLastQuery()->setIsBlobInsertOrUpdate(false);

        return $retVal;
    }
    private function _selectQuery() {
        $r = mysqli_query($this->link, $this->getLastQuery()->getQuery());

        if ($r) {
            $this->result = $r;
            $this->setErrCode(0);

            return true;
        } else {
            $this->setErrMessage($this->link->error);
            $this->setErrCode($this->link->errno);
            $this->result = null;

            return false;
        }
    }
}
