<?php

require_once '../../vendor/autoload.php';

use WebFiori\Database\ColOption;
use WebFiori\Database\ConnectionInfo;
use WebFiori\Database\Database;
use WebFiori\Database\DatabaseException;
use WebFiori\Database\DataType;

const SEP = "────────────────────────────────────────────────────────────────────\n";

echo "=== WebFiori Database Transactions Example ===\n\n";

try {
    $connection = new ConnectionInfo('mysql', 'root', '123456', 'testing_db');
    $database = new Database($connection);

    echo SEP;
    echo "1. Setting up Tables:\n";

    $database->createBlueprint('accounts')->addColumns([
        'id' => [ColOption::TYPE => DataType::INT, ColOption::PRIMARY => true, ColOption::AUTO_INCREMENT => true],
        'name' => [ColOption::TYPE => DataType::VARCHAR, ColOption::SIZE => 100],
        'balance' => [ColOption::TYPE => DataType::DECIMAL, ColOption::SIZE => 10]
    ]);

    $database->createBlueprint('transactions')->addColumns([
        'id' => [ColOption::TYPE => DataType::INT, ColOption::PRIMARY => true, ColOption::AUTO_INCREMENT => true],
        'from-account' => [ColOption::TYPE => DataType::INT],
        'to-account' => [ColOption::TYPE => DataType::INT],
        'amount' => [ColOption::TYPE => DataType::DECIMAL, ColOption::SIZE => 10],
        'description' => [ColOption::TYPE => DataType::VARCHAR, ColOption::SIZE => 255]
    ]);

    $database->table('transactions')->drop(true)->execute();
    $database->table('accounts')->drop(true)->execute();
    $database->createTables();
    echo "   ✓ Tables created\n\n";

    echo SEP;
    echo "2. Initial Account Balances:\n";

    $database->table('accounts')->insert([
        'cols' => ['name', 'balance'],
        'values' => [
            ['Amira', 1000.00],
            ['Yusuf', 500.00]
        ]
    ])->execute();

    $result = $database->table('accounts')->select()->execute();
    foreach ($result as $account) {
        echo "   {$account['name']}: $".number_format($account['balance'], 2)."\n";
    }
    echo "\n";

    echo SEP;
    echo "3. Successful Transaction:\n";

    $transferAmount = 200.00;

    $database->transaction(function (Database $db) use ($transferAmount) {
        $sender = $db->table('accounts')->select()->where('id', 1)->execute()->fetch();
        
        if ($sender['balance'] < $transferAmount) {
            throw new DatabaseException("Insufficient funds");
        }

        $db->table('accounts')->update(['balance' => $sender['balance'] - $transferAmount])->where('id', 1)->execute();
        
        $receiver = $db->table('accounts')->select()->where('id', 2)->execute()->fetch();
        $db->table('accounts')->update(['balance' => $receiver['balance'] + $transferAmount])->where('id', 2)->execute();

        $db->table('transactions')->insert([
            'from-account' => 1,
            'to-account' => 2,
            'amount' => $transferAmount,
            'description' => 'Money transfer'
        ])->execute();

        echo "   ✓ Transaction completed\n";
    });

    $result = $database->table('accounts')->select()->execute();
    echo "   Balances after transfer:\n";
    foreach ($result as $account) {
        echo "   - {$account['name']}: $".number_format($account['balance'], 2)."\n";
    }
    echo "\n";

    echo SEP;
    echo "4. Failed Transaction (Insufficient Funds):\n";

    try {
        $database->transaction(function (Database $db) {
            $sender = $db->table('accounts')->select()->where('id', 1)->execute()->fetch();
            
            if ($sender['balance'] < 2000.00) {
                throw new DatabaseException("Insufficient funds for $2000.00 transfer");
            }
        });
    } catch (DatabaseException $e) {
        echo "   ✗ Transaction failed: ".$e->getMessage()."\n";
        echo "   ✓ Rolled back automatically\n";
    }

    $result = $database->table('accounts')->select()->execute();
    echo "   Balances unchanged:\n";
    foreach ($result as $account) {
        echo "   - {$account['name']}: $".number_format($account['balance'], 2)."\n";
    }
    echo "\n";

    echo SEP;
    echo "5. Cleanup:\n";
    $database->table('transactions')->drop()->execute();
    $database->table('accounts')->drop()->execute();
    echo "   ✓ Tables dropped\n";

} catch (Exception $e) {
    echo "✗ Error: ".$e->getMessage()."\n";
    try {
        $database->table('transactions')->drop(true)->execute();
        $database->table('accounts')->drop(true)->execute();
    } catch (Exception $cleanupError) {}
}

echo "\n" . SEP;
echo "=== Example Complete ===\n";
