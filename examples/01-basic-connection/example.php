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

    // Test connection with a simple query
    $result = $database->setQuery("SELECT VERSION() as version")->execute();

    if ($result) {
        echo "✓ Connection test successful\n";
        $rows = $result->getRows();

        if (!empty($rows)) {
            echo "  MySQL Version: ".$rows[0]['version']."\n";
        }
    }
} catch (Exception $e) {
    echo "✗ Error: ".$e->getMessage()."\n";
    echo "Note: Make sure MySQL is running and accessible with the provided credentials.\n";
}

echo "\n=== Example Complete ===\n";
