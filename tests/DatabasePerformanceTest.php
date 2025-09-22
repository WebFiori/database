<?php

namespace WebFiori\Database\Tests;

use PHPUnit\Framework\TestCase;
use WebFiori\Database\Database;
use WebFiori\Database\ConnectionInfo;
use WebFiori\Database\Performance\PerformanceOption;
use WebFiori\Database\Performance\QueryMetric;

/**
 * Test cases for Database class performance monitoring integration.
 */
class DatabasePerformanceTest extends TestCase {
    
    private Database $database;
    
    protected function setUp(): void {
        $connectionInfo = new ConnectionInfo('mysql', 'root', '123456', 'testing_db', 'localhost');
        $this->database = new Database($connectionInfo);
    }
    
    public function testEnablePerformanceMonitoring() {
        $this->database->enablePerformanceMonitoring();
        
        // Performance monitoring should be enabled
        $metrics = $this->database->getPerformanceMetrics();
        $this->assertIsArray($metrics);
    }
    
    public function testDisablePerformanceMonitoring() {
        $this->database->enablePerformanceMonitoring();
        $this->database->disablePerformanceMonitoring();
        
        // Should still be able to get metrics (existing data preserved)
        $metrics = $this->database->getPerformanceMetrics();
        $this->assertIsArray($metrics);
    }
    
    public function testGetPerformanceMetricsWhenNotInitialized() {
        $metrics = $this->database->getPerformanceMetrics();
        
        $this->assertIsArray($metrics);
        $this->assertEmpty($metrics);
    }
    
    public function testGetSlowQueriesWhenNotInitialized() {
        $slowQueries = $this->database->getSlowQueries();
        
        $this->assertIsArray($slowQueries);
        $this->assertEmpty($slowQueries);
    }
    
    public function testGetPerformanceStatisticsWhenNotInitialized() {
        $stats = $this->database->getPerformanceStatistics();
        
        $expected = [
            'total_queries' => 0,
            'avg_execution_time' => 0,
            'min_execution_time' => 0,
            'max_execution_time' => 0,
            'slow_queries_count' => 0
        ];
        
        $this->assertEquals($expected, $stats);
    }
    
    public function testSetPerformanceConfig() {
        $config = [
            PerformanceOption::ENABLED => true,
            PerformanceOption::SLOW_QUERY_THRESHOLD => 500,
            PerformanceOption::STORAGE_TYPE => PerformanceOption::STORAGE_MEMORY
        ];
        
        $this->database->setPerformanceConfig($config);
        
        // Should be able to get metrics after configuration
        $metrics = $this->database->getPerformanceMetrics();
        $this->assertIsArray($metrics);
    }
    
    public function testSetPerformanceConfigUpdatesExisting() {
        // First configuration
        $this->database->setPerformanceConfig([
            PerformanceOption::ENABLED => true,
            PerformanceOption::SLOW_QUERY_THRESHOLD => 1000
        ]);
        
        // Update configuration
        $this->database->setPerformanceConfig([
            PerformanceOption::SLOW_QUERY_THRESHOLD => 500
        ]);
        
        // Should work without errors
        $metrics = $this->database->getPerformanceMetrics();
        $this->assertIsArray($metrics);
    }
    
    public function testClearPerformanceMetrics() {
        $this->database->enablePerformanceMonitoring();
        $this->database->clearPerformanceMetrics();
        
        // Should work without errors
        $metrics = $this->database->getPerformanceMetrics();
        $this->assertIsArray($metrics);
    }
    
    public function testClearPerformanceMetricsWhenNotInitialized() {
        // Should not throw error when monitor is not initialized
        $this->database->clearPerformanceMetrics();
        
        $this->assertTrue(true); // Test passes if no exception thrown
    }
    
    public function testPerformanceConfigWithMemoryStorage() {
        $config = [
            PerformanceOption::ENABLED => true,
            PerformanceOption::STORAGE_TYPE => PerformanceOption::STORAGE_MEMORY,
            PerformanceOption::MAX_SAMPLES => 100
        ];
        
        $this->database->setPerformanceConfig($config);
        
        // Should initialize without errors
        $metrics = $this->database->getPerformanceMetrics();
        $this->assertIsArray($metrics);
    }
    
    public function testPerformanceConfigWithDatabaseStorage() {
        $config = [
            PerformanceOption::ENABLED => true,
            PerformanceOption::STORAGE_TYPE => PerformanceOption::STORAGE_DATABASE,
            PerformanceOption::RETENTION_HOURS => 48
        ];
        
        $this->database->setPerformanceConfig($config);
        
        // Should initialize without errors
        $metrics = $this->database->getPerformanceMetrics();
        $this->assertIsArray($metrics);
    }
    
    public function testGetSlowQueriesWithCustomThreshold() {
        $this->database->enablePerformanceMonitoring();
        
        $slowQueries = $this->database->getSlowQueries(2000);
        
        $this->assertIsArray($slowQueries);
    }
    
    public function testGetSlowQueriesWithDefaultThreshold() {
        $this->database->enablePerformanceMonitoring();
        
        $slowQueries = $this->database->getSlowQueries();
        
        $this->assertIsArray($slowQueries);
    }
}
