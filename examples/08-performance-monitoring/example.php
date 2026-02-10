<?php

require_once '../../vendor/autoload.php';

use WebFiori\Database\ColOption;
use WebFiori\Database\ConnectionInfo;
use WebFiori\Database\Database;
use WebFiori\Database\DataType;
use WebFiori\Database\Performance\PerformanceAnalyzer;
use WebFiori\Database\Performance\PerformanceOption;

const SEP = "────────────────────────────────────────────────────────────────────\n";

echo "=== WebFiori Database Performance Monitoring Example ===\n\n";

try {
    $connection = new ConnectionInfo('mysql', 'root', '123456', 'testing_db', 'localhost');
    $database = new Database($connection);

    echo SEP;
    echo "1. Configuring Performance Monitoring:\n";
    $database->setPerformanceConfig([
        PerformanceOption::ENABLED => true,
        PerformanceOption::SLOW_QUERY_THRESHOLD => 50,
        PerformanceOption::WARNING_THRESHOLD => 25,
        PerformanceOption::SAMPLING_RATE => 1.0,
        PerformanceOption::MAX_SAMPLES => 1000
    ]);
    echo "   ✓ Performance monitoring configured\n";
    echo "   - Slow query threshold: 50ms\n";
    echo "   - Warning threshold: 25ms\n";
    echo "   - Sampling rate: 100%\n\n";

    echo SEP;
    echo "2. Creating Test Table:\n";
    $database->createBlueprint('performance_test')->addColumns([
        'id' => [ColOption::TYPE => DataType::INT, ColOption::PRIMARY => true, ColOption::AUTO_INCREMENT => true],
        'name' => [ColOption::TYPE => DataType::VARCHAR, ColOption::SIZE => 100],
        'email' => [ColOption::TYPE => DataType::VARCHAR, ColOption::SIZE => 150]
    ]);
    $database->table('performance_test')->drop(true)->execute();
    $database->table('performance_test')->createTable()->execute();
    echo "   ✓ Test table created\n\n";

    echo SEP;
    echo "3. Executing Test Queries:\n";

    // Fast queries - individual inserts for performance monitoring
    for ($i = 1; $i <= 5; $i++) {
        $database->table('performance_test')->insert([
            'name' => "User $i",
            'email' => "user$i@example.com"
        ])->execute();
    }
    echo "   ✓ Executed 5 INSERT queries\n";

    // Medium speed queries
    for ($i = 1; $i <= 3; $i++) {
        $database->table('performance_test')
                 ->select()
                 ->where('email', "user$i@example.com")
                 ->execute();
    }
    echo "   ✓ Executed 3 SELECT queries\n";

    // Simulate slow queries with SLEEP
    $database->raw("SELECT SLEEP(0.03)")->execute();
    $database->raw("SELECT SLEEP(0.08)")->execute();
    $database->raw("SELECT SLEEP(0.12)")->execute();
    echo "   ✓ Executed 3 slow queries with artificial delays\n\n";

    echo SEP;
    echo "4. Performance Analysis:\n";
    $analyzer = $database->getPerformanceMonitor()->getAnalyzer();

    echo "   Query Statistics:\n";
    echo "   - Total queries: ".$analyzer->getQueryCount()."\n";
    echo "   - Total execution time: ".number_format($analyzer->getTotalTime(), 2)." ms\n";
    echo "   - Average execution time: ".number_format($analyzer->getAverageTime(), 2)." ms\n";
    echo "   - Performance score: ".$analyzer->getScore()."\n";
    echo "   - Query efficiency: ".number_format($analyzer->getEfficiency(), 1)."%\n\n";

    echo SEP;
    echo "5. Slow Query Analysis:\n";
    $slowQueries = $analyzer->getSlowQueries();
    echo "   - Slow queries found: ".$analyzer->getSlowQueryCount()."\n";

    if (!empty($slowQueries)) {
        echo "   - Slow query details:\n";

        foreach ($slowQueries as $index => $metric) {
            $query = $metric->getQuery();
            $time = $metric->getExecutionTimeMs();
            $rows = $metric->getRowsAffected();
            $displayQuery = strlen($query) > 60 ? substr($query, 0, 57).'...' : $query;
            echo "     ".($index + 1).". ".number_format($time, 2)."ms - $displayQuery ($rows rows)\n";
        }
    }
    echo "\n";

    echo SEP;
    echo "6. Performance Recommendations:\n";
    $score = $analyzer->getScore();
    $efficiency = $analyzer->getEfficiency();

    switch ($score) {
        case PerformanceAnalyzer::SCORE_EXCELLENT:
            echo "   ✓ Excellent performance! Your queries are running very efficiently.\n";
            break;
        case PerformanceAnalyzer::SCORE_GOOD:
            echo "   ✓ Good performance overall. Consider optimizing slow queries if any.\n";
            break;
        case PerformanceAnalyzer::SCORE_NEEDS_IMPROVEMENT:
            echo "   ⚠ Performance needs improvement. Focus on optimizing slow queries.\n";
            break;
    }

    if ($efficiency < 80) {
        echo "   ⚠ Query efficiency is below 80%. Consider:\n";
        echo "     - Adding database indexes\n";
        echo "     - Optimizing query structure\n";
        echo "     - Reviewing WHERE clauses\n";
    }

    if ($analyzer->getSlowQueryCount() > 0) {
        echo "   ⚠ Slow queries detected. Consider:\n";
        echo "     - Adding appropriate indexes\n";
        echo "     - Limiting result sets with LIMIT\n";
        echo "     - Breaking complex queries into smaller ones\n";
    }
    echo "\n";

    echo SEP;
    echo "7. Cleanup:\n";
    $database->table('performance_test')->drop()->execute();
    echo "   ✓ Test table dropped\n";
} catch (Exception $e) {
    echo "✗ Error: ".$e->getMessage()."\n";
    try {
        $database->table('performance_test')->drop(true)->execute();
    } catch (Exception $cleanupError) {
    }
}

echo "\n".SEP;
echo "=== Example Complete ===\n";
