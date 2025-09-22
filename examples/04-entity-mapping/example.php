<?php

require_once '../../vendor/autoload.php';

use WebFiori\Database\ConnectionInfo;
use WebFiori\Database\Database;
use WebFiori\Database\DataType;
use WebFiori\Database\ColOption;

echo "=== WebFiori Database Entity Mapping Example ===\n\n";

try {
    // Create connection
    $connection = new ConnectionInfo('mysql', 'root', '123456', 'mysql');
    $database = new Database($connection);
    
    echo "1. Creating User Table Blueprint:\n";
    
    // Create user table blueprint
    $userTable = $database->createBlueprint('users')->addColumns([
        'id' => [
            ColOption::TYPE => DataType::INT,
            ColOption::SIZE => 11,
            ColOption::PRIMARY => true,
            ColOption::AUTO_INCREMENT => true
        ],
        'first_name' => [
            ColOption::TYPE => DataType::VARCHAR,
            ColOption::SIZE => 50,
            ColOption::NULL => false
        ],
        'last_name' => [
            ColOption::TYPE => DataType::VARCHAR,
            ColOption::SIZE => 50,
            ColOption::NULL => false
        ],
        'email' => [
            ColOption::TYPE => DataType::VARCHAR,
            ColOption::SIZE => 150,
            ColOption::NULL => false
        ],
        'age' => [
            ColOption::TYPE => DataType::INT,
            ColOption::SIZE => 3
        ]
    ]);
    
    echo "✓ User table blueprint created\n\n";
    
    echo "2. Creating Entity Class:\n";
    
    // Get entity mapper and create entity class
    $entityMapper = $userTable->getEntityMapper();
    $entityMapper->setEntityName('User');
    $entityMapper->setNamespace('');
    $entityMapper->setPath(__DIR__);
    
    // Create the entity class
    $entityMapper->create();
    echo "✓ User entity class created at: " . __DIR__ . "/User.php\n";
    
    
    echo "3. Creating Table in Database:\n";
    
    // Create the table
    $database->createTables();
    $database->execute();
    echo "✓ User table created in database\n\n";
    
    echo "4. Inserting Test Data:\n";
    
    // Insert test users
    $database->table('users')->insert([
        'first_name' => 'Khalid',
        'last_name' => 'Al-Rashid',
        'email' => 'khalid.rashid@example.com',
        'age' => 30
    ])->execute();
    
    $database->table('users')->insert([
        'first_name' => 'Aisha',
        'last_name' => 'Mahmoud',
        'email' => 'aisha.mahmoud@example.com',
        'age' => 25
    ])->execute();
    
    $database->table('users')->insert([
        'first_name' => 'Hassan',
        'last_name' => 'Al-Najjar',
        'email' => 'hassan.najjar@example.com',
        'age' => 35
    ])->execute();
    
    echo "✓ Test users inserted\n\n";
    
    echo "5. Fetching and Mapping Records:\n";
    
    // Include the generated entity class
    require_once __DIR__ . '/User.php';
    
    // Fetch records and map to objects
    $resultSet = $database->table('users')->select()->execute();
    
    $mappedUsers = $resultSet->map(function (array $record) {
        return User::map($record);
    });
    
    echo "Mapped users as objects:\n";
    foreach ($mappedUsers as $user) {
        echo "  - {$user->getFirstName()} {$user->getLastName()} ({$user->getEmail()}) - Age: {$user->getAge()}\n";
    }
    echo "\n";
    
    echo "6. Working with Individual Objects:\n";
    
    // Get first user and demonstrate object methods
    $firstUser = $mappedUsers->getRows()[0];
    echo "First user details:\n";
    echo "  ID: {$firstUser->getId()}\n";
    echo "  Full Name: {$firstUser->getFirstName()} {$firstUser->getLastName()}\n";
    echo "  Email: {$firstUser->getEmail()}\n";
    echo "  Age: {$firstUser->getAge()}\n\n";
    
    echo "7. Filtering with Entity Objects:\n";
    
    // Filter users by age
    $adultUsers = [];
    foreach ($mappedUsers as $user) {
        if ($user->getAge() >= 30) {
            $adultUsers[] = $user;
        }
    }
    
    echo "Users 30 or older:\n";
    foreach ($adultUsers as $user) {
        echo "  - {$user->getFirstName()} {$user->getLastName()} (Age: {$user->getAge()})\n";
    }
    
    echo "\n8. Cleanup:\n";
    $database->setQuery("DROP TABLE users")->execute();
    echo "✓ User table dropped\n";
    
    // Clean up generated file
    if (file_exists(__DIR__ . '/User.php')) {
        unlink(__DIR__ . '/User.php');
        echo "✓ Generated User.php file removed\n";
    }
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    
    // Clean up on error
    try {
        $database->setQuery("DROP TABLE IF EXISTS users")->execute();
        if (file_exists(__DIR__ . '/User.php')) {
            unlink(__DIR__ . '/User.php');
        }
    } catch (Exception $cleanupError) {
        // Ignore cleanup errors
    }
}

echo "\n=== Example Complete ===\n";
