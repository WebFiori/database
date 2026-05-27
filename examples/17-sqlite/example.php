<?php

require_once '../../vendor/autoload.php';

use WebFiori\Database\ColOption;
use WebFiori\Database\ConnectionInfo;
use WebFiori\Database\Database;
use WebFiori\Database\DataType;

const SEP = "────────────────────────────────────────────────────────────────────\n";

echo "=== WebFiori Database - SQLite Example ===\n\n";

try {
    // 1. Connect to SQLite (in-memory)
    echo SEP;
    echo "1. Connecting to SQLite (in-memory):\n";
    $connection = new ConnectionInfo('sqlite', '', '', ':memory:');
    $db = new Database($connection);
    echo "   ✓ Connected to SQLite in-memory database\n\n";

    // 2. Create table
    echo SEP;
    echo "2. Creating Table:\n";
    $db->createBlueprint('products')->addColumns([
        'id' => [
            ColOption::TYPE => DataType::INT,
            ColOption::PRIMARY => true,
            ColOption::AUTO_INCREMENT => true
        ],
        'name' => [
            ColOption::TYPE => DataType::VARCHAR,
            ColOption::SIZE => 100
        ],
        'price' => [
            ColOption::TYPE => DataType::DECIMAL,
            ColOption::SIZE => 10
        ],
        'in_stock' => [
            ColOption::TYPE => DataType::BOOL
        ]
    ]);

    echo "   SQL: ".$db->getTable('products')->toSQL()."\n";
    $db->table('products')->createTable()->execute();
    echo "   ✓ Table created\n\n";

    // 3. Insert records
    echo SEP;
    echo "3. Inserting Records:\n";
    $products = [
        ['name' => 'Laptop', 'price' => 999.99, 'in_stock' => 1],
        ['name' => 'Mouse', 'price' => 29.99, 'in_stock' => 1],
        ['name' => 'Keyboard', 'price' => 79.99, 'in_stock' => 0],
        ['name' => 'Monitor', 'price' => 449.99, 'in_stock' => 1],
    ];

    foreach ($products as $product) {
        $db->table('products')->insert($product)->execute();
    }
    echo "   ✓ Inserted ".count($products)." products\n\n";

    // 4. Select all
    echo SEP;
    echo "4. Select All Products:\n";
    $result = $db->table('products')->select()->execute();

    foreach ($result as $row) {
        $stock = $row['in_stock'] ? '✓' : '✗';
        echo "   [{$row['id']}] {$row['name']} - \${$row['price']} (In stock: $stock)\n";
    }
    echo "\n";

    // 5. Select with WHERE
    echo SEP;
    echo "5. Products in Stock (price > 50):\n";
    $result = $db->table('products')
        ->select()
        ->where('in_stock', 1)
        ->andWhere('price', 50, '>')
        ->execute();

    foreach ($result as $row) {
        echo "   {$row['name']} - \${$row['price']}\n";
    }
    echo "\n";

    // 6. Aggregates
    echo SEP;
    echo "6. Aggregates:\n";
    $count = $db->table('products')->selectCount()->execute()->fetch()['count'];
    $max = $db->table('products')->selectMax('price')->execute()->fetch()['max'];
    $min = $db->table('products')->selectMin('price')->execute()->fetch()['min'];
    echo "   Total products: $count\n";
    echo "   Most expensive: \$$max\n";
    echo "   Cheapest: \$$min\n\n";

    // 7. Update
    echo SEP;
    echo "7. Update (Keyboard now in stock):\n";
    $db->table('products')->update(['in_stock' => 1])->where('name', 'Keyboard')->execute();
    $row = $db->table('products')->select()->where('name', 'Keyboard')->execute()->fetch();
    echo "   Keyboard in_stock: ".($row['in_stock'] ? 'Yes' : 'No')."\n\n";

    // 8. Delete
    echo SEP;
    echo "8. Delete (Remove Mouse):\n";
    $db->table('products')->delete()->where('name', 'Mouse')->execute();
    $count = $db->table('products')->selectCount()->execute()->fetch()['count'];
    echo "   Products remaining: $count\n\n";

    // 9. Pagination
    echo SEP;
    echo "9. Pagination (page 1, 2 per page):\n";
    $result = $db->table('products')->select()->limit(2)->offset(0)->execute();

    foreach ($result as $row) {
        echo "   {$row['name']}\n";
    }
    echo "\n";

    // 10. Transactions
    echo SEP;
    echo "10. Transaction:\n";
    $db->transaction(function (Database $db)
    {
        $db->table('products')->insert(['name' => 'Webcam', 'price' => 59.99, 'in_stock' => 1])->execute();
        $db->table('products')->insert(['name' => 'Headset', 'price' => 89.99, 'in_stock' => 1])->execute();
    });
    $count = $db->table('products')->selectCount()->execute()->fetch()['count'];
    echo "   ✓ Transaction committed. Products: $count\n\n";

    // 11. File-based database
    echo SEP;
    echo "11. File-based SQLite:\n";
    $filePath = sys_get_temp_dir().'/webfiori_example.db';
    $fileConn = new ConnectionInfo('sqlite', '', '', $filePath);
    $fileDb = new Database($fileConn);
    $fileDb->createBlueprint('notes')->addColumns([
        'id' => [ColOption::TYPE => DataType::INT, ColOption::PRIMARY => true, ColOption::AUTO_INCREMENT => true],
        'text' => [ColOption::TYPE => DataType::VARCHAR, ColOption::SIZE => 500],
    ]);
    $fileDb->table('notes')->createTable()->execute();
    $fileDb->table('notes')->insert(['text' => 'Hello from SQLite file!'])->execute();
    echo "   ✓ Created file database at: $filePath\n";
    echo "   ✓ Inserted a note\n";
    unlink($filePath);
    echo "   ✓ Cleaned up\n";
} catch (Exception $e) {
    echo "✗ Error: ".$e->getMessage()."\n";
}

echo "\n".SEP;
echo "=== Example Complete ===\n";
