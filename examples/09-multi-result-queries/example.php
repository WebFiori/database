<?php

require_once '../../vendor/autoload.php';

use WebFiori\Database\ColOption;
use WebFiori\Database\ConnectionInfo;
use WebFiori\Database\Database;
use WebFiori\Database\DataType;
use WebFiori\Database\MultiResultSet;

const SEP = "────────────────────────────────────────────────────────────────────\n";

echo "=== WebFiori Database Multi-Result Queries Example ===\n\n";

try {
    $connection = new ConnectionInfo('mysql', 'root', '123456', 'testing_db');
    $database = new Database($connection);

    echo SEP;
    echo "1. Setting up Test Tables:\n";

    $database->createBlueprint('products')->addColumns([
        'id' => [ColOption::TYPE => DataType::INT, ColOption::PRIMARY => true, ColOption::AUTO_INCREMENT => true],
        'name' => [ColOption::TYPE => DataType::VARCHAR, ColOption::SIZE => 100],
        'category' => [ColOption::TYPE => DataType::VARCHAR, ColOption::SIZE => 50],
        'price' => [ColOption::TYPE => DataType::DECIMAL, ColOption::SIZE => 10],
        'stock' => [ColOption::TYPE => DataType::INT, ColOption::DEFAULT => 0]
    ]);

    $database->createBlueprint('orders')->addColumns([
        'id' => [ColOption::TYPE => DataType::INT, ColOption::PRIMARY => true, ColOption::AUTO_INCREMENT => true],
        'product-id' => [ColOption::TYPE => DataType::INT],
        'quantity' => [ColOption::TYPE => DataType::INT],
        'order-date' => [ColOption::TYPE => DataType::DATETIME],
        'customer-name' => [ColOption::TYPE => DataType::VARCHAR, ColOption::SIZE => 100]
    ]);

    $database->table('orders')->drop(true)->execute();
    $database->table('products')->drop(true)->execute();
    $database->createTables();
    echo "   ✓ Test tables created\n\n";

    echo SEP;
    echo "2. Inserting Sample Data:\n";

    $database->table('products')->insert([
        'cols' => ['name', 'category', 'price', 'stock'],
        'values' => [
            ['Laptop', 'Electronics', 999.99, 10],
            ['Mouse', 'Electronics', 29.99, 50],
            ['Keyboard', 'Electronics', 79.99, 30],
            ['Chair', 'Furniture', 199.99, 15],
            ['Desk', 'Furniture', 299.99, 8],
            ['Book', 'Education', 19.99, 100]
        ]
    ])->execute();
    echo "   ✓ 6 products inserted\n";

    $database->table('orders')->insert([
        'cols' => ['product-id', 'quantity', 'order-date', 'customer-name'],
        'values' => [
            [1, 2, '2024-01-15', 'Ahmed Ali'],
            [2, 5, '2024-01-16', 'Fatima Hassan'],
            [3, 1, '2024-01-17', 'Omar Khalil'],
            [1, 1, '2024-01-18', 'Layla Ahmed'],
            [4, 3, '2024-01-19', 'Yusuf Ibrahim']
        ]
    ])->execute();
    echo "   ✓ 5 orders inserted\n\n";

    echo SEP;
    echo "3. Creating Stored Procedure:\n";

    $database->raw("DROP PROCEDURE IF EXISTS GetBusinessReport")->execute();
    $database->raw("
        CREATE PROCEDURE GetBusinessReport()
        BEGIN
            SELECT 'Product Inventory' as report_section, name, category, price, stock 
            FROM products ORDER BY category, name;
            
            SELECT 'Sales by Category' as report_section, p.category,
                   COUNT(o.id) as total_orders, SUM(o.quantity) as total_quantity,
                   SUM(o.quantity * p.price) as total_revenue
            FROM products p LEFT JOIN orders o ON p.id = o.product_id
            GROUP BY p.category ORDER BY total_revenue DESC;
            
            SELECT 'Recent Orders' as report_section, o.customer_name,
                   p.name as product_name, o.quantity, o.order_date,
                   (o.quantity * p.price) as order_total
            FROM orders o JOIN products p ON o.product_id = p.id
            ORDER BY o.order_date DESC LIMIT 10;
        END
    ")->execute();
    echo "   ✓ Stored procedure created\n\n";

    echo SEP;
    echo "4. Executing Multi-Result Query:\n";

    $result = $database->raw("CALL GetBusinessReport()")->execute();

    if ($result instanceof MultiResultSet) {
        echo "   ✓ Multi-result query executed\n";
        echo "   Number of result sets: ".$result->count()."\n\n";

        for ($i = 0; $i < $result->count(); $i++) {
            $resultSet = $result->getResultSet($i);

            if ($resultSet->getRowsCount() > 0) {
                $firstRow = $resultSet->getRows()[0];

                if (isset($firstRow['report_section'])) {
                    echo "   --- {$firstRow['report_section']} ---\n";

                    foreach ($resultSet as $row) {
                        if ($row['report_section'] === 'Product Inventory') {
                            echo "     {$row['category']}: {$row['name']} - $".number_format($row['price'], 2)." (Stock: {$row['stock']})\n";
                        } elseif ($row['report_section'] === 'Sales by Category') {
                            echo "     {$row['category']}: {$row['total_orders']} orders, $".number_format($row['total_revenue'], 2)." revenue\n";
                        } elseif ($row['report_section'] === 'Recent Orders') {
                            echo "     {$row['customer_name']}: {$row['product_name']} x{$row['quantity']} = $".number_format($row['order_total'], 2)."\n";
                        }
                    }
                    echo "\n";
                }
            }
        }
    }

    echo SEP;
    echo "5. Parameterized Multi-Result Query:\n";

    $database->raw("DROP PROCEDURE IF EXISTS GetCategoryAnalysis")->execute();
    $database->raw("
        CREATE PROCEDURE GetCategoryAnalysis(IN category_filter VARCHAR(50))
        BEGIN
            SELECT 'Products' as section, name, price, stock
            FROM products WHERE category COLLATE utf8mb4_unicode_520_ci = category_filter ORDER BY price DESC;
            
            SELECT 'Statistics' as section, category_filter as category,
                   COUNT(*) as product_count, AVG(price) as avg_price,
                   MIN(price) as min_price, MAX(price) as max_price, SUM(stock) as total_stock
            FROM products WHERE category COLLATE utf8mb4_unicode_520_ci = category_filter;
        END
    ")->execute();

    $categoryResult = $database->raw("CALL GetCategoryAnalysis('Electronics')")->execute();

    if ($categoryResult instanceof MultiResultSet) {
        echo "   ✓ Category analysis for 'Electronics':\n\n";

        for ($i = 0; $i < $categoryResult->count(); $i++) {
            $rs = $categoryResult->getResultSet($i);

            if ($rs->getRowsCount() > 0) {
                $firstRow = $rs->getRows()[0];

                if (isset($firstRow['section'])) {
                    echo "   --- {$firstRow['section']} ---\n";

                    foreach ($rs as $row) {
                        if ($row['section'] === 'Products') {
                            echo "     {$row['name']}: $".number_format($row['price'], 2)." (Stock: {$row['stock']})\n";
                        } elseif ($row['section'] === 'Statistics') {
                            echo "     Product Count: {$row['product_count']}\n";
                            echo "     Average Price: $".number_format($row['avg_price'], 2)."\n";
                            echo "     Price Range: $".number_format($row['min_price'], 2)." - $".number_format($row['max_price'], 2)."\n";
                        }
                    }
                    echo "\n";
                }
            }
        }
    }

    echo SEP;
    echo "6. Working with Individual Result Sets:\n";

    $businessReport = $database->raw("CALL GetBusinessReport()")->execute();

    if ($businessReport instanceof MultiResultSet) {
        $inventoryResults = $businessReport->getResultSet(0);
        $salesResults = $businessReport->getResultSet(1);
        $ordersResults = $businessReport->getResultSet(2);

        echo "   ✓ Extracted individual result sets:\n";
        echo "     - Inventory: ".$inventoryResults->getRowsCount()." products\n";
        echo "     - Sales: ".$salesResults->getRowsCount()." categories\n";
        echo "     - Orders: ".$ordersResults->getRowsCount()." orders\n\n";

        echo "   Low stock products (< 20 items):\n";

        foreach ($inventoryResults as $product) {
            if ($product['stock'] < 20) {
                echo "     ⚠️  {$product['name']}: {$product['stock']} remaining\n";
            }
        }
    }
    echo "\n";

    echo SEP;
    echo "7. Cleanup:\n";
    $database->raw("DROP PROCEDURE IF EXISTS GetBusinessReport")->execute();
    $database->raw("DROP PROCEDURE IF EXISTS GetCategoryAnalysis")->execute();
    $database->table('orders')->drop()->execute();
    $database->table('products')->drop()->execute();
    echo "   ✓ Tables and procedures dropped\n";
} catch (Exception $e) {
    echo "✗ Error: ".$e->getMessage()."\n";
    try {
        $database->raw("DROP PROCEDURE IF EXISTS GetBusinessReport")->execute();
        $database->raw("DROP PROCEDURE IF EXISTS GetCategoryAnalysis")->execute();
        $database->table('orders')->drop(true)->execute();
        $database->table('products')->drop(true)->execute();
    } catch (Exception $cleanupError) {
    }
}

echo "\n".SEP;
echo "=== Example Complete ===\n";
