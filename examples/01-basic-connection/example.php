<?php

require_once '../../vendor/autoload.php';

use WebFiori\Database\ConnectionInfo;
use WebFiori\Database\Database;

const SEP = "────────────────────────────────────────────────────────────────────\n";

echo "=== WebFiori Database Connection Example ===\n\n";

try {
    // Create connection info
    echo SEP;
    echo "1. Creating Connection Info:\n";
    $connection = new ConnectionInfo('mysql', 'root', '123456', 'mysql');
    echo "   ✓ Connection info created\n";
    echo "   Database Type: ".$connection->getDatabaseType()."\n";
    echo "   Host: ".$connection->getHost()."\n";
    echo "   Database: ".$connection->getDBName()."\n\n";

    // Establish database connection
    echo SEP;
    echo "2. Establishing Connection:\n";
    $database = new Database($connection);
    echo "   ✓ Database connection established\n\n";

    // Test connection with a simple query
    echo SEP;
    echo "3. Testing Connection:\n";
    $result = $database->raw("SELECT VERSION() as version")->execute();

    if ($result) {
        echo "   ✓ Connection test successful\n";
        $rows = $result->getRows();

        if (!empty($rows)) {
            echo "   MySQL Version: ".$rows[0]['version']."\n";
        }
    }
    echo "\n";

    // Additional connection tests
    echo SEP;
    echo "4. Additional Connection Info:\n";

    $result = $database->raw("SELECT DATABASE() as current_db")->execute();
    if ($result && $result->getRowsCount() > 0) {
        echo "   ✓ Current database: ".$result->getRows()[0]['current_db']."\n";
    }

    $result = $database->raw("SHOW STATUS LIKE 'Uptime'")->execute();
    if ($result && $result->getRowsCount() > 0) {
        $uptime = $result->getRows()[0]['Value'];
        echo "   ✓ Server uptime: ".$uptime." seconds\n";
    }

    $result = $database->raw("SELECT CONNECTION_ID() as connection_id")->execute();
    if ($result && $result->getRowsCount() > 0) {
        echo "   ✓ Connection ID: ".$result->getRows()[0]['connection_id']."\n";
    }

    $result = $database->raw("SELECT USER() as user_name")->execute();
    if ($result && $result->getRowsCount() > 0) {
        echo "   ✓ Current User: ".$result->getRows()[0]['user_name']."\n";
    }

} catch (Exception $e) {
    echo "✗ Error: ".$e->getMessage()."\n";
    echo "Note: Make sure MySQL is running and accessible with the provided credentials.\n";
}

echo "\n" . SEP;
echo "=== Example Complete ===\n";
