<?php

require_once '../../vendor/autoload.php';

use WebFiori\Database\ConnectionInfo;
use WebFiori\Database\Database;
use WebFiori\Database\Performance\PerformanceOption;
use WebFiori\Database\Performance\PerformanceAnalyzer;

echo "=== WebFiori Database Performance Monitoring Example ===\n\n";

try {
    // 1. Setup database connection
    echo "1. Setting up database connection:\n";
    $connection = new ConnectionInfo('mysql', 'root', '123456', 'testing_db', 'localhost');
    $database = new Database($connection);
    echo "✓ Database connection established\n\n";
    
    // 2. Configure and enable performance monitoring
    echo "2. Configuring performance monitoring:\n";
    $database->setPerformanceConfig([
        PerformanceOption::ENABLED => true,
        PerformanceOption::SLOW_QUERY_THRESHOLD => 50, // 50ms threshold
        PerformanceOption::WARNING_THRESHOLD => 25,    // 25ms warning
        PerformanceOption::SAMPLING_RATE => 1.0,       // Monitor all queries
        PerformanceOption::MAX_SAMPLES => 1000         // Keep up to 1000 samples
    ]);
    echo "✓ Performance monitoring configured\n";
    echo "  - Slow query threshold: 50ms\n";
    echo "  - Warning threshold: 25ms\n";
    echo "  - Sampling rate: 100%\n\n";
    
    // 3. Create test table for demonstration
    echo "3. Creating test table:\n";
    $database->setQuery("DROP TABLE IF EXISTS performance_test")->execute();
    $database->setQuery("
        CREATE TABLE performance_test (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100),
            email VARCHAR(150),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_email (email)
        )
    ")->execute();
    echo "✓ Test table created\n\n";
    
    // 4. Execute various queries with different performance characteristics
    echo "4. Executing test queries:\n";
    
    // Fast queries
    for ($i = 1; $i <= 5; $i++) {
        $database->table('performance_test')->insert([
            'name' => "User $i",
            'email' => "user$i@example.com"
        ])->execute();
    }
    echo "✓ Executed 5 fast INSERT queries\n";
    
    // Medium speed queries
    for ($i = 1; $i <= 3; $i++) {
        $database->table('performance_test')
                 ->select()
                 ->where('email', "user$i@example.com")
                 ->execute();
    }
    echo "✓ Executed 3 medium SELECT queries\n";
    
    // Simulate slow queries with SLEEP
    $database->setQuery("SELECT SLEEP(0.03)")->execute(); // 30ms
    $database->setQuery("SELECT SLEEP(0.08)")->execute(); // 80ms
    $database->setQuery("SELECT SLEEP(0.12)")->execute(); // 120ms
    echo "✓ Executed 3 slow queries with artificial delays\n\n";
    
    // 5. Analyze performance using the new PerformanceAnalyzer
    echo "5. Performance Analysis:\n";
    $analyzer = $database->getPerformanceMonitor()->getAnalyzer();
    
    echo "Query Statistics:\n";
    echo "  - Total queries: " . $analyzer->getQueryCount() . "\n";
    echo "  - Total execution time: " . number_format($analyzer->getTotalTime(), 2) . " ms\n";
    echo "  - Average execution time: " . number_format($analyzer->getAverageTime(), 2) . " ms\n";
    echo "  - Performance score: " . $analyzer->getScore() . "\n";
    echo "  - Query efficiency: " . number_format($analyzer->getEfficiency(), 1) . "%\n\n";
    
    // 6. Analyze slow queries
    echo "6. Slow Query Analysis:\n";
    $slowQueries = $analyzer->getSlowQueries();
    echo "  - Slow queries found: " . $analyzer->getSlowQueryCount() . "\n";
    
    if (!empty($slowQueries)) {
        echo "  - Slow query details:\n";
        foreach ($slowQueries as $index => $metric) {
            $query = $metric->getQuery();
            $time = $metric->getExecutionTimeMs();
            $rows = $metric->getRowsAffected();
            
            // Truncate long queries for display
            $displayQuery = strlen($query) > 60 ? substr($query, 0, 57) . '...' : $query;
            echo "    " . ($index + 1) . ". " . number_format($time, 2) . "ms - $displayQuery ($rows rows)\n";
        }
    } else {
        echo "  - No slow queries detected\n";
    }
    echo "\n";
    
    // 7. Performance recommendations
    echo "7. Performance Recommendations:\n";
    $score = $analyzer->getScore();
    $efficiency = $analyzer->getEfficiency();
    
    switch ($score) {
        case PerformanceAnalyzer::SCORE_EXCELLENT:
            echo "  ✓ Excellent performance! Your queries are running very efficiently.\n";
            break;
        case PerformanceAnalyzer::SCORE_GOOD:
            echo "  ✓ Good performance overall. Consider optimizing slow queries if any.\n";
            break;
        case PerformanceAnalyzer::SCORE_NEEDS_IMPROVEMENT:
            echo "  ⚠ Performance needs improvement. Focus on optimizing slow queries.\n";
            break;
    }
    
    if ($efficiency < 80) {
        echo "  ⚠ Query efficiency is below 80%. Consider:\n";
        echo "    - Adding database indexes\n";
        echo "    - Optimizing query structure\n";
        echo "    - Reviewing WHERE clauses\n";
    }
    
    if ($analyzer->getSlowQueryCount() > 0) {
        echo "  ⚠ Slow queries detected. Consider:\n";
        echo "    - Adding appropriate indexes\n";
        echo "    - Limiting result sets with LIMIT\n";
        echo "    - Breaking complex queries into smaller ones\n";
    }
    echo "\n";
    
    // 8. Cleanup
    echo "8. Cleanup:\n";
    $database->setQuery("DROP TABLE performance_test")->execute();
    echo "✓ Test table dropped\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== Example Complete ===";
