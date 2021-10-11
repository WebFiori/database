<?php
namespace webfiori\database\mssql;

use webfiori\database\Connection;
use webfiori\database\AbstractQuery;
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
     * Close database connection.
     * 
     * @since 1.0
     */
    public function __destruct() {
        sqlsrv_close($this->link);
    }
    private function _bindAndExc() {
        $stm = $this->prepare($this->getLastQuery()->getParams());
        
        return $stm->execute();
    }
    /**
     * Connect to MSSQL database.
     * 
     * @return boolean If the connection was established, the method will return 
     * true. If the attempt to connect fails, the method will return false.
     * 
     * @since 1.0
     */
    public function connect() {
        if (!function_exists('sqlsrv_connect')) {
            $this->setErrCode(-1);
            $this->setErrMessage('Microsoft SQL Server driver is missing.');
            return false;
        }
        ini_set('mssql.charset', 'UTF-8');
        $connObj = $this->getConnectionInfo();
        $connInfo = [
            'UID' => $connObj->getUsername(),
            'PWD' => $connObj->getPassword(),
            'Database' => $connObj->getDBName(),
            'CharacterSet'=>'UTF-8',
            'ReturnDatesAsStrings' => true
        ];
        $servName = $connInfo->getHost().', '. $connInfo->getPort();
        $this->link = sqlsrv_connect($servName, array_merge($connInfo, $connObj->getExtars()));
        if ($this->link) {
            return true;
        }
        $this->_setErr();
        
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
     * 
     * @param array $params
     * 
     * @return boolean|resource
     * 
     * @since 1.0
     */
    public function prepare($params = []) {
        $stm = sqlsrv_prepare($this->link, $this->getLastQuery()->getQuery(), $params);
        if (!$stm) {
            $this->_setErr();
            return false;
        }
        return $stm;
    }
    /**
     * Execute MSSQL query.
     * 
     * @param AbstractQuery $query A query builder that has the generated MSSQL 
     * query.
     * 
     * @return boolean If the query successfully executed, the method will return 
     * true. Other than that, the method will return false.
     * 
     * @since 1.0
     */
    public function runQuery(AbstractQuery $query = null) {
        $this->setLastQuery($query);
        
        $qType = $query->getLastQueryType();

        if ($qType == 'insert' || $qType == 'update') {
            return $this->_insertQuery();
        } else if ($qType == 'select' || $qType == 'show'|| $qType == 'describe') {
            return $this->_selectQuery();
        } else {
            return $this->_otherQuery();
        }
    }
    private function _otherQuery() {
        $r = sqlsrv_query($this->link, $this->getLastQuery()->getQuery());
        if(!is_resource($r)){ 
            $this->_setErr();
            return false;
        }
        return true;
    }
    private function _selectQuery() {
        if ($this->getLastQuery()->isPrepareBeforeExec()) {
            $r = $this->_bindAndExc();
        } else {
            $r = sqlsrv_query($this->link, $this->getLastQuery()->getQuery());
        }
        if(is_resource($r)){
            $data = [];
            while($row = sqlsrv_fetch_array($r,SQLSRV_FETCH_ASSOC)){
                $data[] = $row;
            }
            $this->setResultSet(new ResultSet($data));
            return true;
        } else {
            $this->_setErr();
            return false;
        }
    }
    private function _insertQuery() {
        if ($this->getLastQuery()->isPrepareBeforeExec()) {
            $r = $this->_bindAndExc();
        } else {
            $r = sqlsrv_query($this->link, $this->getLastQuery()->getQuery());
        }
        if(!is_resource($r)){ 
            $this->_setErr();
            return false;
        }
        return true;
    }
    private function _setErr() {
        $allErrs = sqlsrv_errors(SQLSRV_ERR_ERRORS);
        $lastErr = $allErrs[count($allErrs) - 1];
        $this->sqlState = $lastErr['SQLSTATE'];
        $this->setErrMessage($lastErr['message']);
        $this->setErrCode($lastErr['code']);
    }
}
