<?php

require_once '../../vendor/autoload.php';
require_once __DIR__.'/Domain/User.php';
require_once __DIR__.'/Domain/UserRepositoryInterface.php';
require_once __DIR__.'/Infrastructure/Repository/MySQLUserRepository.php';

use Domain\User;
use Infrastructure\Repository\MySQLUserRepository;
use WebFiori\Database\ColOption;
use WebFiori\Database\ConnectionInfo;
use WebFiori\Database\Database;
use WebFiori\Database\DataType;

echo "=== WebFiori Database Clean Architecture Example ===\n\n";

echo "This example demonstrates separation of concerns:\n";
echo "  - Domain: Pure entities and interfaces (no framework dependencies)\n";
echo "  - Infrastructure: Database implementation (WebFiori Database)\n\n";

try {
    // Infrastructure setup
    $connection = new ConnectionInfo('mysql', 'root', '123456', 'mysql');
    $database = new Database($connection);

    echo "1. Setting up Database (Infrastructure):\n";

    $database->raw("DROP TABLE IF EXISTS users")->execute();
    $database->createBlueprint('users')->addColumns([
        'id' => [ColOption::TYPE => DataType::INT, ColOption::PRIMARY => true, ColOption::AUTO_INCREMENT => true],
        'name' => [ColOption::TYPE => DataType::VARCHAR, ColOption::SIZE => 100],
        'email' => [ColOption::TYPE => DataType::VARCHAR, ColOption::SIZE => 150],
        'age' => [ColOption::TYPE => DataType::INT]
    ]);
    $database->table('users')->createTable();
    $database->execute();
    echo "✓ Database table created\n\n";

    echo "2. Creating Repository (Infrastructure implements Domain interface):\n";
    $userRepo = new MySQLUserRepository($database);
    echo "✓ MySQLUserRepository created\n\n";

    echo "3. Working with Domain Entities:\n";

    // Create domain entities (pure PHP, no DB knowledge)
    $users = [
        new User(null, 'Ahmed Ali', 'ahmed@example.com', 28),
        new User(null, 'Sara Hassan', 'sara@example.com', 32),
        new User(null, 'Omar Khalil', 'omar@example.com', 25)
    ];

    foreach ($users as $user) {
        $userRepo->save($user);
        echo "  ✓ Saved: {$user->name}\n";
    }
    echo "\n";

    echo "4. Querying through Repository:\n";
    $allUsers = $userRepo->findAll();
    echo "All users:\n";
    foreach ($allUsers as $user) {
        echo "  - {$user->name} ({$user->email}) - Age: {$user->age}\n";
    }
    echo "\n";

    echo "5. Finding by ID:\n";
    $user = $userRepo->findById(1);
    if ($user) {
        echo "  Found: {$user->name}\n\n";
    }

    echo "6. Benefits of Clean Architecture:\n";
    echo "  ✓ Domain entities are framework-agnostic\n";
    echo "  ✓ Repository interface defines contract\n";
    echo "  ✓ Easy to swap implementations (MySQL, PostgreSQL, etc.)\n";
    echo "  ✓ Domain logic is testable without database\n\n";

    echo "7. Cleanup:\n";
    $database->raw("DROP TABLE users")->execute();
    echo "✓ Table dropped\n";
} catch (Exception $e) {
    echo "✗ Error: ".$e->getMessage()."\n";
    try { $database->raw("DROP TABLE IF EXISTS users")->execute(); } catch (Exception $e) {}
}

echo "\n=== Example Complete ===\n";
