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
use webfiori\database\ResultSet;
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
    private $link;
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
        
        set_error_handler(function()
        {
        });
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
    public function runQuery(AbstractQuery $query) {
        $this->setLastQuery($query);

        if ($query instanceof MySQLQuery && !$query->isBlobInsertOrUpdate()) {
            $collation = filter_var($query->getTable()->getCollation());
            mysqli_query($this->link, 'set collation_connection =\''.$collation.'\'');
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

    private function _insertQuery() {
        $query = $this->getLastQuery();
        $retVal = false;
        $r = mysqli_query($this->link, $query->getQuery());

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
        $r = mysqli_query($this->link, $query);
        $retVal = false;

        if (!$r) {
            $this->setErrMessage($this->link->error);
            $this->setErrCode($this->link->errno);
            $r = mysqli_multi_query($this->link, $query);

            if ($r) {
                $this->setErrMessage('NO ERRORS');
                $this->setErrCode(0);
                $retVal = true;
            }
        } else {
            $this->setErrMessage('NO ERRORS');
            $this->setErrCode(0);
            $this->getLastQuery()->setIsBlobInsertOrUpdate(false);

            $retVal = true;
        }
        $this->getLastQuery()->setIsBlobInsertOrUpdate(false);

        return $retVal;
    }
    private function _selectQuery() {
        $r = mysqli_query($this->link, $this->getLastQuery()->getQuery());

        if ($r) {
            $this->setErrCode(0);
            
            if (function_exists('mysqli_fetch_all')) {
                $rows = mysqli_fetch_all($r, MYSQLI_ASSOC);
            } else {
                $rows = [];

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
