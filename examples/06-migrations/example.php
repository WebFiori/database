<?php

require_once '../../vendor/autoload.php';

use WebFiori\Database\ConnectionInfo;
use WebFiori\Database\Database;
use WebFiori\Database\Schema\SchemaRunner;

const SEP = "────────────────────────────────────────────────────────────────────\n";

echo "=== WebFiori Database Migrations Example ===\n\n";

try {
    $connection = new ConnectionInfo('mysql', 'root', '123456', 'testing_db');
    $database = new Database($connection);

    echo SEP;
    echo "1. Setting up Schema Runner:\n";

    $runner = new SchemaRunner($connection);
    $runner->discoverFromPath(__DIR__, '');
    $runner->createSchemaTable();
    echo "   ✓ Schema runner created\n";
    echo "   ✓ Migration classes discovered\n\n";

    echo SEP;
    echo "2. Available Migrations:\n";

    $changes = $runner->getChanges();

    foreach ($changes as $change) {
        echo "   - ".$change->getName()."\n";
    }
    echo "\n";

    echo SEP;
    echo "3. Running Migrations:\n";

    $result = $runner->apply();

    if ($result->count() > 0) {
        foreach ($result->getApplied() as $change) {
            echo "   ✓ ".$change->getName()."\n";
        }
    } else {
        echo "   No migrations to apply\n";
    }
    echo "\n";

    echo SEP;
    echo "4. Verifying Structure:\n";

    $descResult = $database->raw("DESCRIBE users")->execute();
    echo "   Users table columns:\n";

    foreach ($descResult as $column) {
        echo "   - {$column['Field']} ({$column['Type']})\n";
    }
    echo "\n";

    echo SEP;
    echo "5. Testing Data Operations:\n";

    $database->table('users')->insert([
        'cols' => ['username', 'email', 'password-hash'],
        'values' => [
            ['ahmad_hassan', 'ahmad@example.com', password_hash('password123', PASSWORD_DEFAULT)],
            ['fatima_ali', 'fatima@example.com', password_hash('password456', PASSWORD_DEFAULT)]
        ]
    ])->execute();
    echo "   ✓ Test users inserted\n";

    $selectResult = $database->table('users')->select(['username', 'email'])->execute();
    echo "   Users:\n";

    foreach ($selectResult as $user) {
        echo "   - {$user['username']} ({$user['email']})\n";
    }
    echo "\n";

    echo SEP;
    echo "6. Rolling Back Migrations:\n";

    $rolledBack = $runner->rollbackUpTo(null);

    if (!empty($rolledBack)) {
        foreach ($rolledBack as $change) {
            echo "   ✓ Rolled back: ".$change->getName()."\n";
        }
    }
    echo "\n";

    echo SEP;
    echo "7. Cleanup:\n";
    $runner->dropSchemaTable();
    echo "   ✓ Schema tracking table dropped\n";
} catch (Exception $e) {
    echo "✗ Error: ".$e->getMessage()."\n";
    try {
        $database->table('users')->drop(true)->execute();
        $database->raw("DROP TABLE IF EXISTS schema_changes")->execute();
    } catch (Exception $cleanupError) {
    }
}

echo "\n".SEP;
echo "=== Example Complete ===\n";
