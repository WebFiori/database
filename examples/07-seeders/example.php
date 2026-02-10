<?php

require_once '../../vendor/autoload.php';

use WebFiori\Database\ColOption;
use WebFiori\Database\ConnectionInfo;
use WebFiori\Database\Database;
use WebFiori\Database\DataType;
use WebFiori\Database\Schema\SchemaRunner;

const SEP = "────────────────────────────────────────────────────────────────────\n";

echo "=== WebFiori Database Seeders Example ===\n\n";

try {
    $connection = new ConnectionInfo('mysql', 'root', '123456', 'testing_db');
    $database = new Database($connection);

    echo SEP;
    echo "1. Creating Tables:\n";

    $database->createBlueprint('users')->addColumns([
        'id' => [ColOption::TYPE => DataType::INT, ColOption::PRIMARY => true, ColOption::AUTO_INCREMENT => true],
        'username' => [ColOption::TYPE => DataType::VARCHAR, ColOption::SIZE => 50],
        'email' => [ColOption::TYPE => DataType::VARCHAR, ColOption::SIZE => 150],
        'full-name' => [ColOption::TYPE => DataType::VARCHAR, ColOption::SIZE => 100],
        'role' => [ColOption::TYPE => DataType::VARCHAR, ColOption::SIZE => 20, ColOption::DEFAULT => 'user'],
        'is-active' => [ColOption::TYPE => DataType::BOOL, ColOption::DEFAULT => true]
    ]);

    $database->createBlueprint('categories')->addColumns([
        'id' => [ColOption::TYPE => DataType::INT, ColOption::PRIMARY => true, ColOption::AUTO_INCREMENT => true],
        'name' => [ColOption::TYPE => DataType::VARCHAR, ColOption::SIZE => 100],
        'description' => [ColOption::TYPE => DataType::TEXT],
        'slug' => [ColOption::TYPE => DataType::VARCHAR, ColOption::SIZE => 100]
    ]);

    $database->table('categories')->drop(true)->execute();
    $database->table('users')->drop(true)->execute();
    $database->createTables();
    echo "   ✓ Tables created\n\n";

    echo SEP;
    echo "2. Setting up Schema Runner:\n";

    $runner = new SchemaRunner($connection);
    $runner->discoverFromPath(__DIR__, '');
    $runner->createSchemaTable();
    echo "   ✓ Schema runner created\n";
    echo "   ✓ Seeder classes discovered\n\n";

    echo SEP;
    echo "3. Available Seeders:\n";

    $changes = $runner->getChanges();

    foreach ($changes as $change) {
        echo "   - ".$change->getName()."\n";
    }
    echo "\n";

    echo SEP;
    echo "4. Running Seeders:\n";

    $result = $runner->apply();

    if ($result->count() > 0) {
        foreach ($result->getApplied() as $change) {
            echo "   ✓ ".$change->getName()."\n";
        }
    }
    echo "\n";

    echo SEP;
    echo "5. Verifying Seeded Data:\n";

    $usersResult = $database->table('users')->select()->execute();
    echo "   Users ({$usersResult->getRowsCount()} records):\n";

    foreach ($usersResult as $user) {
        echo "   - {$user['full_name']} (@{$user['username']}) - {$user['role']}\n";
    }
    echo "\n";

    $categoriesResult = $database->table('categories')->select()->execute();
    echo "   Categories ({$categoriesResult->getRowsCount()} records):\n";

    foreach ($categoriesResult as $category) {
        echo "   - {$category['name']} ({$category['slug']})\n";
    }
    echo "\n";

    echo SEP;
    echo "6. Cleanup:\n";
    $runner->dropSchemaTable();
    $database->table('categories')->drop()->execute();
    $database->table('users')->drop()->execute();
    echo "   ✓ Tables dropped\n";
} catch (Exception $e) {
    echo "✗ Error: ".$e->getMessage()."\n";
    try {
        $database->table('categories')->drop(true)->execute();
        $database->table('users')->drop(true)->execute();
        $database->raw("DROP TABLE IF EXISTS schema_changes")->execute();
    } catch (Exception $cleanupError) {
    }
}

echo "\n".SEP;
echo "=== Example Complete ===\n";
