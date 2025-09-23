<?php

require_once '../../vendor/autoload.php';

use WebFiori\Database\ColOption;
use WebFiori\Database\ConnectionInfo;
use WebFiori\Database\Database;
use WebFiori\Database\DataType;

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
    require_once __DIR__.'/UsersSeeder.php';
    require_once __DIR__.'/CategoriesSeeder.php';

    // Create seeder instances
    $usersSeeder = new UsersSeeder();
    $categoriesSeeder = new CategoriesSeeder();

    echo "✓ UsersSeeder loaded\n";
    echo "✓ CategoriesSeeder loaded\n\n";

    echo "3. Running Seeders (Dev Environment):\n";

    // Run seeders manually
    echo "Running: ".$usersSeeder->getName()."\n";
    $usersSeeder->execute($database);
    echo "✓ Users seeder executed\n";

    // Check if categories seeder should run in 'dev' environment
    $environments = $categoriesSeeder->getEnvironments();

    if (empty($environments) || in_array('dev', $environments)) {
        echo "Running: ".$categoriesSeeder->getName()."\n";
        $categoriesSeeder->execute($database);
        echo "✓ Categories seeder executed\n";
    } else {
        echo "Skipping: ".$categoriesSeeder->getName()." (not for dev environment)\n";
    }
    echo "\n";

    echo "4. Verifying Seeded Data:\n";

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

    echo "5. Testing Environment-Specific Seeding:\n";

    // Clear categories and test production environment
    $database->setQuery("DELETE FROM categories")->execute();

    // Check if categories seeder should run in 'prod' environment
    $environments = $categoriesSeeder->getEnvironments();

    if (empty($environments) || in_array('prod', $environments)) {
        echo "Running: ".$categoriesSeeder->getName()." (prod environment)\n";
        $categoriesSeeder->execute($database);
    } else {
        echo "Skipping: ".$categoriesSeeder->getName()." (not for prod environment)\n";
    }

    $result = $database->table('categories')->select()->execute();
    echo "Categories after 'prod' seeding: {$result->getRowsCount()} records\n";
    echo "✓ Environment-specific seeding working correctly\n\n";

    echo "6. Cleanup:\n";
    $database->setQuery("DROP TABLE categories")->execute();
    $database->setQuery("DROP TABLE users")->execute();
    echo "✓ Test tables dropped\n";
} catch (Exception $e) {
    echo "✗ Error: ".$e->getMessage()."\n";

    // Clean up on error
    try {
        $database->setQuery("DROP TABLE IF EXISTS categories")->execute();
        $database->setQuery("DROP TABLE IF EXISTS users")->execute();
    } catch (Exception $cleanupError) {
        // Ignore cleanup errors
    }
}

echo "\n=== Example Complete ===\n";
