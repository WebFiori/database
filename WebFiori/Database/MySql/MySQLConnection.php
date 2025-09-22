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

    /**
     * Execute MySQL query.
     * 
     * @param AbstractQuery $query A query builder that has the generated MySQL 
     * query.
     * 
     * @return bool If the query successfully executed, the method will return 
     * true. Other than that, the method will return true.
     * 
     */
    public function runQuery(?AbstractQuery $query = null) {
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
            if ($qType == 'insert') {
                return $this->runInsertQuery();
            } else if ($qType == 'update') {
                return $this->runUpdateQuery();
            } else if ($qType == 'select' || $qType == 'show' || $qType == 'describe') {
                return $this->runSelectQuery();
            } else {
                return $this->runOtherQuery();
            }
        } catch (\Exception $ex) {
            $this->setErrCode($ex->getCode());
            $this->setErrMessage($ex->getMessage());
            throw new DatabaseException($ex->getCode().' - '.$ex->getMessage(), $ex->getCode(), $this->getLastQuery()->getQuery(), $ex);
        }
        $query->resetBinding();
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
            return false;
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
        $query = $this->getLastQuery()->getQuery();
        $retVal = false;
        
        $sql = $this->getLastQuery()->getQuery();
        $params = $this->getLastQuery()->getBindings()['bind'];
        $values = array_merge($this->getLastQuery()->getBindings()['values']);
        
        if (count($values) != 0 && !empty($params)) {
            // Count the number of ? placeholders in the SQL
            $paramCount = substr_count($sql, '?');
            
            // Only use prepared statements if parameter counts match
            if ($paramCount == count($values) && strlen($params) == count($values)) {
                $sqlStatement = mysqli_prepare($this->link, $sql);
                $sqlStatement->bind_param($params, ...$values);
                $r = $sqlStatement->execute();
                if ($sqlStatement) {
                    mysqli_stmt_close($sqlStatement);
                }
            } else {
                // Fall back to regular query if there's a mismatch
                $r = mysqli_query($this->link, $sql);
            }
        } else {
            if (!$this->getLastQuery()->isMultiQuery()) {
                $r = mysqli_query($this->link, $query);
            } else {
                $r = mysqli_multi_query($this->link, $query);
                // Clean up multi-query results to prevent "Commands out of sync"
                if ($r) {
                    while (mysqli_next_result($this->link)) {
                        // Consume all result sets
                    }
                }
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

        if ($r === true || gettype($r) == 'object') {
            $retVal = true;
        }

        return $retVal;
    }
    private function runSelectQuery() {
        
        $sql = $this->getLastQuery()->getQuery();
        $params = $this->getLastQuery()->getBindings()['bind'];
        $values = array_merge($this->getLastQuery()->getBindings()['values']);
        
        if (count($values) != 0) {
            $sqlStatement = mysqli_prepare($this->link, $sql);
            $sqlStatement->bind_param($params, ...$values);
            $r = $sqlStatement->execute();
            if ($r) {
                $r = mysqli_stmt_get_result($sqlStatement);
            }
            if ($sqlStatement) {
                mysqli_stmt_close($sqlStatement);
            }
        } else {
            $r = mysqli_query($this->link, $this->getLastQuery()->getQuery());
        }
        
        

        if ($r) {
            $this->setErrCode(0);
            $rows = [];

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
}
