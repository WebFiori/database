<?php

namespace WebFiori\Database\Performance;

/**
 * Represents a single query performance metric.
 * 
 * This class encapsulates all performance data collected for a single
 * database query execution, including timing, resource usage, and
 * query characteristics.
 * 
 * @author Ibrahim
 */
class QueryMetric {
    private string $queryHash;
    private string $queryType;
    private string $query;
    private float $executionTimeMs;
    private int $rowsAffected;
    private float $memoryUsageMb;
    private float $executedAt;
    private string $databaseName;
    
    /**
     * Create a new query metric instance.
     * 
     * @param string $queryHash MD5 hash of the normalized query
     * @param string $queryType Type of query (SELECT, INSERT, UPDATE, DELETE)
     * @param string $query The actual SQL query executed
     * @param float $executionTimeMs Execution time in milliseconds
     * @param int $rowsAffected Number of rows affected/returned
     * @param float $memoryUsageMb Memory usage in megabytes
     * @param float $executedAt Unix timestamp with microseconds
     * @param string $databaseName Name of the database
     */
    public function __construct(
        string $queryHash,
        string $queryType,
        string $query,
        float $executionTimeMs,
        int $rowsAffected,
        float $memoryUsageMb,
        float $executedAt,
        string $databaseName
    ) {
        $this->queryHash = $queryHash;
        $this->queryType = $queryType;
        $this->query = $query;
        $this->executionTimeMs = $executionTimeMs;
        $this->rowsAffected = $rowsAffected;
        $this->memoryUsageMb = $memoryUsageMb;
        $this->executedAt = $executedAt;
        $this->databaseName = $databaseName;
    }
    
    /**
     * Get the MD5 hash of the normalized query.
     * 
     * @return string MD5 hash of the normalized query
     */
    public function getQueryHash(): string {
        return $this->queryHash;
    }
    
    /**
     * Get the query type.
     * 
     * @return string Query type (SELECT, INSERT, UPDATE, DELETE)
     */
    public function getQueryType(): string {
        return $this->queryType;
    }
    
    /**
     * Get the actual SQL query that was executed.
     * 
     * @return string The SQL query
     */
    public function getQuery(): string {
        return $this->query;
    }
    
    /**
     * Get the execution time in milliseconds.
     * 
     * @return float Execution time with microsecond precision
     */
    public function getExecutionTimeMs(): float {
        return $this->executionTimeMs;
    }
    
    /**
     * Get the number of rows affected or returned.
     * 
     * @return int Row count
     */
    public function getRowsAffected(): int {
        return $this->rowsAffected;
    }
    
    /**
     * Get the memory usage in megabytes.
     * 
     * @return float Memory usage
     */
    public function getMemoryUsageMb(): float {
        return $this->memoryUsageMb;
    }
    
    /**
     * Get the execution timestamp.
     * 
     * @return float Unix timestamp with microseconds
     */
    public function getExecutedAt(): float {
        return $this->executedAt;
    }
    
    /**
     * Get the database name.
     * 
     * @return string Database name
     */
    public function getDatabaseName(): string {
        return $this->databaseName;
    }
}
