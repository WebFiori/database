<?php
namespace WebFiori\Database\Tests;

use PHPUnit\Framework\TestCase;
use WebFiori\Database\Performance\PerformanceOption;
use WebFiori\Database\Performance\QueryPerformanceMonitor;
use WebFiori\Database\Performance\PerformanceAnalyzer;

class DatabasePerformancePersistenceTest extends TestCase {
    public function testGetAnalyzer() {
        $monitor = new QueryPerformanceMonitor([
            PerformanceOption::ENABLED => true,
        ]);
        $monitor->recordQuery('SELECT 1', 10.0);

        $analyzer = $monitor->getAnalyzer();
        $this->assertInstanceOf(PerformanceAnalyzer::class, $analyzer);
    }
}
