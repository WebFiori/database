<?php

require_once '../../vendor/autoload.php';

use WebFiori\Database\ConnectionInfo;
use WebFiori\Database\Database;

echo "=== WebFiori Database CRUD Operations Example ===\n\n";

try {
    // Create connection
    $connection = new ConnectionInfo('mysql', 'root', '123456', 'mysql');
    $database = new Database($connection);
    
    // Create a test table
    echo "1. Creating test table...\n";
    $database->setQuery("
        CREATE TABLE IF NOT EXISTS test_users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(150) NOT NULL,
            age INT
        )
    ")->execute();
    echo "✓ Test table created\n\n";
    
    // Clear any existing data
    $database->setQuery("DELETE FROM test_users")->execute();
    
    // INSERT operations
    echo "2. INSERT Operations:\n";
    
    $database->table('test_users')->insert([
        'name' => 'Ahmed Hassan',
        'email' => 'ahmed@example.com',
        'age' => 30
    ])->execute();
    echo "✓ Inserted Ahmed Hassan\n";
    
    $database->table('test_users')->insert([
        'name' => 'Fatima Al-Zahra',
        'email' => 'fatima@example.com',
        'age' => 25
    ])->execute();
    echo "✓ Inserted Fatima Al-Zahra\n";
    
    $database->table('test_users')->insert([
        'name' => 'Omar Khalil',
        'email' => 'omar@example.com',
        'age' => 35
    ])->execute();
    echo "✓ Inserted Omar Khalil\n\n";
    
    // SELECT operations
    echo "3. SELECT Operations:\n";
    
    // Select all records
    $result = $database->table('test_users')->select()->execute();
    echo "All users:\n";
    foreach ($result as $user) {
        echo "  - {$user['name']} ({$user['email']}) - Age: {$user['age']}\n";
    }
    echo "\n";
    
    // Select with condition
    $result = $database->table('test_users')
                      ->select()
                      ->where('age', 30, '>')
                      ->execute();
    echo "Users older than 30:\n";
    foreach ($result as $user) {
        echo "  - {$user['name']} - Age: {$user['age']}\n";
    }
    echo "\n";
    
    // UPDATE operations
    echo "4. UPDATE Operations:\n";
    $database->table('test_users')
             ->update(['age' => 26])
             ->where('name', 'Fatima Al-Zahra')
             ->execute();
    echo "✓ Updated Fatima Al-Zahra's age to 26\n";
    
    // Verify update
    $result = $database->table('test_users')
                      ->select()
                      ->where('name', 'Fatima Al-Zahra')
                      ->execute();
    foreach ($result as $user) {
        echo "  Fatima's new age: {$user['age']}\n";
    }
    echo "\n";
    
    // DELETE operations
    echo "5. DELETE Operations:\n";
    $database->table('test_users')
             ->delete()
             ->where('name', 'Omar Khalil')
             ->execute();
    echo "✓ Deleted Omar Khalil\n";
    
    // Show remaining users
    $result = $database->table('test_users')->select()->execute();
    echo "Remaining users:\n";
    foreach ($result as $user) {
        echo "  - {$user['name']} ({$user['email']}) - Age: {$user['age']}\n";
    }
    
    // Clean up
    echo "\n6. Cleanup:\n";
    $database->setQuery("DROP TABLE test_users")->execute();
    echo "✓ Test table dropped\n";
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}

echo "\n=== Example Complete ===\n";
