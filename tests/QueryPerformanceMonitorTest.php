<?php

namespace WebFiori\Database\Tests;

use PHPUnit\Framework\TestCase;
use WebFiori\Database\Performance\QueryPerformanceMonitor;
use WebFiori\Database\Performance\PerformanceOption;
use WebFiori\Database\Performance\QueryMetric;
use WebFiori\Database\Database;
use WebFiori\Database\ConnectionInfo;
use InvalidArgumentException;

/**
 * Test cases for QueryPerformanceMonitor class.
 */
class QueryPerformanceMonitorTest extends TestCase {
    
    private QueryPerformanceMonitor $monitor;
    
    protected function setUp(): void {
        $this->monitor = new QueryPerformanceMonitor([
            PerformanceOption::ENABLED => true,
            PerformanceOption::STORAGE_TYPE => PerformanceOption::STORAGE_MEMORY
        ]);
    }
    
    public function testDefaultConfiguration() {
        $monitor = new QueryPerformanceMonitor();
        $metrics = $monitor->getMetrics();
        
        $this->assertIsArray($metrics);
        $this->assertEmpty($metrics);
    }
    
    public function testRecordQueryWhenDisabled() {
        $monitor = new QueryPerformanceMonitor([
            PerformanceOption::ENABLED => false
        ]);
        
        $monitor->recordQuery('SELECT * FROM users', 100.5);
        $metrics = $monitor->getMetrics();
        
        $this->assertEmpty($metrics);
    }
    
    public function testRecordQueryInMemory() {
        $this->monitor->recordQuery('SELECT * FROM users', 150.5);
        $metrics = $this->monitor->getMetrics();
        
        $this->assertCount(1, $metrics);
        $this->assertInstanceOf(QueryMetric::class, $metrics[0]);
        $this->assertEquals(150.5, $metrics[0]->getExecutionTimeMs());
        $this->assertEquals('SELECT', $metrics[0]->getQueryType());
    }
    
    public function testRecordMultipleQueries() {
        $this->monitor->recordQuery('SELECT * FROM users', 100);
        $this->monitor->recordQuery('INSERT INTO users VALUES (1)', 50);
        $this->monitor->recordQuery('UPDATE users SET name = "test"', 200);
        
        $metrics = $this->monitor->getMetrics();
        
        $this->assertCount(3, $metrics);
        $this->assertEquals('SELECT', $metrics[0]->getQueryType());
        $this->assertEquals('INSERT', $metrics[1]->getQueryType());
        $this->assertEquals('UPDATE', $metrics[2]->getQueryType());
    }
    
    public function testMaxSamplesLimit() {
        $monitor = new QueryPerformanceMonitor([
            PerformanceOption::ENABLED => true,
            PerformanceOption::MAX_SAMPLES => 2,
            PerformanceOption::STORAGE_TYPE => PerformanceOption::STORAGE_MEMORY
        ]);
        
        $monitor->recordQuery('SELECT 1', 10);
        $monitor->recordQuery('SELECT 2', 20);
        $monitor->recordQuery('SELECT 3', 30);
        
        $metrics = $monitor->getMetrics();
        
        $this->assertCount(2, $metrics);
        // Should keep the last 2 queries
        $this->assertEquals(20, $metrics[0]->getExecutionTimeMs());
        $this->assertEquals(30, $metrics[1]->getExecutionTimeMs());
    }
    
    public function testGetSlowQueries() {
        $this->monitor->recordQuery('SELECT * FROM users', 500);  // Not slow
        $this->monitor->recordQuery('SELECT * FROM posts', 1500); // Slow
        $this->monitor->recordQuery('SELECT * FROM comments', 2000); // Slow
        
        $slowQueries = $this->monitor->getSlowQueries(1000);
        
        $this->assertCount(2, $slowQueries);
        $this->assertEquals(1500, $slowQueries[0]->getExecutionTimeMs());
        $this->assertEquals(2000, $slowQueries[1]->getExecutionTimeMs());
    }
    
    public function testGetSlowQueriesWithDefaultThreshold() {
        $monitor = new QueryPerformanceMonitor([
            PerformanceOption::ENABLED => true,
            PerformanceOption::SLOW_QUERY_THRESHOLD => 800,
            PerformanceOption::STORAGE_TYPE => PerformanceOption::STORAGE_MEMORY
        ]);
        
        $monitor->recordQuery('SELECT * FROM users', 500);  // Not slow
        $monitor->recordQuery('SELECT * FROM posts', 900);  // Slow
        
        $slowQueries = $monitor->getSlowQueries();
        
        $this->assertCount(1, $slowQueries);
        $this->assertEquals(900, $slowQueries[0]->getExecutionTimeMs());
    }
    
    public function testGetStatistics() {
        $this->monitor->recordQuery('SELECT 1', 100);
        $this->monitor->recordQuery('SELECT 2', 200);
        $this->monitor->recordQuery('SELECT 3', 300);
        
        $stats = $this->monitor->getStatistics();
        
        $this->assertEquals(3, $stats['total_queries']);
        $this->assertEquals(200, $stats['avg_execution_time']);
        $this->assertEquals(100, $stats['min_execution_time']);
        $this->assertEquals(300, $stats['max_execution_time']);
    }
    
    public function testGetStatisticsEmpty() {
        $stats = $this->monitor->getStatistics();
        
        $this->assertEquals(0, $stats['total_queries']);
        $this->assertEquals(0, $stats['avg_execution_time']);
        $this->assertEquals(0, $stats['min_execution_time']);
        $this->assertEquals(0, $stats['max_execution_time']);
        $this->assertEquals(0, $stats['slow_queries_count']);
    }
    
    public function testClearMetrics() {
        $this->monitor->recordQuery('SELECT * FROM users', 100);
        $this->assertCount(1, $this->monitor->getMetrics());
        
        $this->monitor->clearMetrics();
        $this->assertEmpty($this->monitor->getMetrics());
    }
    
    public function testUpdateConfig() {
        $this->monitor->updateConfig([
            PerformanceOption::SLOW_QUERY_THRESHOLD => 500
        ]);
        
        $this->monitor->recordQuery('SELECT * FROM users', 600);
        $slowQueries = $this->monitor->getSlowQueries();
        
        $this->assertCount(1, $slowQueries);
    }
    
    public function testQueryTypeFiltering() {
        $monitor = new QueryPerformanceMonitor([
            PerformanceOption::ENABLED => true,
            PerformanceOption::TRACK_SELECT => true,
            PerformanceOption::TRACK_INSERT => false,
            PerformanceOption::STORAGE_TYPE => PerformanceOption::STORAGE_MEMORY
        ]);
        
        $monitor->recordQuery('SELECT * FROM users', 100);
        $monitor->recordQuery('INSERT INTO users VALUES (1)', 50);
        
        $metrics = $monitor->getMetrics();
        
        $this->assertCount(1, $metrics);
        $this->assertEquals('SELECT', $metrics[0]->getQueryType());
    }
    
    public function testInvalidConfigurationThrowsException() {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('ENABLED must be boolean');
        
        new QueryPerformanceMonitor([
            PerformanceOption::ENABLED => 'invalid'
        ]);
    }
    
    public function testInvalidSamplingRateThrowsException() {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('SAMPLING_RATE must be between 0.0 and 1.0');
        
        new QueryPerformanceMonitor([
            PerformanceOption::SAMPLING_RATE => 1.5
        ]);
    }
    
    public function testInvalidStorageTypeThrowsException() {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid STORAGE_TYPE');
        
        new QueryPerformanceMonitor([
            PerformanceOption::STORAGE_TYPE => 'invalid'
        ]);
    }
    
    public function testSamplingRate() {
        $monitor = new QueryPerformanceMonitor([
            PerformanceOption::ENABLED => true,
            PerformanceOption::SAMPLING_RATE => 0.0, // Never sample
            PerformanceOption::STORAGE_TYPE => PerformanceOption::STORAGE_MEMORY
        ]);
        
        // Record multiple queries - none should be stored due to 0% sampling
        for ($i = 0; $i < 10; $i++) {
            $monitor->recordQuery('SELECT ' . $i, 100);
        }
        
        $metrics = $monitor->getMetrics();
        $this->assertEmpty($metrics);
    }
}
