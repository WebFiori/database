<?php

require_once '../../vendor/autoload.php';

use WebFiori\Database\ConnectionInfo;
use WebFiori\Database\Database;
use WebFiori\Database\Schema\SchemaRunner;

echo "=== WebFiori Database Migrations Example ===\n\n";

try {
    // Create connection
    $connection = new ConnectionInfo('mysql', 'root', '123456', 'mysql');
    $database = new Database($connection);

    echo "1. Loading Migration Classes:\n";

    // Include migration classes
    require_once __DIR__.'/CreateUsersTableMigration.php';
    require_once __DIR__.'/AddEmailIndexMigration.php';

    echo "✓ Migration classes loaded\n\n";

    echo "2. Setting up Schema Runner:\n";

    // Create schema runner
    $runner = new SchemaRunner($connection);

    // Register migration classes
    $runner->register('CreateUsersTableMigration');
    $runner->register('AddEmailIndexMigration');

    echo "✓ Schema runner created\n";
    echo "✓ Migration classes registered\n";

    // Create schema tracking table
    $runner->createSchemaTable();
    echo "✓ Schema tracking table created\n\n";

    echo "3. Checking Available Migrations:\n";

    $changes = $runner->getChanges();
    echo "Registered migrations:\n";
    foreach ($changes as $change) {
        echo "  - ".$change->getName()."\n";
    }
    echo "\n";

    echo "4. Running Migrations (using apply()):\n";

    // Apply all pending migrations
    $result = $runner->apply();

    if ($result->count() > 0) {
        echo "Applied migrations:\n";
        foreach ($result->getApplied() as $change) {
            echo "  ✓ ".$change->getName()."\n";
        }
    } else {
        echo "No migrations to apply (all up to date)\n";
    }

    if (!empty($result->getFailed())) {
        echo "Failed migrations:\n";
        foreach ($result->getFailed() as $failure) {
            echo "  ✗ ".$failure['change']->getName().": ".$failure['error']->getMessage()."\n";
        }
    }
    echo "\n";

    echo "5. Verifying Database Structure:\n";

    // Check if table exists
    $tableResult = $database->raw("SHOW TABLES LIKE 'users'")->execute();
    if ($tableResult->getRowsCount() > 0) {
        echo "✓ Users table created\n";
    }

    // Check table structure
    $descResult = $database->raw("DESCRIBE users")->execute();
    echo "Users table columns:\n";
    foreach ($descResult as $column) {
        echo "  - {$column['Field']} ({$column['Type']})\n";
    }

    // Check indexes
    $indexResult = $database->raw("SHOW INDEX FROM users WHERE Key_name = 'idx_users_email'")->execute();
    if ($indexResult->getRowsCount() > 0) {
        echo "✓ Email index created\n";
    }
    echo "\n";

    echo "6. Testing Data Operations:\n";

    // Insert test data
    $database->table('users')->insert([
        'username' => 'ahmad_hassan',
        'email' => 'ahmad@example.com',
        'password-hash' => password_hash('password123', PASSWORD_DEFAULT)
    ])->execute();

    $database->table('users')->insert([
        'username' => 'fatima_ali',
        'email' => 'fatima@example.com',
        'password-hash' => password_hash('password456', PASSWORD_DEFAULT)
    ])->execute();

    echo "✓ Test users inserted\n";

    // Query data
    $selectResult = $database->table('users')->select(['username', 'email', 'created-at'])->execute();
    echo "Inserted users:\n";
    foreach ($selectResult as $user) {
        echo "  - {$user['username']} ({$user['email']}) - {$user['created_at']}\n";
    }
    echo "\n";

    echo "7. Checking Migration Status:\n";
    echo "Migration status:\n";
    foreach ($changes as $change) {
        $status = $runner->isApplied($change->getName()) ? "✓ Applied" : "✗ Pending";
        echo "  {$change->getName()}: $status\n";
    }
    echo "\n";

    echo "8. Rolling Back Migrations:\n";

    // Rollback all migrations
    $rolledBack = $runner->rollbackUpTo(null);

    if (!empty($rolledBack)) {
        echo "Rolled back migrations:\n";
        foreach ($rolledBack as $change) {
            echo "  ✓ ".$change->getName()."\n";
        }
    } else {
        echo "No migrations to rollback\n";
    }

    // Verify rollback
    $verifyResult = $database->raw("SHOW TABLES LIKE 'users'")->execute();
    if ($verifyResult->getRowsCount() == 0) {
        echo "✓ Users table removed\n";
    }

    echo "\n9. Cleanup:\n";
    $runner->dropSchemaTable();
    echo "✓ Schema tracking table dropped\n";
} catch (Exception $e) {
    echo "✗ Error: ".$e->getMessage()."\n";

    // Clean up on error
    try {
        $database->raw("DROP TABLE IF EXISTS users")->execute();
        $database->raw("DROP TABLE IF EXISTS schema_changes")->execute();
    } catch (Exception $cleanupError) {
        // Ignore cleanup errors
    }
}

echo "\n=== Example Complete ===\n";
