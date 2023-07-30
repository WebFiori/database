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
namespace webfiori\database\mssql;

use webfiori\database\AbstractQuery;
use webfiori\database\Connection;
use webfiori\database\ConnectionInfo;
use webfiori\database\DatabaseException;
use webfiori\database\ResultSet;
/**
 * A class that represents a connection to MSSQL server.
 * 
 * The main aim of this class is to manage the process of connecting to 
 * MSSQL server and executing SQL queries.
 *
 * @author Ibrahim
 * 
 * @version 1.0
 */
class MSSQLConnection extends Connection {
    private $link;
    private $sqlState;
    /**
     * Creates new instance of the class.
     * 
     * @param ConnectionInfo $connInfo An object that contains database connection 
     * information.
     * 
     * @throws DatabaseException If the connection to the database fails, the method 
     * will throw an exception.
     * 
     * @since 1.0
     */
    public function __construct(ConnectionInfo $connInfo) {
        parent::__construct($connInfo);
    }
    /**
     * Close database connection.
     * 
     * @since 1.0
     */
    public function __destruct() {
        sqlsrv_close($this->link);
    }
    /**
     * Connect to MSSQL database.
     * 
     * @return bool If the connection was established, the method will return 
     * true. If the attempt to connect fails, the method will return false.
     * 
     * @since 1.0
     */
    public function connect() : bool {
        if (!function_exists('sqlsrv_connect')) {
            $this->setErrCode(-1);
            $this->setErrMessage('Microsoft SQL Server driver is missing.');

            return false;
        }
        ini_set('mssql.charset', 'UTF-8');
        $connObj = $this->getConnectionInfo();

        if ($connObj->getUsername() === null) {
            $connInfo = [
                'Database' => $connObj->getDBName(),
                'CharacterSet' => 'UTF-8',
                'ReturnDatesAsStrings' => true
            ];
        } else {
            $connInfo = [
                'UID' => $connObj->getUsername(),
                'PWD' => $connObj->getPassword(),
                'Database' => $connObj->getDBName(),
                'CharacterSet' => 'UTF-8',
                'ReturnDatesAsStrings' => true
            ];
        }

        //Needs more debugging
        //If port is added on localhost, it always fail
        $servName = $connObj->getHost();//.", ".$connObj->getPort();
        $extras = $connObj->getExtars();
        unset($extras['connection-name']);
        $this->link = sqlsrv_connect($servName, array_merge($connInfo, $extras));

        if ($this->link) {
            return true;
        }
        $this->setSqlErr();

        return false;
    }
    /**
     * Returns SQL state in case of warnings or errors.
     * 
     * @return string|null
     * 
     * @since 1.0
     */
    public function getSQLState() {
        return $this->sqlState;
    }
    /**
     * Execute MSSQL query.
     * 
     * @param AbstractQuery $query A query builder that has the generated MSSQL 
     * query.
     * 
     * @return bool If the query successfully executed, the method will return 
     * true. Other than that, the method will return false.
     * 
     * @since 1.0
     */
    public function runQuery(AbstractQuery $query = null) {
        $this->addToExecuted($query->getQuery());
        $this->setLastQuery($query);

        $qType = $query->getLastQueryType();

        if ($qType == 'insert' || $qType == 'update') {
            return $this->runInsertQuery();
        } else {
            if ($qType == 'select' || $qType == 'show' || $qType == 'describe') {
                return $this->runSelectQuery();
            } else {
                return $this->runOtherQuery();
            }
        }
    }
    private function runInsertQuery() {
        $insertBuilder = $this->getLastQuery()->getInsertBuilder();
        $sql = $this->getLastQuery()->getQuery();
        if ($insertBuilder !== null) {
            
            $params = $insertBuilder->getQueryParams();

            $stm = sqlsrv_prepare($this->link, $sql, $params);
            $r = sqlsrv_execute($stm);
        } else {
            $params = $this->getLastQuery()->getBindings();
             
            if (count($params) != 0) {
                $stm = sqlsrv_prepare($this->link, $sql, $params);
                $r = sqlsrv_execute($stm);
            } else {
                $r = sqlsrv_query($this->link, $sql);
            }
        }

        if (!$r) {
            $this->setSqlErr();

            return false;
        }

        return true;
    }
    private function runOtherQuery() {
        $sql = $this->getLastQuery()->getQuery();
        $queryBulder = $this->getLastQuery();
        
        $r = sqlsrv_query($this->link, $sql, $queryBulder->getBindings());
            
        if (!is_resource($r)) {
            $this->setSqlErr();

            return false;
        }

        return true;
    }
    private function runSelectQuery() {
        $queryBulder = $this->getLastQuery();
        $sql = $queryBulder->getQuery();
        
        $r = sqlsrv_query($this->link, $sql, $queryBulder->getBindings());
        

        if (is_resource($r)) {
            $data = [];

            while ($row = sqlsrv_fetch_array($r,SQLSRV_FETCH_ASSOC)) {
                $data[] = $row;
            }
            $this->setResultSet(new ResultSet($data));

            return true;
        } else {
            $this->setSqlErr();

            return false;
        }
    }
    private function setSqlErr() {
        $allErrs = sqlsrv_errors(SQLSRV_ERR_ERRORS);
        $lastErr = $allErrs[count($allErrs) - 1];

        if (strpos($lastErr['message'], 'The statement has been terminated') === false) {
            $this->sqlState = $lastErr['SQLSTATE'];
            $this->setErrMessage($lastErr['message']);
            $this->setErrCode($lastErr['code']);
        } else {
            $lastErr = $allErrs[count($allErrs) - 2];
            $this->sqlState = $lastErr['SQLSTATE'];
            $this->setErrMessage($lastErr['message']);
            $this->setErrCode($lastErr['code']);
        }
    }

    public function beginTransaction(string $name = null) {
        $r = sqlsrv_begin_transaction($this->link);

        if (!$r) {
            $this->setSqlErr();
            throw new DatabaseException($this->getSQLState().' - '.$this->getLastErrMessage());
        }
    }

    public function commit(string $name = null) {
        $r = sqlsrv_commit($this->link);

        if (!$r) {
            $this->setSqlErr();
            throw new DatabaseException($this->getSQLState().' - '.$this->getLastErrMessage());
        }
    }

    public function rollBack(string $name = null) {
        $r = sqlsrv_rollback($this->link);

        if (!$r) {
            $this->setSqlErr();
            throw new DatabaseException($this->getSQLState().' - '.$this->getLastErrMessage());
        }
    }
}
