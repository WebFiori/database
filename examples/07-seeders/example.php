<?php

require_once '../../vendor/autoload.php';

use WebFiori\Database\ConnectionInfo;
use WebFiori\Database\Database;
use WebFiori\Database\DataType;
use WebFiori\Database\ColOption;
use WebFiori\Database\Schema\SchemaRunner;

echo "=== WebFiori Database Seeders Example ===\n\n";

try {
    // Create connection
    $connection = new ConnectionInfo('mysql', 'root', '123456', 'mysql');
    $database = new Database($connection);
    
    echo "1. Creating Test Tables:\n";
    
    // Clean up any existing tables first
    $database->setQuery("DROP TABLE IF EXISTS categories")->execute();
    $database->setQuery("DROP TABLE IF EXISTS users")->execute();
    
    // Create users table
    $database->createBlueprint('users')->addColumns([
        'id' => [
            ColOption::TYPE => DataType::INT,
            ColOption::SIZE => 11,
            ColOption::PRIMARY => true,
            ColOption::AUTO_INCREMENT => true
        ],
        'username' => [
            ColOption::TYPE => DataType::VARCHAR,
            ColOption::SIZE => 50,
            ColOption::NULL => false
        ],
        'email' => [
            ColOption::TYPE => DataType::VARCHAR,
            ColOption::SIZE => 150,
            ColOption::NULL => false
        ],
        'full_name' => [
            ColOption::TYPE => DataType::VARCHAR,
            ColOption::SIZE => 100
        ],
        'role' => [
            ColOption::TYPE => DataType::VARCHAR,
            ColOption::SIZE => 20,
            ColOption::DEFAULT => 'user'
        ],
        'is_active' => [
            ColOption::TYPE => DataType::BOOL,
            ColOption::DEFAULT => true
        ]
    ]);
    
    // Create categories table
    $database->createBlueprint('categories')->addColumns([
        'id' => [
            ColOption::TYPE => DataType::INT,
            ColOption::SIZE => 11,
            ColOption::PRIMARY => true,
            ColOption::AUTO_INCREMENT => true
        ],
        'name' => [
            ColOption::TYPE => DataType::VARCHAR,
            ColOption::SIZE => 100,
            ColOption::NULL => false
        ],
        'description' => [
            ColOption::TYPE => DataType::TEXT
        ],
        'slug' => [
            ColOption::TYPE => DataType::VARCHAR,
            ColOption::SIZE => 100,
            ColOption::NULL => false
        ]
    ]);
    
    $database->createTables();
    $database->execute();
    
    echo "✓ Test tables created\n\n";
    
    echo "2. Loading Seeder Classes:\n";
    
    // Include seeder classes
    require_once __DIR__ . '/UsersSeeder.php';
    require_once __DIR__ . '/CategoriesSeeder.php';
    
    echo "✓ Seeder classes loaded\n";
    
    echo "3. Setting up Schema Runner:\n";
    
    // Create schema runner
    $runner = new SchemaRunner($connection);
    
    // Register seeder classes
    $runner->register('UsersSeeder');
    $runner->register('CategoriesSeeder');
    
    echo "✓ Schema runner created\n";
    echo "✓ Seeder classes registered\n";
    
    // Create schema tracking table
    $runner->createSchemaTable();
    echo "✓ Schema tracking table created\n\n";
    
    echo "4. Checking Available Seeders:\n";
    
    $changes = $runner->getChanges();
    echo "Registered seeders:\n";
    foreach ($changes as $change) {
        echo "  - " . $change->getName() . "\n";
    }
    echo "\n";
    
    echo "5. Running Seeders:\n";
    
    // Force apply all seeders
    $appliedChanges = [];
    
    foreach ($changes as $change) {
        if (!$runner->isApplied($change->getName())) {
            $change->execute($database);
            $appliedChanges[] = $change;
            echo "  ✓ Applied: " . $change->getName() . "\n";
        }
    }
    
    if (empty($appliedChanges)) {
        echo "No seeders to apply (all up to date)\n";
    }
    echo "\n";
    
    echo "6. Verifying Seeded Data:\n";
    
    // Check users data
    $result = $database->table('users')->select()->execute();
    echo "Seeded users ({$result->getRowsCount()} records):\n";
    foreach ($result as $user) {
        $status = $user['is_active'] ? 'Active' : 'Inactive';
        echo "  - {$user['full_name']} (@{$user['username']}) - {$user['role']} - {$status}\n";
    }
    echo "\n";
    
    // Check categories data
    $result = $database->table('categories')->select()->execute();
    echo "Seeded categories ({$result->getRowsCount()} records):\n";
    foreach ($result as $category) {
        echo "  - {$category['name']} ({$category['slug']})\n";
        echo "    {$category['description']}\n";
    }
    echo "\n";
    
    echo "7. Testing Seeder Status:\n";
    
    // Check which seeders are applied
    echo "Seeder status:\n";
    foreach ($changes as $change) {
        $status = $runner->isApplied($change->getName()) ? "✓ Applied" : "✗ Pending";
        echo "  {$change->getName()}: $status\n";
    }
    echo "\n";
    
    echo "8. Rolling Back Seeders:\n";
    
    // Rollback all seeders (this will clear the data)
    $rolledBackChanges = [];
    
    // Reverse order for rollback
    $reversedChanges = array_reverse($changes);
    foreach ($reversedChanges as $change) {
        $change->rollback($database);
        $rolledBackChanges[] = $change;
        echo "  ✓ Rolled back: " . $change->getName() . "\n";
    }
    
    // Verify rollback
    $userCount = $database->table('users')->select()->execute()->getRowsCount();
    $categoryCount = $database->table('categories')->select()->execute()->getRowsCount();
    
    echo "After rollback:\n";
    echo "  Users: $userCount records\n";
    echo "  Categories: $categoryCount records\n";
    echo "✓ Seeders rolled back successfully\n\n";
    
    echo "9. Cleanup:\n";
    $runner->dropSchemaTable();
    $database->setQuery("DROP TABLE categories")->execute();
    $database->setQuery("DROP TABLE users")->execute();
    echo "✓ Test tables and schema tracking table dropped\n";
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    
    // Clean up on error
    try {
        $database->setQuery("DROP TABLE IF EXISTS categories")->execute();
        $database->setQuery("DROP TABLE IF EXISTS users")->execute();
        $database->setQuery("DROP TABLE IF EXISTS schema_changes")->execute();
    } catch (Exception $cleanupError) {
        // Ignore cleanup errors
    }
}

echo "\n=== Example Complete ===\n";
