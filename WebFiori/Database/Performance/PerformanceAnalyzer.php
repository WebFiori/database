<?php
namespace WebFiori\Database\Performance;

/**
 * Analyzes query performance metrics and provides statistical insights.
 * 
 * This class processes collected performance metrics to calculate various
 * statistics and provide performance insights such as total execution time,
 * average query time, slow query identification, and performance scoring.
 * 
 * @author Ibrahim
 */
class PerformanceAnalyzer {
    public const SCORE_EXCELLENT = 'EXCELLENT';
    public const SCORE_GOOD = 'GOOD';
    public const SCORE_NEEDS_IMPROVEMENT = 'NEEDS_IMPROVEMENT';

    private QueryPerformanceMonitor $monitor;

    /**
     * Create a new performance analyzer instance.
     * 
     * @param QueryPerformanceMonitor $monitor The monitor instance containing metrics and configuration.
     */
    public function __construct(QueryPerformanceMonitor $monitor) {
        $this->monitor = $monitor;
    }

    /**
     * Calculate the average execution time per query.
     * 
     * @return float Average execution time in milliseconds, 0 if no metrics.
     */
    public function getAverageTime(): float {
        $metrics = $this->monitor->getMetrics();

        if (empty($metrics)) {
            return 0.0;
        }

        return $this->getTotalTime() / count($metrics);
    }

    /**
     * Calculate query performance efficiency as percentage of fast queries.
     * 
     * @return float Efficiency percentage (0-100).
     */
    public function getEfficiency(): float {
        $metrics = $this->monitor->getMetrics();

        if (empty($metrics)) {
            return 100.0;
        }

        $slowCount = count($this->getSlowQueries());
        $fastCount = count($metrics) - $slowCount;

        return ($fastCount / count($metrics)) * 100;
    }

    /**
     * Get the total number of queries analyzed.
     * 
     * @return int Total query count.
     */
    public function getQueryCount(): int {
        return count($this->monitor->getMetrics());
    }

    /**
     * Get a performance score based on average execution time.
     * 
     * @return string Performance score: SCORE_EXCELLENT, SCORE_GOOD, or SCORE_NEEDS_IMPROVEMENT.
     */
    public function getScore(): string {
        $avgTime = $this->getAverageTime();

        if ($avgTime < 10) {
            return self::SCORE_EXCELLENT;
        } elseif ($avgTime < 50) {
            return self::SCORE_GOOD;
        } else {
            return self::SCORE_NEEDS_IMPROVEMENT;
        }
    }

    /**
     * Get all queries that exceed the slow query threshold.
     * 
     * @return array Array of QueryMetric instances for slow queries.
     */
    public function getSlowQueries(): array {
        return $this->monitor->getSlowQueries();
    }

    /**
     * Get the number of slow queries.
     * 
     * @return int Slow query count.
     */
    public function getSlowQueryCount(): int {
        return count($this->getSlowQueries());
    }

    /**
     * Calculate the total execution time of all queries.
     * 
     * @return float Total execution time in milliseconds.
     */
    public function getTotalTime(): float {
        $total = 0.0;

        foreach ($this->monitor->getMetrics() as $metric) {
            $total += $metric->getExecutionTimeMs();
        }

        return $total;
    }
}
