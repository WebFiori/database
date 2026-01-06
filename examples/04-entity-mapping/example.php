<?php

require_once '../../vendor/autoload.php';

use WebFiori\Database\ColOption;
use WebFiori\Database\ConnectionInfo;
use WebFiori\Database\Database;
use WebFiori\Database\DataType;

const SEP = "────────────────────────────────────────────────────────────────────\n";

echo "=== WebFiori Database Entity Mapping Example ===\n\n";
echo "This example shows entity generation and manual mapping approaches.\n\n";

try {
    $connection = new ConnectionInfo('mysql', 'root', '123456', 'testing_db');
    $database = new Database($connection);

    echo SEP;
    echo "1. Creating User Table:\n";

    $userTable = $database->createBlueprint('users')->addColumns([
        'id' => [ColOption::TYPE => DataType::INT, ColOption::PRIMARY => true, ColOption::AUTO_INCREMENT => true],
        'first-name' => [ColOption::TYPE => DataType::VARCHAR, ColOption::SIZE => 50],
        'last-name' => [ColOption::TYPE => DataType::VARCHAR, ColOption::SIZE => 50],
        'email' => [ColOption::TYPE => DataType::VARCHAR, ColOption::SIZE => 150],
        'age' => [ColOption::TYPE => DataType::INT]
    ]);

    $database->table('users')->drop(true)->execute();
    $database->table('users')->createTable()->execute();
    echo "   ✓ User table created\n\n";

    echo SEP;
    echo "2. Inserting Test Data:\n";

    $database->table('users')->insert([
        'cols' => ['first-name', 'last-name', 'email', 'age'],
        'values' => [
            ['Khalid', 'Al-Rashid', 'khalid@example.com', 30],
            ['Aisha', 'Mahmoud', 'aisha@example.com', 25],
            ['Hassan', 'Al-Najjar', 'hassan@example.com', 35]
        ]
    ])->execute();
    echo "   ✓ 3 test users inserted\n\n";

    echo SEP;
    echo "3. Using EntityGenerator:\n";

    $entityGenerator = $userTable->getEntityGenerator('User', __DIR__, '');
    $entityGenerator->generate();
    echo "   ✓ User entity class generated at: ".__DIR__."/User.php\n";

    require_once __DIR__.'/User.php';

    $resultSet = $database->table('users')->select()->execute();
    $mappedUsers = $resultSet->map(fn($record) => new User(
        id: (int) $record['id'],
        firstName: $record['first_name'],
        lastName: $record['last_name'],
        email: $record['email'],
        age: (int) $record['age']
    ));

    echo "   Mapped users:\n";
    foreach ($mappedUsers as $user) {
        echo "   - {$user->getFirstName()} {$user->getLastName()} ({$user->getEmail()})\n";
    }
    echo "\n";

    echo SEP;
    echo "4. Manual Entity Mapping:\n";

    $result = $database->table('users')->select()->execute();
    foreach ($result as $row) {
        $user = (object) [
            'id' => (int) $row['id'],
            'firstName' => $row['first_name'],
            'lastName' => $row['last_name'],
            'email' => $row['email'],
            'fullName' => $row['first_name'].' '.$row['last_name']
        ];
        echo "   - {$user->fullName} ({$user->email})\n";
    }
    echo "\n";

    echo SEP;
    echo "5. Cleanup:\n";
    $database->table('users')->drop()->execute();
    echo "   ✓ User table dropped\n";

    if (file_exists(__DIR__.'/User.php')) {
        unlink(__DIR__.'/User.php');
        echo "   ✓ Generated User.php file removed\n";
    }

} catch (Exception $e) {
    echo "✗ Error: ".$e->getMessage()."\n";
    try {
        $database->table('users')->drop(true)->execute();
        if (file_exists(__DIR__.'/User.php')) unlink(__DIR__.'/User.php');
    } catch (Exception $cleanupError) {}
}

echo "\n" . SEP;
echo "=== Example Complete ===\n";
