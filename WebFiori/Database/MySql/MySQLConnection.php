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
namespace WebFiori\Database\MySql;

use mysqli;
use mysqli_stmt;
use WebFiori\Database\MultiResultSet;
use WebFiori\Database\AbstractQuery;
use WebFiori\Database\Connection;
use WebFiori\Database\ConnectionInfo;
use WebFiori\Database\DatabaseException;
use WebFiori\Database\ResultSet;
/**
 * MySQL database connection handler with prepared statement support.
 * 
 * This class manages MySQL database connections and provides:
 * - Connection establishment and management
 * - Prepared statement execution with parameter binding
 * - Transaction support (begin, commit, rollback)
 * - Query execution and result handling
 * - Connection cleanup and resource management
 * 
 * The main aim of this class is to manage the process of connecting to 
 * MySQL server and executing SQL queries.
 *
 * @author Ibrahim
 * 
 */
class MySQLConnection extends Connection {
    private $isCollationSet;
    /**
     *
     * @var mysqli|null
     * 
     */
    private $link;
    /**
     *
     * @var mysqli_stmt|null
     * 
     */
    private $sqlStm;
    /**
     * Creates new instance of the class.
     * 
     * @param ConnectionInfo $connInfo An object that holds connection
     * information.
     */
    public function __construct(ConnectionInfo $connInfo) {
        parent::__construct($connInfo);
        $this->isCollationSet = false;
    }
    /**
     * Close database connection.
     */
    public function __destruct() {
        mysqli_close($this->link);
    }

    public function beginTransaction(?string $name = null) {
        //The null check is for php<8
        $message = 'Unable to start transaction.';

        if ($name !== null) {
            if (!$this->link->begin_transaction(0, $name)) {
                throw new DatabaseException($message);
            }
        } else {
            if (!$this->link->begin_transaction()) {
                throw new DatabaseException($message);
            }
        }
    }

    public function commit(?string $name = null) {
        //The null check is for php<8
        $message = 'Unable to commit transaction.';

        if ($name !== null) {
            if (!$this->link->commit(0, $name)) {
                throw new DatabaseException($message);
            }
        } else {
            if (!$this->link->commit()) {
                throw new DatabaseException($message);
            }
        }
    }
    /**
     * Connect to MySQL database.
     * 
     * @return bool If the connection was established, the method will return 
     * true. If the attempt to connect fails, the method will return false.
     * 
     */
    public function connect() : bool {
        $test = false;
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        $connInfo = $this->getConnectionInfo();

        $host = $connInfo->getHost();
        $port = $connInfo->getPort();
        $user = $connInfo->getUsername();
        $pass = $connInfo->getPassword();
        $dbName = $connInfo->getDBName();

        if (!function_exists('mysqli_connect')) {
            throw new DatabaseException('mysqli extension is missing.');
        }

        try {
            $this->link = mysqli_connect($host, $user, $pass, null, $port);
        } catch (\Exception $ex) {
            $this->setErrCode($ex->getCode());
            $this->setErrMessage($ex->getCode());
        }

        if ($this->link instanceof mysqli) {
            $test = mysqli_select_db($this->link, $dbName);

            if ($test) {
                $this->link->set_charset("utf8");
                $this->addToExecuted("set character_set_client='utf8'");
                mysqli_query($this->link, "set character_set_client='utf8'");
                $this->addToExecuted("set character_set_results='utf8'");
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
     */
    public function getMysqli() {
        return $this->link;
    }

    public function rollBack(?string $name = null) {
        //The null check is for php<8
        $message = 'Unable to roll back transaction.';

        if ($name !== null) {
            if (!$this->link->rollback(0, $name)) {
                throw new DatabaseException($message);
            }
        } else {
            if (!$this->link->rollback()) {
                throw new DatabaseException($message);
            }
        }
    }

    /**
     * Execute MySQL query.
     * 
     * @param AbstractQuery $query A query builder that has the generated MySQL 
     * query.
    /**
     * Execute a query and return execution status.
     * 
     * @param AbstractQuery|null $query The query to execute. If null, uses the last set query.
     * 
     * @return bool True if the query executed successfully, false if there were errors.
     */
    public function runQuery(?AbstractQuery $query = null): bool {
        $this->setLastQuery($query);

        if ($query instanceof MySQLQuery && !$query->isBlobInsertOrUpdate() && !$this->isCollationSet) {
            $table = $query->getTable();

            if ($table !== null && $table instanceof MySQLTable) {
                $collation = $query->getTable()->getCollation();
            } else {
                $collation = 'utf8mb4_unicode_520_ci';
            }
            $this->addToExecuted('set collation_connection = ?');
            $stm = mysqli_prepare($this->link, 'set collation_connection = ?');
            $stm->bind_param('s', $collation);
            $stm->execute();

            if ($stm) {
                mysqli_stmt_close($stm);
            }
            $this->isCollationSet = true;
        }
        $qType = $query->getLastQueryType();
        $this->addToExecuted($query->getQuery());

        try {
            $result = false;
            if ($qType == 'insert') {
                $result = $this->runInsertQuery();
            } else if ($qType == 'update') {
                $result = $this->runUpdateQuery();
            } else if ($qType == 'select' || $qType == 'show' || $qType == 'describe') {
                $result = $this->runSelectQuery();
            } else {
                $result = $this->runOtherQuery();
            }
            $query->resetBinding();
            return $result;
        } catch (\Exception $ex) {
            $this->setErrCode($ex->getCode());
            $this->setErrMessage($ex->getMessage());
            throw new DatabaseException($ex->getCode().' - '.$ex->getMessage(), $ex->getCode(), $this->getLastQuery()->getQuery(), $ex);
        }
    }
    private function chechInsertOrUpdateResult($r) {
        $retVal = false;

        if (!$r) {
            $this->setErrMessage($this->link->error);
            $this->setErrCode($this->link->errno);

            $r = mysqli_multi_query($this->link, $this->getLastQuery()->getQuery());

            if ($r) {
                // Clean up multi-query results to prevent "Commands out of sync"
                while (mysqli_next_result($this->link)) {
                    // Consume all result sets
                }
                $this->setErrMessage('NO ERRORS');
                $this->setErrCode(0);

                $retVal = true;
            }
        } else {
            $retVal = true;
        }
        $this->getLastQuery()->setIsBlobInsertOrUpdate(false);

        return $retVal;
    }
    private function runInsertQuery() {
        $insertBuilder = $this->getLastQuery()->getInsertBuilder();

        if ($insertBuilder === null) {
            return $this->runOtherQuery();
        } 

        $sqlStatement = mysqli_prepare($this->link, $insertBuilder->getQuery());
        $insertParams = $insertBuilder->getQueryParams()['bind'];
        $values = array_merge($insertBuilder->getQueryParams()['values']);
        $bindValues = [];

        foreach ($values as $valuesArr) {
            foreach ($valuesArr as $val) {
                $bindValues[] = $val;
            }
        }
        $sqlStatement->bind_param($insertParams, ...$bindValues);

        $r = $sqlStatement->execute();

        if ($sqlStatement) {
            mysqli_stmt_close($sqlStatement);
        }

        $retVal = false;

        return $this->chechInsertOrUpdateResult($r);
    }
    private function runOtherQuery() {
        $sql = $this->getLastQuery()->getQuery();
        $params = $this->getLastQuery()->getBindings()['bind'];
        $values = array_merge($this->getLastQuery()->getBindings()['values']);
        $successExec = false;
        $r = null;
        // Execute query
        if (count($values) != 0 && !empty($params)) {
            $paramCount = substr_count($sql, '?');
            if ($paramCount == count($values) && strlen($params) == count($values)) {
                $stmt = mysqli_prepare($this->link, $sql);
                mysqli_stmt_bind_param($stmt, $params, ...$values);
                $successExec = mysqli_stmt_execute($stmt);
                $r = mysqli_stmt_get_result($stmt);
                mysqli_stmt_close($stmt);
            } else {
                $r = mysqli_query($this->link, $sql);
            }
        } else {
            $r = mysqli_query($this->link, $sql);
        }

        if (($r === null || $r === false) && !$successExec) {
            $this->setErrMessage($this->link->error);
            $this->setErrCode($this->link->errno);
            return false;
        }

        // Collect all result sets
        $allResults = [];
        
        // First result set
        if (is_object($r) && method_exists($r, 'fetch_assoc')) {
            $rows = mysqli_fetch_all($r, MYSQLI_ASSOC);
            $allResults[] = $rows;
            mysqli_free_result($r);
        }

        // Additional result sets
        while (mysqli_more_results($this->link)) {
            mysqli_next_result($this->link);
            if ($result = mysqli_store_result($this->link)) {
                $rows = mysqli_fetch_all($result, MYSQLI_ASSOC);
                $allResults[] = $rows;
                mysqli_free_result($result);
            }
        }

        // Set result
        if (count($allResults) > 1) {
            $this->setResultSet(new MultiResultSet($allResults));
        } else if (count($allResults) == 1) {
            $this->setResultSet(new ResultSet($allResults[0]));
        }

        $this->setErrCode(0);
        return true;
    }
    /**
     * Get the mysqli link for testing purposes.
     * 
     * @return mysqli The mysqli connection link
     */
    public function getMysqliLink() {
        return $this->link;
    }
    
    private function runSelectQuery() {
        $sql = $this->getLastQuery()->getQuery();
        $params = $this->getLastQuery()->getBindings()['bind'];
        $values = array_merge($this->getLastQuery()->getBindings()['values']);

        // Execute query
        if (count($values) != 0) {
            $stmt = mysqli_prepare($this->link, $sql);
            mysqli_stmt_bind_param($stmt, $params, ...$values);
            mysqli_stmt_execute($stmt);
            $r = mysqli_stmt_get_result($stmt);
            mysqli_stmt_close($stmt);
        } else {
            $r = mysqli_query($this->link, $sql);
        }

        if (!$r) {
            $this->setErrMessage($this->link->error);
            $this->setErrCode($this->link->errno);
            return false;
        }

        // Collect all result sets
        $allResults = [];
        
        // First result set
        $rows = mysqli_fetch_all($r, MYSQLI_ASSOC);
        $allResults[] = $rows;
        mysqli_free_result($r);

        // Additional result sets
        while (mysqli_more_results($this->link)) {
            mysqli_next_result($this->link);
            if ($result = mysqli_store_result($this->link)) {
                $rows = mysqli_fetch_all($result, MYSQLI_ASSOC);
                $allResults[] = $rows;
                mysqli_free_result($result);
            }
        }

        // Set result
        if (count($allResults) > 1) {
            $this->setResultSet(new MultiResultSet($allResults));
        } else {
            $this->setResultSet(new ResultSet($allResults[0]));
        }

        $this->setErrCode(0);
        return true;
    }
    private function runUpdateQuery() {
        $sqlStatement = mysqli_prepare($this->link, $this->getLastQuery()->getQuery());
        $params = $this->getLastQuery()->getBindings();

        if (count($params['values']) != 0) {
            $sqlStatement->bind_param($params['bind'], ...$params['values']);
        }
        $r = $sqlStatement->execute();

        if ($sqlStatement) {
            mysqli_stmt_close($sqlStatement);
        }

        return $this->chechInsertOrUpdateResult($r);
    }
}
