<?php

/**
 * This file is licensed under MIT License.
 * 
 * Copyright (c) 2019-present WebFiori Framework
 * 
 * For more information on the license, please visit: 
 * https://github.com/WebFiori/.github/blob/main/LICENSE
 * 
 */
namespace WebFiori\Database\MsSql;

use WebFiori\Database\AbstractQuery;
use WebFiori\Database\Connection;
use WebFiori\Database\ConnectionInfo;
use WebFiori\Database\DatabaseException;
use WebFiori\Database\MultiResultSet;
use WebFiori\Database\ResultSet;
/**
 * A class that represents a connection to MSSQL server.
 * 
 * The main aim of this class is to manage the process of connecting to 
 * MSSQL server and executing SQL queries.
 *
 * @author Ibrahim
 * 
 */
class MSSQLConnection extends Connection {
    private $isTransactionStarted;
    /**
     * The id of the last insert, captured in the same batch as the insert.
     * 
     * @var string|int
     */
    private $lastInsertId = 0;
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
     */
    public function __construct(ConnectionInfo $connInfo) {
        parent::__construct($connInfo);
        $this->isTransactionStarted = false;
    }
    /**
     * Close database connection.
     * 
     */
    public function __destruct() {
        $this->close();
    }
    /**
     * Starts SQL server transaction.
     * 
     * Note that calling this method multiple times will have no effect on number
     * of created transactions.
     * 
     * @param string|null $name This parameter is ignored.
     * 
     * @throws DatabaseException If the method was not able to start the transaction.
     */
    public function beginTransaction(?string $name = null) {
        if ($this->isTransactionStarted) {
            return;
        }
        $r = sqlsrv_begin_transaction($this->link);

        if (!$r) {
            $this->setSqlErr();
            throw new DatabaseException($this->getSQLState().' - '.$this->getLastErrMessage());
        }
        $this->isTransactionStarted = true;
    }

    /**
     * Close the MSSQL connection and release resources.
     */
    public function close(): void {
        if ($this->link !== null) {
            @sqlsrv_close($this->link);
            $this->link = null;
        }
    }
    /**
     * Commit transaction changes to database.
     * 
     * Note that if no transaction is started, calling this method will have
     * no effect.
     * 
     * @param string|null $name This parameter is ignored.
     * 
     * @throws DatabaseException If the method was not able to commit the transaction.
     */
    public function commit(?string $name = null) {
        if (!$this->isTransactionStarted) {
            return;
        }
        $r = sqlsrv_commit($this->link);

        if (!$r) {
            $this->setSqlErr();
            throw new DatabaseException($this->getSQLState().' - '.$this->getLastErrMessage());
        }
        $this->isTransactionStarted = false;
    }
    /**
     * Connect to MSSQL database.
     * 
     * @return bool If the connection was established, the method will return 
     * true. If the attempt to connect fails, the method will return false.
     * 
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
     * Returns the ID of the last inserted row.
     * 
     * The id is read in the same batch as the INSERT, because
     * SCOPE_IDENTITY() only sees identities created in the same scope.
     * 
     * - Identity column: the generated value as int. This also works for raw
     * INSERT statements.
     * - Query-builder insert into a table with a single-column key that is not
     * an identity (for example a UUID): the key of the inserted row as string.
     * This includes values generated by the server, such as
     * UNIQUEIDENTIFIER DEFAULT NEWID().
     * - Anything else: 0.
     * 
     * @return string|int The last insert ID, or 0 if not available.
     */
    public function getLastInsertId(): string|int {
        if ($this->link === null) {
            return 0;
        }

        return $this->lastInsertId;
    }
    /**
     * Returns SQL state in case of warnings or errors.
     * 
     * @return string|null
     * 
     */
    public function getSQLState() {
        return $this->sqlState;
    }

    /**
     * Check if the MSSQL connection is still alive.
     * 
     * @return bool True if the connection is active.
     */
    public function isAlive(): bool {
        if ($this->link === null) {
            return false;
        }

        $result = @sqlsrv_query($this->link, 'SELECT 1');

        if ($result !== false) {
            sqlsrv_free_stmt($result);

            return true;
        }

        return false;
    }
    /**
     * Roll back a transaction.
     * 
     * Note that if no transaction is started, calling this method will have
     * no effect.
     * 
     * @param string|null $name This parameter is ignored.
     * 
     * @throws DatabaseException If the method was not able to rollback the transaction.
     */
    public function rollBack(?string $name = null) {
        if (!$this->isTransactionStarted) {
            return;
        }
        $r = sqlsrv_rollback($this->link);

        if (!$r) {
            $this->setSqlErr();
            throw new DatabaseException($this->getSQLState().' - '.$this->getLastErrMessage());
        }
        $this->isTransactionStarted = false;
    }
    /**
     * Execute MSSQL query.
     * 
     * @param AbstractQuery $query A query builder that has the generated MSSQL 
     * /**
     * Execute a query and return execution status.
     * 
     * @param AbstractQuery|null $query The query to execute. If null, uses the last set query.
     * 
     * @return bool True if the query executed successfully, false if there were errors.
     */
    public function runQuery(?AbstractQuery $query = null): bool {
        $this->addToExecuted($query->getQuery());
        $this->setLastQuery($query);

        $qType = $query->getLastQueryType();

        if ($qType == 'insert') {
            return $this->runInsertQuery();
        } else if ($qType == 'update') {
            return $this->runUpdateQuery();
        } else if ($qType == 'select' || $qType == 'show' || $qType == 'describe') {
            return $this->runSelectQuery();
        } else if ($qType == 'dbcc') {
            $this->runDBCC();

            return true;
        } else {
            return $this->runOtherQuery();
        }
    }
    private function checkInsertOrUpdateResult($r) {
        if (!$r) {
            $this->setSqlErr();

            return false;
        }

        return true;
    }
    private function runDBCC() {
        $sql = $this->getLastQuery()->getQuery();
        $queryBulder = $this->getLastQuery();

        sqlsrv_query($this->link, $sql, $queryBulder->getBindings());

        return true;
    }
    /**
     * Finds the generated id in the results of an insert batch.
     * 
     * The batch has the form 'insert; select <id> as wf_last_id'. Every
     * result is consumed so that errors raised by later statements are not lost.
     */
    private function readCapturedId($stm) {
        $id = null;

        do {
            if (sqlsrv_has_rows($stm)) {
                $row = sqlsrv_fetch_array($stm, SQLSRV_FETCH_ASSOC);

                if (is_array($row) && array_key_exists('wf_last_id', $row)) {
                    $id = $row['wf_last_id'];
                }
            }
            $next = sqlsrv_next_result($stm);
        } while ($next);

        return $id;
    }
    private function runInsertQuery() {
        $this->lastInsertId = 0;
        $insertBuilder = $this->getLastQuery()->getInsertBuilder();
        $sql = $this->getLastQuery()->getQuery();

        if ($insertBuilder === null) {
            // Raw INSERT. Only an identity value can be known.
            return $this->runOtherQuery(true);
        }

        $params = $insertBuilder->getQueryParams();
        $mode = 'none';
        $known = null;

        if ($this->tableHasIdentity($insertBuilder->getTable())) {
            $sql .= "\nSELECT SCOPE_IDENTITY() AS wf_last_id;";
            $mode = 'identity';
        } else if ($insertBuilder->getKeyColumn() !== null) {
            $known = $insertBuilder->getKeyValue();

            if ($known === null) {
                // The key is generated by the server (for example NEWID()).
                // OUTPUT ... INTO also works on tables that have triggers.
                $keyName = '['.$insertBuilder->getKeyColumn()->getNormalName().']';
                $sql = 'DECLARE @wf_ids TABLE (n INT IDENTITY(1,1), id NVARCHAR(4000));'."\n"
                    .$insertBuilder->getQueryWithClause('OUTPUT CONVERT(NVARCHAR(4000), INSERTED.'.$keyName.') INTO @wf_ids (id)')
                    ."\nSELECT TOP 1 id AS wf_last_id FROM @wf_ids ORDER BY n DESC;";
                $mode = 'output';
            }
        }

        $stm = sqlsrv_prepare($this->link, $sql, $params);

        if ($stm === false) {
            $this->setSqlErr();

            return false;
        }
        $r = sqlsrv_execute($stm);

        if (!$r) {
            return $this->checkInsertOrUpdateResult($r);
        }

        if ($mode !== 'none') {
            $id = $this->readCapturedId($stm);

            if ($id !== null) {
                $this->lastInsertId = $mode === 'identity' ? (int) $id : (string) $id;
            }
        } else if ($known !== null) {
            $this->lastInsertId = $known;
        }
        sqlsrv_free_stmt($stm);

        return $this->checkInsertOrUpdateResult($r);
    }
    private function runOtherQuery(bool $captureIdentity = false) {
        $query = $this->getLastQuery();
        $sql = $query->getQuery();

        if ($captureIdentity) {
            // Same batch as the statement, so SCOPE_IDENTITY() can see it.
            $sql = rtrim($sql, "; \t\r\n")."\n;SELECT SCOPE_IDENTITY() AS wf_last_id;";
        }
        $r = sqlsrv_query($this->link, $sql, $query->getBindings());

        if (!is_resource($r)) {
            $this->setSqlErr();

            return false;
        }

        // Collect all result sets
        $allResults = [];

        // First result set
        if (sqlsrv_has_rows($r)) {
            $data = [];

            while ($row = sqlsrv_fetch_array($r, SQLSRV_FETCH_ASSOC)) {
                $data[] = $row;
            }
            $allResults[] = $data;
        } else {
            $allResults[] = [];
        }

        // Additional result sets
        while (sqlsrv_next_result($r)) {
            $data = [];

            while ($row = sqlsrv_fetch_array($r, SQLSRV_FETCH_ASSOC)) {
                $data[] = $row;
            }
            $allResults[] = $data;
        }

        if ($captureIdentity) {
            $idResult = array_pop($allResults);
            $id = $idResult[0]['wf_last_id'] ?? null;
            $this->lastInsertId = $id === null ? 0 : (int) $id;
        }

        // Set result
        if (count($allResults) > 1) {
            $this->setResultSet(new MultiResultSet($allResults));
        } else {
            $this->setResultSet(new ResultSet($allResults[0]));
        }

        return true;
    }
    private function runSelectQuery() {
        $query = $this->getLastQuery();
        $sql = $query->getQuery();
        $r = sqlsrv_query($this->link, $sql, $query->getBindings());

        if (!is_resource($r)) {
            $this->setSqlErr();

            return false;
        }

        // Collect all result sets
        $allResults = [];

        // First result set
        $data = [];

        while ($row = sqlsrv_fetch_array($r, SQLSRV_FETCH_ASSOC)) {
            $data[] = $row;
        }
        $allResults[] = $data;

        // Additional result sets
        while (sqlsrv_next_result($r)) {
            $data = [];

            while ($row = sqlsrv_fetch_array($r, SQLSRV_FETCH_ASSOC)) {
                $data[] = $row;
            }
            $allResults[] = $data;
        }

        // Set result
        if (count($allResults) > 1) {
            $this->setResultSet(new MultiResultSet($allResults));
        } else {
            $this->setResultSet(new ResultSet($allResults[0]));
        }

        return true;
    }
    private function runUpdateQuery() {
        $params = $this->getLastQuery()->getBindings();
        $sql = $this->getLastQuery()->getQuery();

        if (count($params) != 0) {
            $stm = sqlsrv_prepare($this->link, $sql, $params);
            $r = sqlsrv_execute($stm);
        } else {
            $r = sqlsrv_query($this->link, $sql);
        }

        return $this->checkInsertOrUpdateResult($r);
    }
    private function setSqlErr() {
        $allErrs = sqlsrv_errors(SQLSRV_ERR_ERRORS);
        $lastErr = $allErrs[count($allErrs) - 1];

        if ($lastErr['SQLSTATE'] == '01000' && $lastErr['code'] == 3621) {
            $lastErr = $allErrs[count($allErrs) - 2];
            $this->sqlState = $lastErr['SQLSTATE'];
            $this->setErrMessage('The statement has been terminated due to following: '.$lastErr['message']);
            $this->setErrCode($lastErr['code']);
        } else if ($lastErr['SQLSTATE'] == '42000' && $lastErr['code'] == 8180) {
            $lastErr = $allErrs[count($allErrs) - 2];
            $this->sqlState = $lastErr['SQLSTATE'];
            $this->setErrMessage('Statement(s) could not be prepared due to the following: '.$lastErr['message']);
            $this->setErrCode($lastErr['code']);
        } else {
            $this->setErrCode($lastErr['code']);
            $this->sqlState = $lastErr['SQLSTATE'];
            $this->setErrMessage($lastErr['message']);
            $this->setErrCode($lastErr['code']);
        }
    }
    private function tableHasIdentity($table): bool {
        foreach ($table->getCols() as $col) {
            if ($col instanceof MSSQLColumn && $col->isIdentity()) {
                return true;
            }
        }

        return false;
    }
}
