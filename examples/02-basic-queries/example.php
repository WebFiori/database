<?php

require_once '../../vendor/autoload.php';

use WebFiori\Database\ConnectionInfo;
use WebFiori\Database\Database;
use WebFiori\Database\MultiResultSet;

echo "=== WebFiori Database CRUD Operations Example ===\n\n";

try {
    // Create connection
    $connection = new ConnectionInfo('mysql', 'root', '123456', 'mysql');
    $database = new Database($connection);

    // Create a test table using raw()
    echo "1. Creating test table...\n";
    $database->raw("
        CREATE TABLE IF NOT EXISTS test_users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(150) NOT NULL,
            age INT
        )
    ")->execute();
    echo "✓ Test table created\n\n";

    // Clear any existing data using raw()
    $database->raw("DELETE FROM test_users")->execute();

    // INSERT operations using raw() with parameters
    echo "2. INSERT Operations with Parameters:\n";

    $database->raw("INSERT INTO test_users (name, email, age) VALUES (?, ?, ?)", [
        'Ahmed Hassan', 'ahmed@example.com', 30
    ])->execute();
    echo "✓ Inserted Ahmed Hassan\n";

    $database->raw("INSERT INTO test_users (name, email, age) VALUES (?, ?, ?)", [
        'Fatima Al-Zahra', 'fatima@example.com', 25
    ])->execute();
    echo "✓ Inserted Fatima Al-Zahra\n";

    $database->raw("INSERT INTO test_users (name, email, age) VALUES (?, ?, ?)", [
        'Omar Khalil', 'omar@example.com', 35
    ])->execute();
    echo "✓ Inserted Omar Khalil\n\n";

    // SELECT operations using raw() with parameters
    echo "3. SELECT Operations with Parameters:\n";

    // Select all records
    $result = $database->raw("SELECT * FROM test_users")->execute();
    echo "All users:\n";

    foreach ($result as $user) {
        echo "  - {$user['name']} ({$user['email']}) - Age: {$user['age']}\n";
    }
    echo "\n";

    // Select with condition using parameters
    $result = $database->raw("SELECT * FROM test_users WHERE age > ?", [30])->execute();
    echo "Users older than 30:\n";

    foreach ($result as $user) {
        echo "  - {$user['name']} - Age: {$user['age']}\n";
    }
    echo "\n";

    // Multi-parameter query
    $result = $database->raw("SELECT * FROM test_users WHERE age BETWEEN ? AND ?", [25, 35])->execute();
    echo "Users between 25 and 35:\n";

    foreach ($result as $user) {
        echo "  - {$user['name']} - Age: {$user['age']}\n";
    }
    echo "\n";

    // UPDATE operations using raw() with parameters
    echo "4. UPDATE Operations with Parameters:\n";
    $database->raw("UPDATE test_users SET age = ? WHERE name = ?", [26, 'Fatima Al-Zahra'])->execute();
    echo "✓ Updated Fatima Al-Zahra's age to 26\n";

    // Verify update
    $result = $database->raw("SELECT * FROM test_users WHERE name = ?", ['Fatima Al-Zahra'])->execute();

    foreach ($result as $user) {
        echo "  Fatima's new age: {$user['age']}\n";
    }
    echo "\n";

    // DELETE operations using raw() with parameters
    echo "5. DELETE Operations with Parameters:\n";
    $database->raw("DELETE FROM test_users WHERE name = ?", ['Omar Khalil'])->execute();
    echo "✓ Deleted Omar Khalil\n";

    // Show remaining users
    $result = $database->raw("SELECT * FROM test_users")->execute();
    echo "Remaining users:\n";

    foreach ($result as $user) {
        echo "  - {$user['name']} ({$user['email']}) - Age: {$user['age']}\n";
    }

    // Multi-Result Query Example
    echo "\n6. Multi-Result Query Example:\n";
    
    // Create a stored procedure that returns multiple result sets
    $database->raw("DROP PROCEDURE IF EXISTS GetUserStats")->execute();
    $database->raw("
        CREATE PROCEDURE GetUserStats()
        BEGIN
            SELECT 'User List' as report_type;
            SELECT name, age FROM test_users ORDER BY age;
            SELECT COUNT(*) as total_users, AVG(age) as avg_age FROM test_users;
        END
    ")->execute();
    
    // Execute the stored procedure
    $result = $database->raw("CALL GetUserStats()")->execute();
    
    if ($result instanceof MultiResultSet) {
        echo "✓ Multi-result query executed successfully!\n";
        echo "Number of result sets: " . $result->count() . "\n";
        
        for ($i = 0; $i < $result->count(); $i++) {
            $resultSet = $result->getResultSet($i);
            echo "\nResult Set " . ($i + 1) . ":\n";
            
            foreach ($resultSet as $row) {
                echo "  ";
                foreach ($row as $key => $value) {
                    echo "$key: $value  ";
                }
                echo "\n";
            }
        }
    } else {
        echo "Single result set returned:\n";
        foreach ($result as $row) {
            echo "  ";
            foreach ($row as $key => $value) {
                echo "$key: $value  ";
            }
            echo "\n";
        }
    }

    // Another multi-result example with conditional logic
    echo "\n7. Complex Multi-Result Example:\n";
    
    $database->raw("DROP PROCEDURE IF EXISTS ComplexStats")->execute();
    $database->raw("
        CREATE PROCEDURE ComplexStats()
        BEGIN
            -- First result: All users
            SELECT 'All Users' as section, name, email, age FROM test_users;
            
            -- Second result: Statistics
            SELECT 
                'Statistics' as section,
                COUNT(*) as total_count,
                MIN(age) as min_age,
                MAX(age) as max_age,
                AVG(age) as avg_age
            FROM test_users;
                
            -- Third result: Age groups
            SELECT 
                'Age Groups' as section,
                CASE 
                    WHEN age < 30 THEN 'Young'
                    WHEN age >= 30 THEN 'Mature'
                END as age_group,
                COUNT(*) as count
            FROM test_users 
            GROUP BY age_group;
        END
    ")->execute();
    
    $complexResult = $database->raw("CALL ComplexStats()")->execute();
    
    if ($complexResult instanceof MultiResultSet) {
        echo "✓ Complex multi-result query executed!\n";
        
        // Process each result set with specific handling
        for ($i = 0; $i < $complexResult->count(); $i++) {
            $rs = $complexResult->getResultSet($i);
            if ($rs->getRowsCount() > 0) {
                $firstRow = $rs->getRows()[0];
                
                if (isset($firstRow['section'])) {
                    echo "\n--- {$firstRow['section']} ---\n";
                    
                    foreach ($rs as $row) {
                        if ($row['section'] === 'All Users') {
                            echo "  User: {$row['name']} ({$row['email']}) - Age: {$row['age']}\n";
                        } elseif ($row['section'] === 'Statistics') {
                            echo "  Total: {$row['total_count']}, Min Age: {$row['min_age']}, Max Age: {$row['max_age']}, Avg Age: " . round($row['avg_age'], 1) . "\n";
                        } elseif ($row['section'] === 'Age Groups') {
                            echo "  {$row['age_group']}: {$row['count']} users\n";
                        }
                    }
                }
            }
        }
    }

    // Clean up
    echo "\n8. Cleanup:\n";
    $database->raw("DROP PROCEDURE IF EXISTS GetUserStats")->execute();
    $database->raw("DROP PROCEDURE IF EXISTS ComplexStats")->execute();
    $database->raw("DROP TABLE test_users")->execute();
    echo "✓ Test table and procedures dropped\n";

} catch (Exception $e) {
    echo "✗ Error: ".$e->getMessage()."\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== Example Complete ===\n";
