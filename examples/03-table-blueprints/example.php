<?php

require_once '../../vendor/autoload.php';

use WebFiori\Database\ColOption;
use WebFiori\Database\ConnectionInfo;
use WebFiori\Database\Database;
use WebFiori\Database\DataType;

const SEP = "────────────────────────────────────────────────────────────────────\n";

echo "=== WebFiori Database Table Blueprints Example ===\n\n";

try {
    $connection = new ConnectionInfo('mysql', 'root', '123456', 'testing_db');
    $database = new Database($connection);

    echo SEP;
    echo "1. Creating Users Table Blueprint:\n";

    $usersTable = $database->createBlueprint('users')->addColumns([
        'id' => [ColOption::TYPE => DataType::INT, ColOption::PRIMARY => true, ColOption::AUTO_INCREMENT => true],
        'username' => [ColOption::TYPE => DataType::VARCHAR, ColOption::SIZE => 50],
        'email' => [ColOption::TYPE => DataType::VARCHAR, ColOption::SIZE => 150],
        'created-at' => [ColOption::TYPE => DataType::TIMESTAMP, ColOption::DEFAULT => 'current_timestamp']
    ]);

    echo "   ✓ Users table blueprint created\n";
    echo "   Columns: ".implode(', ', $usersTable->getColsKeys())."\n\n";

    echo SEP;
    echo "2. Creating Posts Table Blueprint:\n";

    $postsTable = $database->createBlueprint('posts')->addColumns([
        'id' => [ColOption::TYPE => DataType::INT, ColOption::PRIMARY => true, ColOption::AUTO_INCREMENT => true],
        'user-id' => [ColOption::TYPE => DataType::INT],
        'title' => [ColOption::TYPE => DataType::VARCHAR, ColOption::SIZE => 200],
        'content' => [ColOption::TYPE => DataType::TEXT],
        'created-at' => [ColOption::TYPE => DataType::TIMESTAMP, ColOption::DEFAULT => 'current_timestamp']
    ]);

    echo "   ✓ Posts table blueprint created\n";
    echo "   Columns: ".implode(', ', $postsTable->getColsKeys())."\n\n";

    echo SEP;
    echo "3. Adding Foreign Key Relationship:\n";

    $postsTable->addReference($usersTable, ['user-id' => 'id'], 'user_fk', 'cascade', 'cascade');
    echo "   ✓ Foreign key added (posts.user-id -> users.id)\n\n";

    echo SEP;
    echo "4. Creating Tables:\n";

    $database->table('users')->drop(true)->execute();
    $database->table('posts')->drop(true)->execute();
    $database->createTables();
    echo "   ✓ Tables created\n\n";

    echo SEP;
    echo "5. Testing Tables:\n";

    $database->table('users')->insert([
        'username' => 'ahmad_salem',
        'email' => 'ahmad@example.com'
    ])->execute();
    echo "   ✓ Inserted test user\n";

    $database->table('posts')->insert([
        'user-id' => 1,
        'title' => 'My First Post',
        'content' => 'This is the content of my first post.'
    ])->execute();
    echo "   ✓ Inserted test post\n";

    $result = $database->table('users')->select()->execute();
    echo "   Users:\n";

    foreach ($result as $row) {
        echo "   - {$row['username']} ({$row['email']})\n";
    }
    echo "\n";

    echo SEP;
    echo "6. Cleanup:\n";
    $database->table('posts')->drop()->execute();
    $database->table('users')->drop()->execute();
    echo "   ✓ Tables dropped\n";
} catch (Exception $e) {
    echo "✗ Error: ".$e->getMessage()."\n";
}

echo "\n".SEP;
echo "=== Example Complete ===\n";
