<?php

require_once '../../vendor/autoload.php';

use WebFiori\Database\ColOption;
use WebFiori\Database\ConnectionInfo;
use WebFiori\Database\Database;
use WebFiori\Database\DataType;
use WebFiori\Database\MultiResultSet;

const SEP = "────────────────────────────────────────────────────────────────────\n";

echo "=== WebFiori Database CRUD Operations Example ===\n\n";

try {
    $connection = new ConnectionInfo('mysql', 'root', '123456', 'testing_db');
    $database = new Database($connection);

    // Create table
    echo SEP;
    echo "1. Creating Table:\n";

    $database->createBlueprint('test_users')->addColumns([
        'id' => [ColOption::TYPE => DataType::INT, ColOption::PRIMARY => true, ColOption::AUTO_INCREMENT => true],
        'name' => [ColOption::TYPE => DataType::VARCHAR, ColOption::SIZE => 100],
        'email' => [ColOption::TYPE => DataType::VARCHAR, ColOption::SIZE => 150],
        'age' => [ColOption::TYPE => DataType::INT]
    ]);

    $database->table('test_users')->drop(true)->execute();
    $database->table('test_users')->createTable()->execute();
    echo "   ✓ Table created\n\n";

    // INSERT operations
    echo SEP;
    echo "2. INSERT Operations:\n";

    $database->table('test_users')->insert([
        'cols' => ['name', 'email', 'age'],
        'values' => [
            ['Ahmed Hassan', 'ahmed@example.com', 30],
            ['Fatima Al-Zahra', 'fatima@example.com', 25],
            ['Omar Khalil', 'omar@example.com', 35]
        ]
    ])->execute();
    echo "   ✓ Inserted 3 users\n\n";

    // SELECT operations
    echo SEP;
    echo "3. SELECT Operations:\n";

    $result = $database->table('test_users')->select()->execute();
    echo "   All users:\n";
    foreach ($result as $user) {
        echo "   - {$user['name']} ({$user['email']}) - Age: {$user['age']}\n";
    }
    echo "\n";

    $result = $database->table('test_users')->select()->where('age', 30, '>')->execute();
    echo "   Users older than 30:\n";
    foreach ($result as $user) {
        echo "   - {$user['name']} - Age: {$user['age']}\n";
    }
    echo "\n";

    // UPDATE operations
    echo SEP;
    echo "4. UPDATE Operations:\n";

    $database->table('test_users')->update(['age' => 26])->where('name', 'Fatima Al-Zahra')->execute();
    echo "   ✓ Updated Fatima Al-Zahra's age to 26\n";

    $result = $database->table('test_users')->select()->where('name', 'Fatima Al-Zahra')->execute();
    foreach ($result as $user) {
        echo "   Fatima's new age: {$user['age']}\n";
    }
    echo "\n";

    // DELETE operations
    echo SEP;
    echo "5. DELETE Operations:\n";

    $database->table('test_users')->delete()->where('name', 'Omar Khalil')->execute();
    echo "   ✓ Deleted Omar Khalil\n";

    $result = $database->table('test_users')->select()->execute();
    echo "   Remaining users:\n";
    foreach ($result as $user) {
        echo "   - {$user['name']} ({$user['email']}) - Age: {$user['age']}\n";
    }
    echo "\n";

    // Cleanup
    echo SEP;
    echo "6. Cleanup:\n";
    $database->table('test_users')->drop()->execute();
    echo "   ✓ Table dropped\n";

} catch (Exception $e) {
    echo "✗ Error: ".$e->getMessage()."\n";
}

echo "\n" . SEP;
echo "=== Example Complete ===\n";
