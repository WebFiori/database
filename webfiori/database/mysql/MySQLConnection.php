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
namespace webfiori\database\mysql;

use mysqli;
use mysqli_stmt;
use webfiori\database\AbstractQuery;
use webfiori\database\Connection;
use webfiori\database\ConnectionInfo;
use webfiori\database\DatabaseException;
use webfiori\database\ResultSet;
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
    private $isCollationSet;
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
     * @since 1.0
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
     * @since 1.0.1
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
     * @since 1.0
     */
    public function runQuery(AbstractQuery $query = null) {
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
            $this->isCollationSet = true;
        }
        $qType = $query->getLastQueryType();
        $this->addToExecuted($query->getQuery());

        try {
            if ($qType == 'insert' || $qType == 'update') {
                return $this->runInsertQuery($query);
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
    private function runInsertQuery() {
        $insertBuilder = $this->getLastQuery()->getInsertBuilder();
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

        $retVal = false;

        if (!$r) {
            $this->setErrMessage($this->link->error);
            $this->setErrCode($this->link->errno);

            $r = mysqli_multi_query($this->link, $this->getLastQuery()->getQuery());

            if ($r) {
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
    private function runOtherQuery() {
        $query = $this->getLastQuery()->getQuery();
        $retVal = false;
        
        $sql = $this->getLastQuery()->getQuery();
        $params = $this->getLastQuery()->getBindings()['bind'];
        $values = array_merge($this->getLastQuery()->getBindings()['values']);
        
        if (count($values) != 0) {
            $sqlStatement = mysqli_prepare($this->link, $sql);
            $sqlStatement->bind_param($params, ...$values);
            $r = $sqlStatement->execute();
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
