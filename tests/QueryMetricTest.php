<?php

namespace WebFiori\Database\Tests;

use PHPUnit\Framework\TestCase;
use WebFiori\Database\Performance\QueryMetric;

/**
 * Test cases for QueryMetric class.
 */
class QueryMetricTest extends TestCase {
    
    private QueryMetric $metric;
    
    protected function setUp(): void {
        $this->metric = new QueryMetric(
            'abc123',
            'SELECT',
            150.5,
            10,
            2.5,
            1640995200.123456,
            'test_db'
        );
    }
    
    public function testGetQueryHash() {
        $this->assertEquals('abc123', $this->metric->getQueryHash());
    }
    
    public function testGetQueryType() {
        $this->assertEquals('SELECT', $this->metric->getQueryType());
    }
    
    public function testGetExecutionTimeMs() {
        $this->assertEquals(150.5, $this->metric->getExecutionTimeMs());
    }
    
    public function testGetRowsAffected() {
        $this->assertEquals(10, $this->metric->getRowsAffected());
    }
    
    public function testGetMemoryUsageMb() {
        $this->assertEquals(2.5, $this->metric->getMemoryUsageMb());
    }
    
    public function testGetExecutedAt() {
        $this->assertEquals(1640995200.123456, $this->metric->getExecutedAt());
    }
    
    public function testGetDatabaseName() {
        $this->assertEquals('test_db', $this->metric->getDatabaseName());
    }
    
    public function testToArray() {
        $expected = [
            'query_hash' => 'abc123',
            'query_type' => 'SELECT',
            'execution_time_ms' => 150.5,
            'rows_affected' => 10,
            'memory_usage_mb' => 2.5,
            'executed_at' => 1640995200.123456,
            'database_name' => 'test_db'
        ];
        
        $this->assertEquals($expected, $this->metric->toArray());
    }
    
    public function testCreateMetricWithDifferentTypes() {
        $insertMetric = new QueryMetric(
            'def456',
            'INSERT',
            75.2,
            1,
            1.8,
            1640995300.654321,
            'prod_db'
        );
        
        $this->assertEquals('INSERT', $insertMetric->getQueryType());
        $this->assertEquals(75.2, $insertMetric->getExecutionTimeMs());
        $this->assertEquals(1, $insertMetric->getRowsAffected());
        $this->assertEquals('prod_db', $insertMetric->getDatabaseName());
    }
}
