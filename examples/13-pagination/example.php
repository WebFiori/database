<?php

require_once '../../vendor/autoload.php';
require_once __DIR__.'/User.php';
require_once __DIR__.'/UserRepository.php';

use WebFiori\Database\ColOption;
use WebFiori\Database\ConnectionInfo;
use WebFiori\Database\Database;
use WebFiori\Database\DataType;

echo "=== WebFiori Database Pagination Example ===\n\n";

try {
    $connection = new ConnectionInfo('mysql', 'root', '123456', 'mysql');
    $database = new Database($connection);

    echo "1. Setting up Test Data:\n";

    $database->raw("DROP TABLE IF EXISTS users")->execute();
    $database->createBlueprint('users')->addColumns([
        'id' => [ColOption::TYPE => DataType::INT, ColOption::PRIMARY => true, ColOption::AUTO_INCREMENT => true],
        'name' => [ColOption::TYPE => DataType::VARCHAR, ColOption::SIZE => 100],
        'email' => [ColOption::TYPE => DataType::VARCHAR, ColOption::SIZE => 150],
        'age' => [ColOption::TYPE => DataType::INT]
    ]);
    $database->table('users')->createTable();
    $database->execute();

    $names = ['Ahmed', 'Fatima', 'Omar', 'Layla', 'Hassan', 'Sara', 'Yusuf', 'Maryam', 'Ali', 'Noor',
              'Khalid', 'Aisha', 'Ibrahim', 'Zahra', 'Mahmoud', 'Hana', 'Tariq', 'Salma', 'Rami', 'Dina',
              'Faisal', 'Lina', 'Samir', 'Rania', 'Walid'];

    foreach ($names as $i => $name) {
        $database->table('users')->insert([
            'name' => $name,
            'email' => strtolower($name).'@example.com',
            'age' => 20 + ($i % 30)
        ])->execute();
    }
    echo "✓ Created 25 test users\n\n";

    $repo = new UserRepository($database);

    echo "2. Offset-Based Pagination:\n";
    echo "   (Traditional page numbers)\n\n";

    for ($page = 1; $page <= 3; $page++) {
        $result = $repo->paginate($page, 5);
        echo "Page $page of {$result->getTotalPages()}:\n";
        foreach ($result->getItems() as $user) {
            echo "  - {$user->name} ({$user->email})\n";
        }
        echo "  Has next: ".($result->hasNextPage() ? 'Yes' : 'No')."\n\n";
    }

    echo "3. Cursor-Based Pagination:\n";
    echo "   (Better for large datasets, infinite scroll)\n\n";

    $cursor = null; // null = start from beginning (first page)
    $pageNum = 1;

    while ($pageNum <= 3) {
        $result = $repo->paginateByCursor($cursor, 5, 'id', 'ASC');
        echo "Cursor Page $pageNum:\n";
        foreach ($result->getItems() as $user) {
            echo "  - ID {$user->id}: {$user->name}\n";
        }
        echo "  Has more: ".($result->hasMore() ? 'Yes' : 'No')."\n";

        if (!$result->hasMore()) break;

        // Next cursor is base64-encoded ID of last item, used to fetch next page
        $cursor = $result->getNextCursor();
        echo "  Next cursor: $cursor\n\n";
        $pageNum++;
    }

    echo "\n4. Pagination with Ordering:\n";
    $result = $repo->paginate(1, 5, ['age' => 'DESC']);
    echo "Top 5 oldest users:\n";
    foreach ($result->getItems() as $user) {
        echo "  - {$user->name} (Age: {$user->age})\n";
    }

    echo "\n5. Cleanup:\n";
    $database->raw("DROP TABLE users")->execute();
    echo "✓ Table dropped\n";
} catch (Exception $e) {
    echo "✗ Error: ".$e->getMessage()."\n";
    try { $database->raw("DROP TABLE IF EXISTS users")->execute(); } catch (Exception $e) {}
}

echo "\n=== Example Complete ===\n";
