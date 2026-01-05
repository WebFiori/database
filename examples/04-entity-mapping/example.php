<?php

require_once '../../vendor/autoload.php';

use WebFiori\Database\ColOption;
use WebFiori\Database\ConnectionInfo;
use WebFiori\Database\Database;
use WebFiori\Database\DataType;

echo "=== WebFiori Database Entity Mapping Example ===\n\n";

echo "This example shows entity generation and manual mapping approaches.\n\n";

try {
    $connection = new ConnectionInfo('mysql', 'root', '123456', 'mysql');
    $database = new Database($connection);

    echo "1. Creating User Table:\n";

    $userTable = $database->createBlueprint('users')->addColumns([
        'id' => [ColOption::TYPE => DataType::INT, ColOption::PRIMARY => true, ColOption::AUTO_INCREMENT => true],
        'first-name' => [ColOption::TYPE => DataType::VARCHAR, ColOption::SIZE => 50],
        'last-name' => [ColOption::TYPE => DataType::VARCHAR, ColOption::SIZE => 50],
        'email' => [ColOption::TYPE => DataType::VARCHAR, ColOption::SIZE => 150],
        'age' => [ColOption::TYPE => DataType::INT, ColOption::SIZE => 3]
    ]);

    $database->table('users')->createTable();
    $database->execute();
    echo "✓ User table created\n\n";

    echo "2. Inserting Test Data:\n";

    $database->table('users')->insert(['first-name' => 'Khalid', 'last-name' => 'Al-Rashid', 'email' => 'khalid@example.com', 'age' => 30])->execute();
    $database->table('users')->insert(['first-name' => 'Aisha', 'last-name' => 'Mahmoud', 'email' => 'aisha@example.com', 'age' => 25])->execute();
    $database->table('users')->insert(['first-name' => 'Hassan', 'last-name' => 'Al-Najjar', 'email' => 'hassan@example.com', 'age' => 35])->execute();
    echo "✓ Test users inserted\n\n";

    // ============================================
    // APPROACH 1: Using EntityMapper (Deprecated)
    // ============================================
    echo "3. Using EntityGenerator:\n";

    $entityGenerator = $userTable->getEntityGenerator('User', __DIR__, '');
    $entityGenerator->generate();
    echo "✓ User entity class generated at: ".__DIR__."/User.php\n";

    require_once __DIR__.'/User.php';

    $resultSet = $database->table('users')->select()->execute();
    $mappedUsers = $resultSet->map(fn($record) => new User(
        id: (int) $record['id'],
        firstName: $record['first_name'],
        lastName: $record['last_name'],
        email: $record['email'],
        age: (int) $record['age']
    ));

    echo "Mapped users (EntityGenerator):\n";
    foreach ($mappedUsers as $user) {
        echo "  - {$user->getFirstName()} {$user->getLastName()} ({$user->getEmail()})\n";
    }
    echo "\n";

    // ============================================
    // APPROACH 2: Manual Entity (Recommended)
    // ============================================
    echo "4. Alternative: Manual Entity Mapping:\n";

    // Define entity manually (in real code, this would be in a separate file)
    // See example 11-repository-pattern for full implementation

    echo "Manual entity mapping example:\n";
    $result = $database->table('users')->select()->execute();
    foreach ($result as $row) {
        // Manual mapping - more control, no code generation
        $user = (object) [
            'id' => (int) $row['id'],
            'firstName' => $row['first_name'],
            'lastName' => $row['last_name'],
            'email' => $row['email'],
            'age' => (int) $row['age'],
            'fullName' => $row['first_name'].' '.$row['last_name']
        ];
        echo "  - {$user->fullName} ({$user->email}) - Age: {$user->age}\n";
    }
    echo "\n";

    echo "5. Cleanup:\n";
    $database->raw("DROP TABLE users")->execute();
    echo "✓ User table dropped\n";

    if (file_exists(__DIR__.'/User.php')) {
        unlink(__DIR__.'/User.php');
        echo "✓ Generated User.php file removed\n";
    }
} catch (Exception $e) {
    echo "✗ Error: ".$e->getMessage()."\n";
    try {
        $database->raw("DROP TABLE IF EXISTS users")->execute();
        if (file_exists(__DIR__.'/User.php')) unlink(__DIR__.'/User.php');
    } catch (Exception $cleanupError) {}
}

echo "\n=== Example Complete ===\n";
