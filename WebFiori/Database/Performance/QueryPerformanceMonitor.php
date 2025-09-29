<?php
namespace WebFiori\Database\Performance;

use InvalidArgumentException;
use WebFiori\Database\ColOption;
use WebFiori\Database\Database;
use WebFiori\Database\DataType;
use WebFiori\Database\ResultSet;

/**
 * Query performance monitoring system for database operations.
 * 
 * This class provides comprehensive performance monitoring capabilities
 * for database queries, including execution timing, resource usage tracking,
 * and configurable storage options (memory or database).
 * 
 * Features:
 * - Real-time query performance tracking
 * - Configurable slow query detection
 * - Memory and database storage options
 * - Query type filtering and sampling
 * - Automatic cleanup and retention management
 * 
 * @author Ibrahim
 */
class QueryPerformanceMonitor {
    private array $config;
    private ?Database $database = null;
    private array $memoryMetrics = [];
    private bool $schemaCreated = false;

    /**
     * Initialize performance monitor with configuration.
     * 
     * @param array $config Performance monitoring configuration
     * @param Database|null $database Database instance for persistent storage
     */
    public function __construct(array $config = [], ?Database $database = null) {
        $this->config = array_merge($this->getDefaultConfig(), $config);
        $this->database = $database;
        $this->validateConfig();
    }

    /**
     * Clear all stored metrics.
     */
    public function clearMetrics(): void {
        if ($this->config[PerformanceOption::STORAGE_TYPE] === PerformanceOption::STORAGE_MEMORY) {
            $this->memoryMetrics = [];
        } else {
            $this->clearDatabaseMetrics();
        }
    }

    /**
     * Create a performance analyzer for the collected metrics.
     * 
     * @return PerformanceAnalyzer Analyzer instance with current metrics and configuration.
     */
    public function getAnalyzer(): PerformanceAnalyzer {
        return new PerformanceAnalyzer($this);
    }

    /**
     * Get all performance metrics.
     * 
     * @return array Array of QueryMetric instances or metric arrays
     */
    public function getMetrics(): array {
        if ($this->config[PerformanceOption::STORAGE_TYPE] === PerformanceOption::STORAGE_MEMORY) {
            return $this->memoryMetrics;
        }

        if (!$this->database) {
            return [];
        }

        try {
            return $this->getMetricsFromDatabase();
        } catch (\Exception $e) {
            // If table doesn't exist, return empty array
            return [];
        }
    }

    /**
     * Get slow queries based on configured threshold.
     * 
     * @param int|null $thresholdMs Custom threshold in milliseconds
     * @return array Array of slow query metrics
     */
    public function getSlowQueries(?int $thresholdMs = null): array {
        $threshold = $thresholdMs ?? $this->config[PerformanceOption::SLOW_QUERY_THRESHOLD];
        $metrics = $this->getMetrics();

        return array_values(array_filter($metrics, function($metric) use ($threshold)
        {
            $executionTime = $metric instanceof QueryMetric 
                ? $metric->getExecutionTimeMs() 
                : $metric['execution_time_ms'];

            return $executionTime >= $threshold;
        }));
    }

    /**
     * Get the configured slow query threshold.
     * 
     * @return float Slow query threshold in milliseconds.
     */
    public function getSlowQueryThreshold(): float {
        return (float) $this->config[PerformanceOption::SLOW_QUERY_THRESHOLD];
    }    
    /**
     * Get performance statistics summary.
     * 
     * @return array Statistics including avg, min, max execution times
     */
    public function getStatistics(): array {
        $metrics = $this->getMetrics();

        if (empty($metrics)) {
            return [
                'total_queries' => 0,
                'avg_execution_time' => 0,
                'min_execution_time' => 0,
                'max_execution_time' => 0,
                'slow_queries_count' => 0
            ];
        }

        $executionTimes = array_map(function($metric)
        {
            return $metric instanceof QueryMetric 
                ? $metric->getExecutionTimeMs() 
                : $metric['execution_time_ms'];
        }, $metrics);

        return [
            'total_queries' => count($metrics),
            'avg_execution_time' => array_sum($executionTimes) / count($executionTimes),
            'min_execution_time' => min($executionTimes),
            'max_execution_time' => max($executionTimes),
            'slow_queries_count' => count($this->getSlowQueries())
        ];
    }

    /**
     * Record a query performance metric.
     * 
     * @param string $query The SQL query that was executed
     * @param float $executionTimeMs Execution time in milliseconds
     * @param mixed $result Query result (for row count extraction)
     */
    public function recordQuery(string $query, float $executionTimeMs, $result = null): void {
        if (!$this->config[PerformanceOption::ENABLED]) {
            return;
        }

        if (!$this->shouldTrackQuery($query)) {
            return;
        }

        if (!$this->shouldSample()) {
            return;
        }

        $metric = $this->createMetric($query, $executionTimeMs, $result);

        if ($this->config[PerformanceOption::STORAGE_TYPE] === PerformanceOption::STORAGE_MEMORY) {
            $this->storeInMemory($metric);
        } else {
            $this->storeInDatabase($metric);
        }

        $this->performCleanup();
    }

    /**
     * Update monitoring configuration.
     * 
     * @param array $config New configuration options
     */
    public function updateConfig(array $config): void {
        $this->config = array_merge($this->config, $config);
        $this->validateConfig();
    }

    /**
     * Clean up old metrics from database.
     */
    private function cleanupOldDatabaseMetrics(): void {
        if (!$this->database) {
            return;
        }

        $cutoffTime = microtime(true) - ($this->config[PerformanceOption::RETENTION_HOURS] * 3600);

        $this->database->table('query_performance_metrics')
            ->delete()
            ->where('executed_at', $cutoffTime, '<')
            ->execute();
    }

    /**
     * Clear metrics from database.
     */
    private function clearDatabaseMetrics(): void {
        if (!$this->database) {
            return;
        }

        $this->database->table('query_performance_metrics')
            ->delete()
            ->execute();
    }

    /**
     * Create a QueryMetric instance from query data.
     * 
     * @param string $query SQL query
     * @param float $executionTimeMs Execution time
     * @param mixed $result Query result
     * @return QueryMetric
     */
    private function createMetric(string $query, float $executionTimeMs, $result): QueryMetric {
        return new QueryMetric(
            md5($query),
            $this->getQueryType($query),
            $query,
            $executionTimeMs,
            $this->getRowCount($result),
            $this->getMemoryUsage(),
            microtime(true),
            $this->database ? $this->database->getName() : 'unknown'
        );
    }

    /**
     * Ensure performance metrics table exists.
     */
    private function ensureSchemaExists(): void {
        if ($this->schemaCreated || !$this->database) {
            return;
        }

        $this->database->createBlueprint('query_performance_metrics')
            ->addColumns([
                'id' => [
                    ColOption::TYPE => DataType::INT,
                    ColOption::PRIMARY => true,
                    ColOption::AUTO_INCREMENT => true
                ],
                'query_hash' => [
                    ColOption::TYPE => DataType::VARCHAR,
                    ColOption::SIZE => 64
                ],
                'query_type' => [
                    ColOption::TYPE => DataType::VARCHAR,
                    ColOption::SIZE => 20
                ],
                'execution_time_ms' => [
                    ColOption::TYPE => DataType::DECIMAL,
                    ColOption::SIZE => 10,
                    ColOption::SCALE => 2
                ],
                'rows_affected' => [
                    ColOption::TYPE => DataType::INT
                ],
                'memory_usage_mb' => [
                    ColOption::TYPE => DataType::DECIMAL,
                    ColOption::SIZE => '8,2'
                ],
                'executed_at' => [
                    ColOption::TYPE => DataType::DECIMAL,
                    ColOption::SIZE => '15,6'
                ],
                'database_name' => [
                    ColOption::TYPE => DataType::VARCHAR,
                    ColOption::SIZE => 64
                ]
            ]);

        $this->database->createTable()->execute();
        $this->schemaCreated = true;
    }

    /**
     * Get default configuration values.
     * 
     * @return array Default configuration
     */
    private function getDefaultConfig(): array {
        return [
            PerformanceOption::ENABLED => false,
            PerformanceOption::SLOW_QUERY_THRESHOLD => 1000,
            PerformanceOption::WARNING_THRESHOLD => 500,
            PerformanceOption::SAMPLING_RATE => 1.0,
            PerformanceOption::MAX_SAMPLES => 10000,
            PerformanceOption::STORAGE_TYPE => PerformanceOption::STORAGE_MEMORY,
            PerformanceOption::RETENTION_HOURS => 24,
            PerformanceOption::AUTO_CLEANUP => true,
            PerformanceOption::MEMORY_LIMIT_MB => 50,
            PerformanceOption::TRACK_SELECT => true,
            PerformanceOption::TRACK_INSERT => true,
            PerformanceOption::TRACK_UPDATE => true,
            PerformanceOption::TRACK_DELETE => true
        ];
    }

    /**
     * Get current memory usage in MB.
     * 
     * @return float Memory usage in megabytes
     */
    private function getMemoryUsage(): float {
        return memory_get_usage(true) / 1024 / 1024;
    }

    /**
     * Get metrics from database.
     * 
     * @return array
     */
    private function getMetricsFromDatabase(): array {
        if (!$this->database) {
            return [];
        }

        $result = $this->database->table('query_performance_metrics')
            ->select()
            ->execute();

        return $result->getRows();
    }

    /**
     * Extract query type from SQL.
     * 
     * @param string $query SQL query
     * @return string Query type (SELECT, INSERT, UPDATE, DELETE)
     */
    private function getQueryType(string $query): string {
        $query = trim(strtoupper($query));

        if (str_starts_with($query, 'SELECT')) {
            return 'SELECT';
        }

        if (str_starts_with($query, 'INSERT')) {
            return 'INSERT';
        }

        if (str_starts_with($query, 'UPDATE')) {
            return 'UPDATE';
        }

        if (str_starts_with($query, 'DELETE')) {
            return 'DELETE';
        }

        return 'OTHER';
    }

    /**
     * Extract row count from query result.
     * 
     * @param mixed $result Query result
     * @return int Row count
     */
    private function getRowCount($result): int {
        if ($result instanceof ResultSet) {
            return $result->count();
        }

        return 0;
    }

    /**
     * Perform cleanup based on configuration.
     */
    private function performCleanup(): void {
        if (!$this->config[PerformanceOption::AUTO_CLEANUP]) {
            return;
        }

        if ($this->config[PerformanceOption::STORAGE_TYPE] === PerformanceOption::STORAGE_DATABASE) {
            $this->cleanupOldDatabaseMetrics();
        }
    }

    /**
     * Check if current query should be sampled.
     * 
     * @return bool True if query should be sampled
     */
    private function shouldSample(): bool {
        return mt_rand() / mt_getrandmax() <= $this->config[PerformanceOption::SAMPLING_RATE];
    }

    /**
     * Check if query should be tracked based on type.
     * 
     * @param string $query SQL query
     * @return bool True if query should be tracked
     */
    private function shouldTrackQuery(string $query): bool {
        $queryType = $this->getQueryType($query);

        return match ($queryType) {
            'SELECT' => $this->config[PerformanceOption::TRACK_SELECT],
            'INSERT' => $this->config[PerformanceOption::TRACK_INSERT],
            'UPDATE' => $this->config[PerformanceOption::TRACK_UPDATE],
            'DELETE' => $this->config[PerformanceOption::TRACK_DELETE],
            default => false
        };
    }

    /**
     * Store metric in database.
     * 
     * @param QueryMetric $metric
     */
    private function storeInDatabase(QueryMetric $metric): void {
        if (!$this->database) {
            return;
        }

        $this->ensureSchemaExists();

        $this->database->table('query_performance_metrics')
            ->insert($metric->toArray())
            ->execute();
    }

    /**
     * Store metric in memory.
     * 
     * @param QueryMetric $metric
     */
    private function storeInMemory(QueryMetric $metric): void {
        $this->memoryMetrics[] = $metric;

        if (count($this->memoryMetrics) > $this->config[PerformanceOption::MAX_SAMPLES]) {
            array_shift($this->memoryMetrics);
        }
    }

    /**
     * Validate configuration values.
     * 
     * @throws InvalidArgumentException If configuration is invalid
     */
    private function validateConfig(): void {
        if (!is_bool($this->config[PerformanceOption::ENABLED])) {
            throw new InvalidArgumentException('ENABLED must be boolean');
        }

        if ($this->config[PerformanceOption::SAMPLING_RATE] < 0 || $this->config[PerformanceOption::SAMPLING_RATE] > 1) {
            throw new InvalidArgumentException('SAMPLING_RATE must be between 0.0 and 1.0');
        }

        if (!in_array($this->config[PerformanceOption::STORAGE_TYPE], [
            PerformanceOption::STORAGE_MEMORY, 
            PerformanceOption::STORAGE_DATABASE
        ])) {
            throw new InvalidArgumentException('Invalid STORAGE_TYPE');
        }
    }
}
