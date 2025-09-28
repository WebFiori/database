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
    require_once __DIR__ . '/CreateUsersTableMigration.php';
    require_once __DIR__ . '/AddEmailIndexMigration.php';
    
    echo "✓ Migration classes loaded\n";
    
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
        echo "  - " . $change->getName() . "\n";
    }
    echo "\n";
    
    echo "4. Running Migrations:\n";
    
    // Force apply all migrations
    $changes = $runner->getChanges();
    $appliedChanges = [];
    
    foreach ($changes as $change) {
        if (!$runner->isApplied($change->getName())) {
            $change->execute($database);
            $appliedChanges[] = $change;
            echo "  ✓ Applied: " . $change->getName() . "\n";
        }
    }
    
    if (empty($appliedChanges)) {
        echo "No migrations to apply (all up to date)\n";
    }
    echo "\n";
    
    echo "5. Verifying Database Structure:\n";
    
    // Check if table exists
    $result = $database->setQuery("SHOW TABLES LIKE 'users'")->execute();
    if ($result->getRowsCount() > 0) {
        echo "✓ Users table created\n";
    }
    
    // Check table structure
    $result = $database->setQuery("DESCRIBE users")->execute();
    echo "Users table columns:\n";
    foreach ($result as $column) {
        echo "  - {$column['Field']} ({$column['Type']})\n";
    }
    
    // Check indexes
    $result = $database->setQuery("SHOW INDEX FROM users WHERE Key_name = 'idx_users_email'")->execute();
    if ($result->getRowsCount() > 0) {
        echo "✓ Email index created\n";
    }
    echo "\n";
    
    echo "6. Testing Data Operations:\n";
    
    // Insert test data
    $database->table('users')->insert([
        'username' => 'ahmad_hassan',
        'email' => 'ahmad@example.com',
        'password_hash' => password_hash('password123', PASSWORD_DEFAULT)
    ])->execute();
    
    $database->table('users')->insert([
        'username' => 'fatima_ali',
        'email' => 'fatima@example.com',
        'password_hash' => password_hash('password456', PASSWORD_DEFAULT)
    ])->execute();
    
    echo "✓ Test users inserted\n";
    
    // Query data
    $result = $database->table('users')->select(['username', 'email', 'created_at'])->execute();
    echo "Inserted users:\n";
    foreach ($result as $user) {
        echo "  - {$user['username']} ({$user['email']}) - {$user['created_at']}\n";
    }
    echo "\n";
    
    echo "7. Checking Migration Status:\n";
    
    // Check which migrations are applied
    echo "Migration status:\n";
    foreach ($changes as $change) {
        $status = $runner->isApplied($change->getName()) ? "✓ Applied" : "✗ Pending";
        echo "  {$change->getName()}: $status\n";
    }
    echo "\n";
    
    echo "8. Rolling Back Migrations:\n";
    
    // Rollback all migrations
    $rolledBackChanges = $runner->rollbackUpTo(null);
    
    if (!empty($rolledBackChanges)) {
        echo "Rolled back migrations:\n";
        foreach ($rolledBackChanges as $change) {
            echo "  ✓ " . $change->getName() . "\n";
        }
    } else {
        echo "No migrations to rollback\n";
    }
    
    // Verify rollback
    $result = $database->setQuery("SHOW TABLES LIKE 'users'")->execute();
    if ($result->getRowsCount() == 0) {
        echo "✓ Users table removed\n";
    }
    
    echo "\n9. Cleanup:\n";
    $runner->dropSchemaTable();
    echo "✓ Schema tracking table dropped\n";
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    
    // Clean up on error
    try {
        $database->setQuery("DROP TABLE IF EXISTS users")->execute();
        $database->setQuery("DROP TABLE IF EXISTS schema_changes")->execute();
    } catch (Exception $cleanupError) {
        // Ignore cleanup errors
    }
}

echo "\n=== Example Complete ===\n";
