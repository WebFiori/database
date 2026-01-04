<?php

require_once '../../vendor/autoload.php';

use WebFiori\Database\ColOption;
use WebFiori\Database\ConnectionInfo;
use WebFiori\Database\Database;
use WebFiori\Database\DataType;
use WebFiori\Database\Schema\SchemaRunner;

echo "=== WebFiori Database Seeders Example ===\n\n";

try {
    // Create connection
    $connection = new ConnectionInfo('mysql', 'root', '123456', 'mysql');
    $database = new Database($connection);

    echo "1. Creating Test Tables:\n";

    // Clean up any existing tables first
    $database->raw("DROP TABLE IF EXISTS categories")->execute();
    $database->raw("DROP TABLE IF EXISTS users")->execute();

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
        'full-name' => [
            ColOption::TYPE => DataType::VARCHAR,
            ColOption::SIZE => 100
        ],
        'role' => [
            ColOption::TYPE => DataType::VARCHAR,
            ColOption::SIZE => 20,
            ColOption::DEFAULT => 'user'
        ],
        'is-active' => [
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

    // Create tables one by one
    $database->table('users')->createTable();
    $database->execute();
    echo "✓ Users table created\n";

    $database->table('categories')->createTable();
    $database->execute();
    echo "✓ Categories table created\n\n";

    echo "2. Loading Seeder Classes:\n";

    // Include seeder classes
    require_once __DIR__.'/UsersSeeder.php';
    require_once __DIR__.'/CategoriesSeeder.php';

    echo "✓ Seeder classes loaded\n\n";

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
        echo "  - ".$change->getName()."\n";
    }
    echo "\n";

    echo "5. Running Seeders (using apply()):\n";

    // Apply all pending seeders
    $result = $runner->apply();

    if ($result->count() > 0) {
        echo "Applied seeders:\n";
        foreach ($result->getApplied() as $change) {
            echo "  ✓ ".$change->getName()."\n";
        }
    } else {
        echo "No seeders to apply (all up to date)\n";
    }
    echo "\n";

    echo "6. Verifying Seeded Data:\n";

    // Check users data
    $usersResult = $database->table('users')->select()->execute();
    echo "Seeded users ({$usersResult->getRowsCount()} records):\n";
    foreach ($usersResult as $user) {
        $status = $user['is_active'] ? 'Active' : 'Inactive';
        echo "  - {$user['full_name']} (@{$user['username']}) - {$user['role']} - $status\n";
    }
    echo "\n";

    // Check categories data
    $categoriesResult = $database->table('categories')->select()->execute();
    echo "Seeded categories ({$categoriesResult->getRowsCount()} records):\n";
    foreach ($categoriesResult as $category) {
        echo "  - {$category['name']} ({$category['slug']})\n";
    }
    echo "\n";

    echo "7. Checking Seeder Status:\n";
    echo "Seeder status:\n";
    foreach ($changes as $change) {
        $status = $runner->isApplied($change->getName()) ? "✓ Applied" : "✗ Pending";
        echo "  {$change->getName()}: $status\n";
    }
    echo "\n";

    echo "8. Rolling Back Seeders:\n";

    // Rollback all seeders (note: seeders don't clear data by default)
    $rolledBack = $runner->rollbackUpTo(null);

    if (!empty($rolledBack)) {
        echo "Rolled back seeders (tracking removed):\n";
        foreach ($rolledBack as $change) {
            echo "  ✓ ".$change->getName()."\n";
        }
    } else {
        echo "No seeders to rollback\n";
    }

    // Note: Data remains because seeders don't implement rollback by default
    $userCount = $database->table('users')->select()->execute()->getRowsCount();
    $categoryCount = $database->table('categories')->select()->execute()->getRowsCount();

    echo "Note: Data remains after rollback (seeders don't clear data by default):\n";
    echo "  Users: $userCount records\n";
    echo "  Categories: $categoryCount records\n\n";

    echo "9. Cleanup:\n";
    $runner->dropSchemaTable();
    $database->raw("DROP TABLE categories")->execute();
    $database->raw("DROP TABLE users")->execute();
    echo "✓ Test tables and schema tracking table dropped\n";
} catch (Exception $e) {
    echo "✗ Error: ".$e->getMessage()."\n";

    // Clean up on error
    try {
        $database->raw("DROP TABLE IF EXISTS categories")->execute();
        $database->raw("DROP TABLE IF EXISTS users")->execute();
        $database->raw("DROP TABLE IF EXISTS schema_changes")->execute();
    } catch (Exception $cleanupError) {
        // Ignore cleanup errors
    }
}

echo "\n=== Example Complete ===\n";
