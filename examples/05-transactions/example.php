<?php

require_once '../../vendor/autoload.php';

use WebFiori\Database\ConnectionInfo;
use WebFiori\Database\Database;
use WebFiori\Database\DatabaseException;

echo "=== WebFiori Database Transactions Example ===\n\n";

try {
    // Create connection
    $connection = new ConnectionInfo('mysql', 'root', '123456', 'mysql');
    $database = new Database($connection);

    echo "1. Setting up Test Tables:\n";

    // Create test tables using raw()
    $database->raw("
        CREATE TABLE IF NOT EXISTS accounts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            balance DECIMAL(10,2) NOT NULL DEFAULT 0.00
        )
    ")->execute();

    $database->raw("
        CREATE TABLE IF NOT EXISTS transactions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            from_account INT,
            to_account INT,
            amount DECIMAL(10,2) NOT NULL,
            description VARCHAR(255),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ")->execute();

    echo "✓ Test tables created\n\n";

    // Clear existing data using raw()
    $database->raw("DELETE FROM transactions")->execute();
    $database->raw("DELETE FROM accounts")->execute();

    // Insert initial account data using raw() with parameters
    $database->raw("INSERT INTO accounts (name, balance) VALUES (?, ?)", [
        'Amira', 1000.00
    ])->execute();

    $database->raw("INSERT INTO accounts (name, balance) VALUES (?, ?)", [
        'Yusuf', 500.00
    ])->execute();

    echo "2. Initial Account Balances:\n";
    $result = $database->raw("SELECT * FROM accounts")->execute();

    foreach ($result as $account) {
        echo "  {$account['name']}: $".number_format($account['balance'], 2)."\n";
    }
    echo "\n";

    echo "3. Successful Transaction Example:\n";

    // Successful money transfer transaction
    $transferAmount = 200.00;
    $fromAccountId = 1; // Amira
    $toAccountId = 2;   // Yusuf

    $database->transaction(function (Database $db) use ($transferAmount, $fromAccountId, $toAccountId)
    {
        // Check if sender has sufficient balance using raw() with parameters
        $senderResult = $db->raw("SELECT balance FROM accounts WHERE id = ?", [$fromAccountId])->execute();
        $senderBalance = $senderResult->getRows()[0]['balance'];

        if ($senderBalance < $transferAmount) {
            throw new DatabaseException("Insufficient funds");
        }

        // Deduct from sender using raw() with parameters
        $db->raw("UPDATE accounts SET balance = ? WHERE id = ?", [
            $senderBalance - $transferAmount, $fromAccountId
        ])->execute();

        // Get receiver balance using raw() with parameters
        $receiverResult = $db->raw("SELECT balance FROM accounts WHERE id = ?", [$toAccountId])->execute();
        $receiverBalance = $receiverResult->getRows()[0]['balance'];

        // Add to receiver using raw() with parameters
        $db->raw("UPDATE accounts SET balance = ? WHERE id = ?", [
            $receiverBalance + $transferAmount, $toAccountId
        ])->execute();

        // Record the transaction using raw() with parameters
        $db->raw("INSERT INTO transactions (from_account, to_account, amount, description) VALUES (?, ?, ?, ?)", [
            $fromAccountId, $toAccountId, $transferAmount, 'Money transfer'
        ])->execute();

        echo "✓ Transaction completed successfully\n";
    });

    echo "Account balances after successful transfer:\n";
    $result = $database->raw("SELECT * FROM accounts")->execute();

    foreach ($result as $account) {
        echo "  {$account['name']}: $".number_format($account['balance'], 2)."\n";
    }
    echo "\n";

    echo "4. Failed Transaction Example (Insufficient Funds):\n";

    // Attempt to transfer more money than available
    $largeTransferAmount = 2000.00;

    try {
        $database->transaction(function (Database $db) use ($largeTransferAmount, $fromAccountId, $toAccountId)
        {
            // Check if sender has sufficient balance using raw() with parameters
            $senderResult = $db->raw("SELECT balance FROM accounts WHERE id = ?", [$fromAccountId])->execute();
            $senderBalance = $senderResult->getRows()[0]['balance'];

            if ($senderBalance < $largeTransferAmount) {
                throw new DatabaseException("Insufficient funds for transfer of $".number_format($largeTransferAmount, 2));
            }

            // This code won't be reached due to insufficient funds
            $db->raw("UPDATE accounts SET balance = ? WHERE id = ?", [
                $senderBalance - $largeTransferAmount, $fromAccountId
            ])->execute();
        });
    } catch (DatabaseException $e) {
        echo "✗ Transaction failed: ".$e->getMessage()."\n";
        echo "✓ Transaction was rolled back automatically\n";
    }

    echo "Account balances after failed transaction (should be unchanged):\n";
    $result = $database->raw("SELECT * FROM accounts")->execute();

    foreach ($result as $account) {
        echo "  {$account['name']}: $".number_format($account['balance'], 2)."\n";
    }
    echo "\n";

    echo "5. Transaction History:\n";
    $result = $database->raw("
        SELECT t.*, 
               a1.name as from_name, 
               a2.name as to_name 
        FROM transactions t
        LEFT JOIN accounts a1 ON t.from_account = a1.id
        LEFT JOIN accounts a2 ON t.to_account = a2.id
        ORDER BY t.created_at
    ")->execute();

    if ($result->getRowsCount() > 0) {
        foreach ($result as $transaction) {
            echo "  Transfer: {$transaction['from_name']} → {$transaction['to_name']}\n";
            echo "    Amount: $".number_format($transaction['amount'], 2)."\n";
            echo "    Date: {$transaction['created_at']}\n";
            echo "    Description: {$transaction['description']}\n\n";
        }
    } else {
        echo "  No transactions recorded\n\n";
    }

    echo "6. Multi-Result Transaction Analysis:\n";
    
    // Create a stored procedure for transaction analysis
    $database->raw("DROP PROCEDURE IF EXISTS TransactionAnalysis")->execute();
    $database->raw("
        CREATE PROCEDURE TransactionAnalysis()
        BEGIN
            -- Account summary
            SELECT 'Account Summary' as report_type, name, balance FROM accounts ORDER BY balance DESC;
            
            -- Transaction summary
            SELECT 'Transaction Summary' as report_type, 
                   COUNT(*) as total_transactions,
                   SUM(amount) as total_amount,
                   AVG(amount) as avg_amount
            FROM transactions;
            
            -- Recent transactions
            SELECT 'Recent Transactions' as report_type,
                   t.amount,
                   a1.name as from_name,
                   a2.name as to_name,
                   t.created_at
            FROM transactions t
            LEFT JOIN accounts a1 ON t.from_account = a1.id
            LEFT JOIN accounts a2 ON t.to_account = a2.id
            ORDER BY t.created_at DESC
            LIMIT 5;
        END
    ")->execute();
    
    $analysisResult = $database->raw("CALL TransactionAnalysis()")->execute();
    
    if (method_exists($analysisResult, 'count') && $analysisResult->count() > 1) {
        echo "✓ Multi-result transaction analysis completed!\n";
        
        for ($i = 0; $i < $analysisResult->count(); $i++) {
            $rs = $analysisResult->getResultSet($i);
            if ($rs->getRowsCount() > 0) {
                $firstRow = $rs->getRows()[0];
                
                if (isset($firstRow['report_type'])) {
                    echo "\n--- {$firstRow['report_type']} ---\n";
                    
                    foreach ($rs as $row) {
                        if ($row['report_type'] === 'Account Summary') {
                            echo "  {$row['name']}: $" . number_format($row['balance'], 2) . "\n";
                        } elseif ($row['report_type'] === 'Transaction Summary') {
                            echo "  Total Transactions: {$row['total_transactions']}\n";
                            echo "  Total Amount: $" . number_format($row['total_amount'], 2) . "\n";
                            echo "  Average Amount: $" . number_format($row['avg_amount'], 2) . "\n";
                        } elseif ($row['report_type'] === 'Recent Transactions') {
                            echo "  {$row['from_name']} → {$row['to_name']}: $" . number_format($row['amount'], 2) . " ({$row['created_at']})\n";
                        }
                    }
                }
            }
        }
    }

    echo "\n7. Cleanup:\n";
    $database->raw("DROP PROCEDURE IF EXISTS TransactionAnalysis")->execute();
    $database->raw("DROP TABLE transactions")->execute();
    $database->raw("DROP TABLE accounts")->execute();
    echo "✓ Test tables and procedures dropped\n";

} catch (Exception $e) {
    echo "✗ Error: ".$e->getMessage()."\n";

    // Clean up on error
    try {
        $database->raw("DROP PROCEDURE IF EXISTS TransactionAnalysis")->execute();
        $database->raw("DROP TABLE IF EXISTS transactions")->execute();
        $database->raw("DROP TABLE IF EXISTS accounts")->execute();
    } catch (Exception $cleanupError) {
        // Ignore cleanup errors
    }
}

echo "\n=== Example Complete ===\n";
