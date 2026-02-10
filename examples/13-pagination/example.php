<?php

require_once '../../vendor/autoload.php';
require_once __DIR__.'/User.php';
require_once __DIR__.'/UserRepository.php';

use WebFiori\Database\ColOption;
use WebFiori\Database\ConnectionInfo;
use WebFiori\Database\Database;
use WebFiori\Database\DataType;

const SEP = "────────────────────────────────────────────────────────────────────\n";

echo "=== WebFiori Database Pagination Example ===\n\n";

try {
    $connection = new ConnectionInfo('mysql', 'root', '123456', 'testing_db');
    $database = new Database($connection);

    echo SEP;
    echo "1. Setting up Test Data:\n";

    $database->createBlueprint('users')->addColumns([
        'id' => [ColOption::TYPE => DataType::INT, ColOption::PRIMARY => true, ColOption::AUTO_INCREMENT => true],
        'name' => [ColOption::TYPE => DataType::VARCHAR, ColOption::SIZE => 100],
        'email' => [ColOption::TYPE => DataType::VARCHAR, ColOption::SIZE => 150],
        'age' => [ColOption::TYPE => DataType::INT]
    ]);

    $database->table('users')->drop(true)->execute();
    $database->table('users')->createTable()->execute();

    $names = ['Ahmed', 'Fatima', 'Omar', 'Layla', 'Hassan', 'Sara', 'Yusuf', 'Maryam', 'Ali', 'Noor',
        'Khalid', 'Aisha', 'Ibrahim', 'Zahra', 'Mahmoud', 'Hana', 'Tariq', 'Salma', 'Rami', 'Dina',
        'Faisal', 'Lina', 'Samir', 'Rania', 'Walid'];

    $values = [];

    foreach ($names as $i => $name) {
        $values[] = [$name, strtolower($name).'@example.com', 20 + ($i % 30)];
    }

    $database->table('users')->insert([
        'cols' => ['name', 'email', 'age'],
        'values' => $values
    ])->execute();
    echo "   ✓ Created 25 test users\n\n";

    $repo = new UserRepository($database);

    echo SEP;
    echo "2. Offset-Based Pagination:\n";
    echo "   (Traditional page numbers)\n\n";

    for ($page = 1; $page <= 3; $page++) {
        $result = $repo->paginate($page, 5);
        echo "   Page $page of {$result->getTotalPages()}:\n";

        foreach ($result->getItems() as $user) {
            echo "   - {$user->name} ({$user->email})\n";
        }
        echo "   Has next: ".($result->hasNextPage() ? 'Yes' : 'No')."\n\n";
    }

    echo SEP;
    echo "3. Cursor-Based Pagination:\n";
    echo "   (Better for large datasets, infinite scroll)\n\n";

    $cursor = null;
    $pageNum = 1;

    while ($pageNum <= 3) {
        $result = $repo->paginateByCursor($cursor, 5, 'id', 'ASC');
        echo "   Cursor Page $pageNum:\n";

        foreach ($result->getItems() as $user) {
            echo "   - ID {$user->id}: {$user->name}\n";
        }
        echo "   Has more: ".($result->hasMore() ? 'Yes' : 'No')."\n";

        if (!$result->hasMore()) {
            break;
        }

        $cursor = $result->getNextCursor();
        echo "   Next cursor: $cursor\n\n";
        $pageNum++;
    }
    echo "\n";

    echo SEP;
    echo "4. Pagination with Ordering:\n";
    $result = $repo->paginate(1, 5, ['age' => 'DESC']);
    echo "   Top 5 oldest users:\n";

    foreach ($result->getItems() as $user) {
        echo "   - {$user->name} (Age: {$user->age})\n";
    }
    echo "\n";

    echo SEP;
    echo "5. Cleanup:\n";
    $database->table('users')->drop()->execute();
    echo "   ✓ Table dropped\n";
} catch (Exception $e) {
    echo "✗ Error: ".$e->getMessage()."\n";
    try {
        $database->table('users')->drop(true)->execute();
    } catch (Exception $cleanupError) {
    }
}

echo "\n".SEP;
echo "=== Example Complete ===\n";
