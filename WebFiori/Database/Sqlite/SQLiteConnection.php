<?php

/**
 * This file is licensed under MIT License.
 * 
 * Copyright (c) 2026-present WebFiori Framework
 * 
 * For more information on the license, please visit: 
 * https://github.com/WebFiori/.github/blob/main/LICENSE
 * 
 */
namespace WebFiori\Database\Sqlite;

use SQLite3;
use SQLite3Result;
use SQLite3Stmt;
use WebFiori\Database\AbstractQuery;
use WebFiori\Database\Connection;
use WebFiori\Database\ConnectionInfo;
use WebFiori\Database\DatabaseException;
use WebFiori\Database\ResultSet;

/**
 * A class that represents a connection to a SQLite database.
 * 
 * This class uses the native PHP sqlite3 extension to manage connections
 * and execute queries. The database file path is taken from
 * {@see ConnectionInfo::getDBName()} — use ':memory:' for in-memory databases.
 * 
 * On connection, the following PRAGMAs are enabled:
 * - foreign_keys = ON (enforce FK constraints)
 * - journal_mode = WAL (better concurrent read performance)
 *
 * @author Ibrahim
 */
class SQLiteConnection extends Connection {
    /**
     * The SQLite3 connection instance.
     * 
     * @var SQLite3|null
     */
    private ?SQLite3 $link = null;

    /**
     * The current prepared statement.
     * 
     * @var SQLite3Stmt|null
     */
    private ?SQLite3Stmt $stmt = null;

    /**
     * Creates a new SQLite connection instance.
     * 
     * The database path is read from the connection info's database name.
     * Use ':memory:' for an in-memory database or a file path for persistent storage.
     * 
     * @param ConnectionInfo $connInfo An object that holds connection information.
     * The database name should be the file path or ':memory:'.
     * 
     * @throws DatabaseException If the connection to the database fails.
     */
    public function __construct(ConnectionInfo $connInfo) {
        parent::__construct($connInfo);
    }

    /**
     * Closes the connection when the object is destroyed.
     */
    public function __destruct() {
        $this->close();
    }

    /**
     * Starts a new database transaction.
     * 
     * @param string|null $name Not used for SQLite. Included for interface compatibility.
     */
    public function beginTransaction(?string $name = null) {
        $this->link->exec('BEGIN TRANSACTION');
    }

    /**
     * Closes the SQLite connection and releases resources.
     * 
     * After calling this method, the connection should not be used for queries.
     */
    public function close(): void {
        if ($this->link !== null) {
            $this->link->close();
            $this->link = null;
        }
    }

    /**
     * Commits the current transaction.
     * 
     * @param string|null $name Not used for SQLite. Included for interface compatibility.
     */
    public function commit(?string $name = null) {
        $this->link->exec('COMMIT');
    }

    /**
     * Establishes a connection to the SQLite database.
     * 
     * Opens the database file specified in the connection info's database name.
     * Enables foreign key enforcement and WAL journal mode for better performance.
     * 
     * @return bool True if the connection was established successfully, false otherwise.
     */
    public function connect(): bool {
        $dbPath = $this->getConnectionInfo()->getDBName();

        $this->link = new SQLite3($dbPath);
        $this->link->enableExceptions(true);
        $this->link->exec('PRAGMA foreign_keys = ON');
        $this->link->exec('PRAGMA journal_mode = WAL');

        return true;
    }

    /**
     * Returns the last auto-generated row ID from an INSERT operation.
     * 
     * @return int The row ID of the most recent successful INSERT, or 0 if
     * no connection is established.
     */
    public function getLastInsertId(): int {
        return $this->link !== null ? $this->link->lastInsertRowID() : 0;
    }

    /**
     * Returns the raw SQLite3 connection object.
     * 
     * This can be used for operations not directly supported by this class.
     * 
     * @return SQLite3|null The underlying SQLite3 instance, or null if not connected.
     */
    public function getLink(): ?SQLite3 {
        return $this->link;
    }

    /**
     * Checks if the SQLite connection is still alive and usable.
     * 
     * @return bool True if the connection is active and can execute queries, false otherwise.
     */
    public function isAlive(): bool {
        if ($this->link === null) {
            return false;
        }

        try {
            $this->link->query('SELECT 1');
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Rolls back the current transaction.
     * 
     * @param string|null $name Not used for SQLite. Included for interface compatibility.
     */
    public function rollBack(?string $name = null) {
        $this->link->exec('ROLLBACK');
    }

    /**
     * Executes the given query or the last set query.
     * 
     * If the query has bindings, it will be executed as a prepared statement.
     * For SELECT queries, the result set is populated and can be retrieved
     * via {@see Connection::getLastResultSet()}.
     * 
     * @param AbstractQuery|null $query The query to execute. If null, the last
     * set query will be executed.
     * 
     * @return bool True if the query was executed successfully, false otherwise.
     * 
     * @throws DatabaseException If query execution fails.
     */
    public function runQuery(?AbstractQuery $query = null): bool {
        if ($query !== null) {
            $this->setLastQuery($query);
        }

        $queryObj = $this->getLastQuery();

        if ($queryObj === null) {
            $this->setErrCode(-1);
            $this->setErrMessage('No query to execute.');

            return false;
        }

        $sql = $queryObj->getQuery();
        $this->addToExecuted($sql);

        try {
            // For insert queries, get bindings from the insert builder
            $insertBuilder = $queryObj->getInsertBuilder();

            if ($insertBuilder !== null && $queryObj->getLastQueryType() === 'insert') {
                $bindings = $insertBuilder->getQueryParams();
            } else {
                $bindings = $queryObj->getBindings();
            }

            if (!empty($bindings)) {
                $result = $this->runPrepared($sql, $bindings, $queryObj);
            } else {
                $result = $this->runDirect($sql, $queryObj);
            }

            // Reset bindings after execution to prevent accumulation
            $queryObj->resetBinding();

            return $result;
        } catch (\Exception $e) {
            $this->setErrCode($e->getCode());
            $this->setErrMessage($e->getMessage());

            throw new DatabaseException($e->getMessage(), $e->getCode());
        }
    }

    /**
     * Executes a query directly without prepared statement binding.
     * 
     * @param string $sql The SQL query string.
     * @param AbstractQuery $queryObj The query object for type detection.
     * 
     * @return bool True on success, false on failure.
     */
    private function runDirect(string $sql, AbstractQuery $queryObj): bool {
        $type = $queryObj->getLastQueryType();

        if ($type === 'select' || $type === 'show') {
            $result = $this->link->query($sql);
            $this->setResultSet($this->fetchResult($result));
            $result->finalize();
        } else {
            $this->link->exec($sql);
        }

        $this->setErrCode(0);
        $this->setErrMessage('NO ERROR');

        return true;
    }

    /**
     * Executes a query as a prepared statement with parameter binding.
     * 
     * Binds values using appropriate SQLite3 types (INTEGER, FLOAT, TEXT, NULL).
     * Boolean values are cast to integers (0/1).
     * 
     * @param string $sql The SQL query string with ? placeholders.
     * @param array $bindings The values to bind to the placeholders.
     * @param AbstractQuery $queryObj The query object for type detection.
     * 
     * @return bool True on success, false on failure.
     */
    private function runPrepared(string $sql, array $bindings, AbstractQuery $queryObj): bool {
        $this->stmt = $this->link->prepare($sql);

        foreach ($bindings as $index => $value) {
            $paramIndex = $index + 1;

            if ($value === null) {
                $this->stmt->bindValue($paramIndex, null, SQLITE3_NULL);
            } elseif (is_int($value)) {
                $this->stmt->bindValue($paramIndex, $value, SQLITE3_INTEGER);
            } elseif (is_float($value)) {
                $this->stmt->bindValue($paramIndex, $value, SQLITE3_FLOAT);
            } elseif (is_bool($value)) {
                $this->stmt->bindValue($paramIndex, $value ? 1 : 0, SQLITE3_INTEGER);
            } else {
                $this->stmt->bindValue($paramIndex, (string) $value, SQLITE3_TEXT);
            }
        }

        $result = $this->stmt->execute();

        $type = $queryObj->getLastQueryType();

        if ($type === 'select' || $type === 'show') {
            $this->setResultSet($this->fetchResult($result));
        }

        $result->finalize();
        $this->stmt->close();
        $this->setErrCode(0);
        $this->setErrMessage('NO ERROR');

        return true;
    }

    /**
     * Fetches all rows from a SQLite3Result into a ResultSet.
     * 
     * @param SQLite3Result $result The result object from a query execution.
     * 
     * @return ResultSet A result set containing all fetched rows as associative arrays.
     */
    private function fetchResult(SQLite3Result $result): ResultSet {
        $rows = [];

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $rows[] = $row;
        }

        return new ResultSet($rows);
    }
}
