<?php

require_once '../../vendor/autoload.php';

use WebFiori\Database\ColOption;
use WebFiori\Database\ConnectionInfo;
use WebFiori\Database\Database;
use WebFiori\Database\DataType;

echo "=== WebFiori Database Table Blueprints Example ===\n\n";

try {
    // Create connection
    $connection = new ConnectionInfo('mysql', 'root', '123456', 'mysql');
    $database = new Database($connection);

    echo "1. Creating Users Table Blueprint:\n";

    // Create users table blueprint
    $usersTable = $database->createBlueprint('users')->addColumns([
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
        'created_at' => [
            ColOption::TYPE => DataType::TIMESTAMP,
            ColOption::DEFAULT => 'current_timestamp'
        ]
    ]);

    echo "✓ Users table blueprint created\n";
    echo "  Columns: ".implode(', ', array_keys($usersTable->getCols()))."\n\n";

    echo "2. Creating Posts Table Blueprint:\n";

    // Create posts table blueprint
    $postsTable = $database->createBlueprint('posts')->addColumns([
        'id' => [
            ColOption::TYPE => DataType::INT,
            ColOption::SIZE => 11,
            ColOption::PRIMARY => true,
            ColOption::AUTO_INCREMENT => true
        ],
        'user-id' => [
            ColOption::TYPE => DataType::INT,
            ColOption::SIZE => 11,
            ColOption::NULL => false
        ],
        'title' => [
            ColOption::TYPE => DataType::VARCHAR,
            ColOption::SIZE => 200,
            ColOption::NULL => false
        ],
        'content' => [
            ColOption::TYPE => DataType::TEXT
        ],
        'created-at' => [
            ColOption::TYPE => DataType::TIMESTAMP,
            ColOption::DEFAULT => 'current_timestamp'
        ]
    ]);

    echo "✓ Posts table blueprint created\n";
    echo "  Columns: ".implode(', ', array_keys($postsTable->getCols()))."\n\n";

    echo "3. Adding Foreign Key Relationship:\n";

    // Add foreign key relationship with CASCADE actions
    $postsTable->addReference($usersTable, ['user-id' => 'id'], 'user_fk', 'cascade', 'cascade');
    echo "✓ Foreign key relationship added (posts.user_id -> users.id)\n\n";

    echo "4. Creating Tables One by One:\n";

    // Create users table first (no dependencies)
    $database->table('users')->createTable();
    echo "SQL for users table:\n".$database->getLastQuery()."\n\n";
    $database->execute();
    echo "✓ Users table created\n";

    // Create posts table (depends on users)
    $database->table('posts')->createTable();
    echo "SQL for posts table:\n".$database->getLastQuery()."\n\n";
    $database->execute();
    echo "✓ Posts table created\n\n";

    echo "5. Testing the Created Tables:\n";

    // Insert test data
    $database->table('users')->insert([
        'username' => 'ahmad_salem',
        'email' => 'ahmad@example.com'
    ])->execute();
    echo "✓ Inserted test user\n";

    // Get the user ID
    $userResult = $database->table('users')
                          ->select(['id'])
                          ->where('username', 'ahmad_salem')
                          ->execute();
    $userId = $userResult->getRows()[0]['id'];

    $database->table('posts')->insert([
        'user-id' => $userId,
        'title' => 'My First Post',
        'content' => 'This is the content of my first post.'
    ])->execute();
    echo "✓ Inserted test post\n";

    // Query with join to show relationship
    $result = $database->raw("
        SELECT u.username, p.title, p.created_at 
        FROM users u 
        JOIN posts p ON u.id = p.user_id
    ")->execute();

    echo "\nJoined data:\n";
    foreach ($result as $row) {
        echo "  User: {$row['username']}, Post: {$row['title']}, Created: {$row['created_at']}\n";
    }

    echo "\n6. Using Custom Table Class:\n";

    // Include the custom table class
    require_once __DIR__.'/UserTable.php';

    // Create an instance of the custom table
    $customTable = new UserTable();

    echo "✓ Custom UserTable class created\n";
    echo "  Table name: ".$customTable->getName()."\n";
    echo "  Engine: ".$customTable->getEngine()."\n";
    echo "  Charset: ".$customTable->getCharSet()."\n";

    // Generate and execute CREATE TABLE for custom table
    $createQuery = $customTable->toSQL();
    echo "\nGenerated SQL for custom table:\n$createQuery\n\n";

    // Execute the custom table creation
    $database->raw($createQuery)->execute();
    echo "✓ Custom table created successfully\n";

    // Test the custom table
    $database->addTable($customTable);
    $database->table('users_extended')->insert([
        'username' => 'sara_ahmad',
        'email' => 'sara@example.com',
        'full-name' => 'Sara Ahmad Al-Mansouri'
    ])->execute();
    echo "✓ Inserted test data into custom table\n";

    // Query the custom table
    $result = $database->table('users_extended')->select()->execute();
    echo "Custom table data:\n";
    foreach ($result as $row) {
        echo "  User: {$row['full_name']} ({$row['username']}) - Active: ".($row['is_active'] ? 'Yes' : 'No')."\n";
    }

    echo "\n7. Cleanup:\n";
    $database->raw("DROP TABLE IF EXISTS users_extended")->execute();
    $database->raw("DROP TABLE IF EXISTS posts")->execute();
    $database->raw("DROP TABLE IF EXISTS users")->execute();
    echo "✓ Tables dropped\n";
} catch (Exception $e) {
    echo "✗ Error: ".$e->getMessage()."\n";
}

echo "\n=== Example Complete ===\n";
