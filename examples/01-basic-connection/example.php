<?php

require_once '../../vendor/autoload.php';

use WebFiori\Database\ConnectionInfo;
use WebFiori\Database\Database;

echo "=== WebFiori Database Connection Example ===\n\n";

try {
    // Create connection info
    $connection = new ConnectionInfo('mysql', 'root', '123456', 'mysql');
    echo "✓ Connection info created\n";
    echo "  Database Type: ".$connection->getDatabaseType()."\n";
    echo "  Host: ".$connection->getHost()."\n";
    echo "  Database: ".$connection->getDBName()."\n\n";

    // Establish database connection
    $database = new Database($connection);
    echo "✓ Database connection established\n";

    // Test connection with a simple query using raw()
    $result = $database->raw("SELECT VERSION() as version")->execute();

    if ($result) {
        echo "✓ Connection test successful\n";
        $rows = $result->getRows();

        if (!empty($rows)) {
            echo "  MySQL Version: ".$rows[0]['version']."\n";
        }
    }

    // Additional connection tests using raw() with parameters
    echo "\n--- Additional Connection Tests ---\n";
    
    // Test current database
    $result = $database->raw("SELECT DATABASE() as current_db")->execute();
    if ($result && $result->getRowsCount() > 0) {
        echo "✓ Current database: " . $result->getRows()[0]['current_db'] . "\n";
    }
    
    // Test server status
    $result = $database->raw("SHOW STATUS LIKE 'Uptime'")->execute();
    if ($result && $result->getRowsCount() > 0) {
        $uptime = $result->getRows()[0]['Value'];
        echo "✓ Server uptime: " . $uptime . " seconds\n";
    }
    
    // Test connection info
    $result = $database->raw("SELECT CONNECTION_ID() as connection_id")->execute();
    if ($result && $result->getRowsCount() > 0) {
        echo "✓ Connection ID: " . $result->getRows()[0]['connection_id'] . "\n";
    }
    
    $result = $database->raw("SELECT USER() as user_name")->execute();
    if ($result && $result->getRowsCount() > 0) {
        echo "✓ Current User: " . $result->getRows()[0]['user_name'] . "\n";
    }

} catch (Exception $e) {
    echo "✗ Error: ".$e->getMessage()."\n";
    echo "Note: Make sure MySQL is running and accessible with the provided credentials.\n";
}

echo "\n=== Example Complete ===\n";
