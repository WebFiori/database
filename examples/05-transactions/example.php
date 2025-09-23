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

    // Create test tables
    $database->setQuery("
        CREATE TABLE IF NOT EXISTS accounts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            balance DECIMAL(10,2) NOT NULL DEFAULT 0.00
        )
    ")->execute();

    $database->setQuery("
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

    // Clear existing data
    $database->setQuery("DELETE FROM transactions")->execute();
    $database->setQuery("DELETE FROM accounts")->execute();

    // Insert initial account data
    $database->table('accounts')->insert([
        'name' => 'Amira',
        'balance' => 1000.00
    ])->execute();

    $database->table('accounts')->insert([
        'name' => 'Yusuf',
        'balance' => 500.00
    ])->execute();

    echo "2. Initial Account Balances:\n";
    $result = $database->table('accounts')->select()->execute();

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
        // Check if sender has sufficient balance
        $senderResult = $db->table('accounts')
                          ->select(['balance'])
                          ->where('id', $fromAccountId)
                          ->execute();

        $senderBalance = $senderResult->getRows()[0]['balance'];

        if ($senderBalance < $transferAmount) {
            throw new DatabaseException("Insufficient funds");
        }

        // Deduct from sender
        $db->table('accounts')
           ->update(['balance' => $senderBalance - $transferAmount])
           ->where('id', $fromAccountId)
           ->execute();

        // Add to receiver
        $receiverResult = $db->table('accounts')
                            ->select(['balance'])
                            ->where('id', $toAccountId)
                            ->execute();

        $receiverBalance = $receiverResult->getRows()[0]['balance'];

        $db->table('accounts')
           ->update(['balance' => $receiverBalance + $transferAmount])
           ->where('id', $toAccountId)
           ->execute();

        // Record the transaction
        $db->table('transactions')->insert([
            'from_account' => $fromAccountId,
            'to_account' => $toAccountId,
            'amount' => $transferAmount,
            'description' => 'Money transfer'
        ])->execute();

        echo "✓ Transaction completed successfully\n";
    });

    echo "Account balances after successful transfer:\n";
    $result = $database->table('accounts')->select()->execute();

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
            // Check if sender has sufficient balance
            $senderResult = $db->table('accounts')
                              ->select(['balance'])
                              ->where('id', $fromAccountId)
                              ->execute();

            $senderBalance = $senderResult->getRows()[0]['balance'];

            if ($senderBalance < $largeTransferAmount) {
                throw new DatabaseException("Insufficient funds for transfer of $".number_format($largeTransferAmount, 2));
            }

            // This code won't be reached due to insufficient funds
            $db->table('accounts')
               ->update(['balance' => $senderBalance - $largeTransferAmount])
               ->where('id', $fromAccountId)
               ->execute();
        });
    } catch (DatabaseException $e) {
        echo "✗ Transaction failed: ".$e->getMessage()."\n";
        echo "✓ Transaction was rolled back automatically\n";
    }

    echo "Account balances after failed transaction (should be unchanged):\n";
    $result = $database->table('accounts')->select()->execute();

    foreach ($result as $account) {
        echo "  {$account['name']}: $".number_format($account['balance'], 2)."\n";
    }
    echo "\n";

    echo "5. Transaction History:\n";
    $result = $database->setQuery("
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

    echo "6. Cleanup:\n";
    $database->setQuery("DROP TABLE transactions")->execute();
    $database->setQuery("DROP TABLE accounts")->execute();
    echo "✓ Test tables dropped\n";
} catch (Exception $e) {
    echo "✗ Error: ".$e->getMessage()."\n";

    // Clean up on error
    try {
        $database->setQuery("DROP TABLE IF EXISTS transactions")->execute();
        $database->setQuery("DROP TABLE IF EXISTS accounts")->execute();
    } catch (Exception $cleanupError) {
        // Ignore cleanup errors
    }
}

echo "\n=== Example Complete ===\n";
