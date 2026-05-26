<?php

/**
 * This file is licensed under MIT License.
 * 
 * Copyright (c) 2025-present WebFiori Framework
 * 
 * For more information on the license, please visit: 
 * https://github.com/WebFiori/.github/blob/main/LICENSE
 * 
 */
namespace WebFiori\Database;

use WebFiori\Database\MySql\MySQLConnection;
use WebFiori\Database\MsSql\MSSQLConnection;

/**
 * A connection pool that manages database connection lifecycle.
 * 
 * The pool reuses idle connections instead of creating new ones,
 * preventing "Too many connections" errors and reducing overhead
 * from repeated connection handshakes.
 * 
 * Usage:
 * ```php
 * $pool = ConnectionPool::getInstance();
 * $conn = $pool->acquire($connectionInfo);
 * // ... use connection ...
 * $pool->release($conn);
 * ```
 * 
 * @author Ibrahim
 */
class ConnectionPool {
    private static ?ConnectionPool $instance = null;

    /** @var array<string, Connection[]> */
    private array $idle = [];

    /** @var array<string, Connection[]> */
    private array $active = [];

    private int $maxPerKey = 10;
    private int $maxTotal = 100;

    private function __construct() {
    }

    /**
     * Returns the singleton pool instance.
     * 
     * @return ConnectionPool
     */
    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Acquire a connection for the given connection info.
     * 
     * If an idle connection exists for the same host/port/db/user combination,
     * it will be reused. Otherwise, a new connection is created.
     * 
     * @param ConnectionInfo $info Connection parameters.
     * 
     * @return Connection A ready-to-use database connection.
     * 
     * @throws DatabaseException If the pool is exhausted or connection fails.
     */
    public function acquire(ConnectionInfo $info): Connection {
        $key = $this->buildKey($info);

        // Try to reuse an idle connection
        while (!empty($this->idle[$key])) {
            $conn = array_pop($this->idle[$key]);

            if ($conn->isAlive()) {
                $this->active[$key][] = $conn;
                return $conn;
            }

            // Dead connection, discard
            $conn->close();
        }

        // Check total limit
        if ($this->getActiveCount() >= $this->maxTotal) {
            throw new DatabaseException(
                "Connection pool exhausted (max: {$this->maxTotal})"
            );
        }

        // Create new connection
        $conn = $this->createConnection($info);
        $this->active[$key][] = $conn;
        return $conn;
    }

    /**
     * Release a connection back to the pool for reuse.
     * 
     * @param Connection $conn The connection to release.
     */
    public function release(Connection $conn): void {
        $key = $this->buildKey($conn->getConnectionInfo());

        // Remove from active
        if (isset($this->active[$key])) {
            $index = array_search($conn, $this->active[$key], true);

            if ($index !== false) {
                unset($this->active[$key][$index]);
                $this->active[$key] = array_values($this->active[$key]);
            }
        }

        // Return to idle if under per-key limit
        $idleCount = count($this->idle[$key] ?? []);

        if ($idleCount < $this->maxPerKey && $conn->isAlive()) {
            $this->idle[$key][] = $conn;
        } else {
            $conn->close();
        }
    }

    /**
     * Close all connections (idle and active) and drain the pool.
     */
    public function closeAll(): void {
        foreach ($this->idle as $connections) {
            foreach ($connections as $conn) {
                $conn->close();
            }
        }

        foreach ($this->active as $connections) {
            foreach ($connections as $conn) {
                $conn->close();
            }
        }

        $this->idle = [];
        $this->active = [];
    }

    /**
     * Set the maximum number of connections per unique key (host+port+db+user).
     * 
     * @param int $max Maximum idle connections per key.
     */
    public function setMaxPerKey(int $max): void {
        if ($max > 0) {
            $this->maxPerKey = $max;
        }
    }

    /**
     * Set the maximum total number of active connections across all keys.
     * 
     * @param int $max Maximum total active connections.
     */
    public function setMaxTotal(int $max): void {
        if ($max > 0) {
            $this->maxTotal = $max;
        }
    }

    /**
     * Returns the maximum number of idle connections per key.
     * 
     * @return int
     */
    public function getMaxPerKey(): int {
        return $this->maxPerKey;
    }

    /**
     * Returns the maximum total active connections allowed.
     * 
     * @return int
     */
    public function getMaxTotal(): int {
        return $this->maxTotal;
    }

    /**
     * Returns the number of currently active (in-use) connections.
     * 
     * @return int
     */
    public function getActiveCount(): int {
        $count = 0;

        foreach ($this->active as $connections) {
            $count += count($connections);
        }

        return $count;
    }

    /**
     * Returns the number of currently idle (available) connections.
     * 
     * @return int
     */
    public function getIdleCount(): int {
        $count = 0;

        foreach ($this->idle as $connections) {
            $count += count($connections);
        }

        return $count;
    }

    /**
     * Reset the pool singleton. Closes all connections and destroys the instance.
     * Primarily useful for testing.
     */
    public static function reset(): void {
        if (self::$instance !== null) {
            self::$instance->closeAll();
        }
        self::$instance = null;
    }

    private function buildKey(ConnectionInfo $info): string {
        return $info->getHost() . ':' . $info->getPort() . '/'
             . $info->getDBName() . '@' . $info->getUsername();
    }

    private function createConnection(ConnectionInfo $info): Connection {
        $driver = $info->getDatabaseType();

        return match ($driver) {
            'mysql' => new MySQLConnection($info),
            'mssql' => new MSSQLConnection($info),
            'sqlite' => new \WebFiori\Database\Sqlite\SQLiteConnection($info),
            default => throw new DatabaseException("Unsupported driver: $driver"),
        };
    }
}
