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
use mysqli_stmt;
use webfiori\database\AbstractQuery;
use webfiori\database\Connection;
use webfiori\database\ResultSet;
use webfiori\database\DatabaseException;
/**
 * A class that represents a connection to MySQL server.
 * 
 * The main aim of this class is to manage the process of connecting to 
 * MySQL server and executing SQL queries.
 *
 * @author Ibrahim
 * 
 * @version 1.0.2
 */
class MySQLConnection extends Connection {
    /**
     *
     * @var mysqli|null
     * 
     * @since 1.0 
     */
    private $link;
    /**
     *
     * @var mysqli_stmt|null
     * 
     * @since 1.0.2 
     */
    private $sqlStm;
    /**
     * Close database connection.
     */
    public function __destruct() {
        mysqli_close($this->link);
    }
    /**
     * Connect to MySQL database.
     * 
     * @return boolean If the connection was established, the method will return 
     * true. If the attempt to connect fails, the method will return false.
     * 
     * @since 1.0
     */
    public function connect() {
        $test = false;
        $connInfo = $this->getConnectionInfo();

        $host = $connInfo->getHost();
        $port = $connInfo->getPort();
        $user = $connInfo->getUsername();
        $pass = $connInfo->getPassword();
        $dbName = $connInfo->getDBName();
        
        if (!function_exists('mysqli_connect')) {
            throw new DatabaseException('mysqli extension is missing.');
        }
        set_error_handler(null);
        $this->link = mysqli_connect($host, $user, $pass, null, $port);
        restore_error_handler();

        if ($this->link instanceof mysqli) {
            $test = mysqli_select_db($this->link, $dbName);

            if ($test) {
                $this->link->set_charset("utf8");
                mysqli_query($this->link, "set character_set_client='utf8'");
                mysqli_query($this->link, "set character_set_results='utf8'");
            }
        } else {
            $this->setErrCode(mysqli_connect_errno());
            $this->setErrMessage(mysqli_connect_error());
        }

        return $test;
    }
    /**
     * Returns the instance at which the connection uses to execute 
     * database queries.
     * 
     * @return mysqli|null The object which is used to connect to the database.
     * 
     * @since 1.0.1
     */
    public function getMysqli() {
        return $this->link;
    }
    /**
     * Prepare and bind SQL statement.
     * 
     * @param array $queryParams An array that holds sub associative arrays that holds 
     * values. Each sub array must have two indices:
     * <ul>
     * <li><b>value</b>: The value to bind.</li>
     * <li><b>type</b>: The type of the value as a character. can be one of 4 values: 
     * <ul>
     * <li>i: corresponding variable has type integer</li>
     * <li>d: corresponding variable has type double</li>
     * <li>s: corresponding variable has type string</li>
     * <li>b: corresponding variable is a blob and will be sent in packets</li>
     * </ul>
     * </li>
     * <ul>
     * 
     * @return boolean|mysqli_stmt If the statement was successfully prepared, the method 
     * will return true. If an error happens, the method will return false.
     * 
     * @since 1.0.2
     */
    public function prepare(array $queryParams = []) {
        $queryObj = $this->getLastQuery();

        if ($queryObj !== null) {
            $queryStr = $queryObj->getQuery();
            $sqlStatement = mysqli_prepare($this->link, $queryStr);

            if (gettype($sqlStatement) == 'object') {
                foreach ($queryParams as $subArr) {
                    $value = isset($subArr['value']) ? $subArr['value'] : null;
                    $type = isset($subArr['type']) ? $subArr['type'] : 's';
                    $sqlStatement->bind_param("$type", $value);
                }

                return $sqlStatement;
            }
        }

        return false;
    }

    /**
     * Execute MySQL query.
     * 
     * @param AbstractQuery $query A query builder that has the generated MySQL 
     * query.
     * 
     * @return boolean If the query successfully executed, the method will return 
     * true. Other than that, the method will return true.
     * 
     * @since 1.0
     */
    public function runQuery(AbstractQuery $query = null) {
        $this->setLastQuery($query);

        if ($query instanceof MySQLQuery && !$query->isBlobInsertOrUpdate()) {
            $table = $query->getTable();

            if ($table !== null && $table instanceof MySQLTable) {
                $collation = $query->getTable()->getCollation();
            } else {
                $collation = 'utf8mb4_unicode_520_ci';
            }
            $stm = mysqli_prepare($this->link, 'set collation_connection = ?');
            $stm->bind_param('s', $collation);
            $stm->execute();
        }
        $qType = $query->getLastQueryType();

        if ($qType == 'insert' || $qType == 'update') {
            return $this->_insertQuery();
        } else if ($qType == 'select' || $qType == 'show'|| $qType == 'describe') {
            return $this->_selectQuery();
        } else {
            return $this->_otherQuery();
        }
    }
    private function _bindAndExc() {
        $stm = $this->prepare($this->getLastQuery()->getParams());

        return $stm->execute();
    }
    private function _insertQuery() {
        $query = $this->getLastQuery();

        if ($query->isPrepareBeforeExec()) {
            $r = $this->_bindAndExc();
        } else {
            $r = mysqli_query($this->link, $query->getQuery());
        }
        $retVal = false;

        if (!$r) {
            $this->setErrMessage($this->link->error);
            $this->setErrCode($this->link->errno);

            $r = mysqli_multi_query($this->link, $query->getQuery());

            if ($r) {
                $this->setErrMessage('NO ERRORS');
                $this->setErrCode(0);

                $retVal = true;
            }
        } else {
            $retVal = true;
        }
        $query->setIsBlobInsertOrUpdate(false);

        return $retVal;
    }
    private function _otherQuery() {
        $query = $this->getLastQuery()->getQuery();
        $retVal = false;

        if ($this->getLastQuery()->isPrepareBeforeExec()) {
            $r = $this->_bindAndExc();
        } else {
            if (!$this->getLastQuery()->isMultiQuery()) {
                $r = mysqli_query($this->link, $query);
            } else {
                $r = mysqli_multi_query($this->link, $query);
            }
        }

        if (!$r) {
            $this->setErrMessage($this->link->error);
            $this->setErrCode($this->link->errno);
        } else {
            $this->setErrMessage('NO ERRORS');
            $this->setErrCode(0);
            $this->getLastQuery()->setIsBlobInsertOrUpdate(false);
            $retVal = true;
        }
        $r = mysqli_query($this->link, $query);

        if ($r === true || gettype($r) == 'object') {
            $retVal = true;
        }

        return $retVal;
    }
    private function _selectQuery() {
        if ($this->getLastQuery()->isPrepareBeforeExec()) {
            $r = $this->_bindAndExc();
        } else {
            $r = mysqli_query($this->link, $this->getLastQuery()->getQuery());
        }

        if ($r) {
            $this->setErrCode(0);
            $rows = [];

            if ($this->getLastQuery()->isPrepareBeforeExec()) {
                $this->sqlStm->store_result();
                $r = $this->sqlStm->get_result();
            }

            if (function_exists('mysqli_fetch_all')) {
                $rows = mysqli_fetch_all($r, MYSQLI_ASSOC);
            } else {
                while ($row = $r->fetch_assoc()) {
                    $rows[] = $row;
                }
            }
            $this->setResultSet(new ResultSet($rows));

            return true;
        } else {
            $this->setErrMessage($this->link->error);
            $this->setErrCode($this->link->errno);

            return false;
        }
    }
}
