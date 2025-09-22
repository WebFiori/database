<?php

namespace WebFiori\Tests\Database\Performance;

use PHPUnit\Framework\TestCase;
use WebFiori\Database\Performance\PerformanceAnalyzer;
use WebFiori\Database\Performance\QueryPerformanceMonitor;
use WebFiori\Database\Performance\QueryMetric;

class PerformanceAnalyzerTest extends TestCase {
    
    private QueryPerformanceMonitor $monitor;
    private array $testMetrics;
    
    protected function setUp(): void {
        $this->monitor = $this->createMock(QueryPerformanceMonitor::class);
        $this->monitor->method('getSlowQueryThreshold')->willReturn(50.0);
        
        // Create test metrics
        $this->testMetrics = [
            $this->createMetric('SELECT * FROM users', 25.5),
            $this->createMetric('SELECT * FROM posts', 75.2),
            $this->createMetric('UPDATE users SET name = ?', 15.8),
            $this->createMetric('SELECT * FROM comments', 120.3),
            $this->createMetric('INSERT INTO logs VALUES (?)', 8.1)
        ];
        
        $this->monitor->method('getMetrics')->willReturn($this->testMetrics);
        $this->monitor->method('getSlowQueries')->willReturn([
            $this->testMetrics[1], // 75.2ms
            $this->testMetrics[3]  // 120.3ms
        ]);
    }
    
    private function createMetric(string $query, float $timeMs): QueryMetric {
        $metric = $this->createMock(QueryMetric::class);
        $metric->method('getQueryHash')->willReturn(md5($query));
        $metric->method('getQuery')->willReturn($query);
        $metric->method('getExecutionTimeMs')->willReturn($timeMs);
        return $metric;
    }
    
    public function testGetTotalTime() {
        $analyzer = new PerformanceAnalyzer($this->monitor);
        
        // 25.5 + 75.2 + 15.8 + 120.3 + 8.1 = 244.9
        $this->assertEquals(244.9, $analyzer->getTotalTime());
    }
    
    public function testGetAverageTime() {
        $analyzer = new PerformanceAnalyzer($this->monitor);
        
        // 244.9 / 5 = 48.98
        $this->assertEquals(48.98, round($analyzer->getAverageTime(), 2));
    }
    
    public function testGetAverageTimeWithEmptyMetrics() {
        $emptyMonitor = $this->createMock(QueryPerformanceMonitor::class);
        $emptyMonitor->method('getMetrics')->willReturn([]);
        
        $analyzer = new PerformanceAnalyzer($emptyMonitor);
        
        $this->assertEquals(0.0, $analyzer->getAverageTime());
    }
    
    public function testGetSlowQueries() {
        $analyzer = new PerformanceAnalyzer($this->monitor);
        
        $slowQueries = $analyzer->getSlowQueries();
        
        // Should return queries with time > 50ms: 75.2ms and 120.3ms
        $this->assertCount(2, $slowQueries);
        $this->assertEquals(75.2, $slowQueries[0]->getExecutionTimeMs());
        $this->assertEquals(120.3, $slowQueries[1]->getExecutionTimeMs());
    }
    
    public function testGetEfficiency() {
        $analyzer = new PerformanceAnalyzer($this->monitor);
        
        // 3 fast queries out of 5 total = 60%
        $this->assertEquals(60.0, $analyzer->getEfficiency());
    }
    
    public function testGetEfficiencyWithEmptyMetrics() {
        $emptyMonitor = $this->createMock(QueryPerformanceMonitor::class);
        $emptyMonitor->method('getMetrics')->willReturn([]);
        
        $analyzer = new PerformanceAnalyzer($emptyMonitor);
        
        $this->assertEquals(100.0, $analyzer->getEfficiency());
    }
    
    public function testGetScore() {
        // Test EXCELLENT score (< 10ms average)
        $fastMonitor = $this->createMock(QueryPerformanceMonitor::class);
        $fastMetrics = [
            $this->createMetric('SELECT 1', 5.0),
            $this->createMetric('SELECT 2', 8.0)
        ];
        $fastMonitor->method('getMetrics')->willReturn($fastMetrics);
        $analyzer = new PerformanceAnalyzer($fastMonitor);
        $this->assertEquals(PerformanceAnalyzer::SCORE_EXCELLENT, $analyzer->getScore());
        
        // Test GOOD score (10-50ms average)
        $goodMonitor = $this->createMock(QueryPerformanceMonitor::class);
        $goodMetrics = [
            $this->createMetric('SELECT 1', 20.0),
            $this->createMetric('SELECT 2', 30.0)
        ];
        $goodMonitor->method('getMetrics')->willReturn($goodMetrics);
        $analyzer = new PerformanceAnalyzer($goodMonitor);
        $this->assertEquals(PerformanceAnalyzer::SCORE_GOOD, $analyzer->getScore());
        
        // Test NEEDS_IMPROVEMENT score (> 50ms average)
        $slowMonitor = $this->createMock(QueryPerformanceMonitor::class);
        $slowMetrics = [
            $this->createMetric('SELECT 1', 100.0),
            $this->createMetric('SELECT 2', 200.0)
        ];
        $slowMonitor->method('getMetrics')->willReturn($slowMetrics);
        $analyzer = new PerformanceAnalyzer($slowMonitor);
        $this->assertEquals(PerformanceAnalyzer::SCORE_NEEDS_IMPROVEMENT, $analyzer->getScore());
    }
    
    public function testGetQueryCount() {
        $analyzer = new PerformanceAnalyzer($this->monitor);
        
        $this->assertEquals(5, $analyzer->getQueryCount());
    }
    
    public function testGetSlowQueryCount() {
        $analyzer = new PerformanceAnalyzer($this->monitor);
        
        $this->assertEquals(2, $analyzer->getSlowQueryCount());
    }
}
