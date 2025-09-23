<?php

require_once '../../vendor/autoload.php';

use WebFiori\Database\ConnectionInfo;
use WebFiori\Database\Database;

echo "=== WebFiori Database Migrations Example ===\n\n";

try {
    // Create connection
    $connection = new ConnectionInfo('mysql', 'root', '123456', 'mysql');
    $database = new Database($connection);

    echo "1. Loading Migration Classes:\n";

    // Include migration classes
    require_once __DIR__.'/CreateUsersTableMigration.php';
    require_once __DIR__.'/AddEmailIndexMigration.php';

    // Create migration instances
    $createUsersMigration = new CreateUsersTableMigration();
    $addEmailIndexMigration = new AddEmailIndexMigration();

    echo "✓ CreateUsersTableMigration loaded\n";
    echo "✓ AddEmailIndexMigration loaded\n\n";

    echo "2. Running Migrations (UP):\n";

    // Execute migrations manually in order
    echo "Running: ".$createUsersMigration->getName()."\n";
    $createUsersMigration->execute($database);
    echo "✓ Users table migration executed\n";

    echo "Running: ".$addEmailIndexMigration->getName()."\n";
    $addEmailIndexMigration->execute($database);
    echo "✓ Email index migration executed\n\n";

    echo "3. Verifying Database Structure:\n";

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

    echo "4. Testing Data Operations:\n";

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

    echo "5. Rolling Back Migrations:\n";

    // Rollback migrations in reverse order
    echo "Rolling back: ".$addEmailIndexMigration->getName()."\n";
    $addEmailIndexMigration->rollback($database);
    echo "✓ Email index migration rolled back\n";

    echo "Rolling back: ".$createUsersMigration->getName()."\n";
    $createUsersMigration->rollback($database);
    echo "✓ Users table migration rolled back\n";

    // Verify rollback
    $result = $database->setQuery("SHOW TABLES LIKE 'users'")->execute();

    if ($result->getRowsCount() == 0) {
        echo "✓ Users table removed\n";
    }
} catch (Exception $e) {
    echo "✗ Error: ".$e->getMessage()."\n";

    // Clean up on error
    try {
        $database->setQuery("DROP TABLE IF EXISTS users")->execute();
    } catch (Exception $cleanupError) {
        // Ignore cleanup errors
    }
}

echo "\n=== Example Complete ===\n";
