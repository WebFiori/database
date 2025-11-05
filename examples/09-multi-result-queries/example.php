<?php

require_once '../../vendor/autoload.php';

use WebFiori\Database\ConnectionInfo;
use WebFiori\Database\Database;
use WebFiori\Database\MultiResultSet;

echo "=== WebFiori Database Multi-Result Queries Example ===\n\n";

try {
    // Create connection
    $connection = new ConnectionInfo('mysql', 'root', '123456', 'mysql');
    $database = new Database($connection);

    echo "1. Setting up Test Data:\n";

    // Create test tables
    $database->raw("
        CREATE TABLE IF NOT EXISTS products (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            category VARCHAR(50) NOT NULL,
            price DECIMAL(10,2) NOT NULL,
            stock INT NOT NULL DEFAULT 0
        )
    ")->execute();

    $database->raw("
        CREATE TABLE IF NOT EXISTS orders (
            id INT AUTO_INCREMENT PRIMARY KEY,
            product_id INT,
            quantity INT NOT NULL,
            order_date DATE NOT NULL,
            customer_name VARCHAR(100) NOT NULL
        )
    ")->execute();

    echo "✓ Test tables created\n";

    // Clear existing data
    $database->raw("DELETE FROM orders")->execute();
    $database->raw("DELETE FROM products")->execute();

    // Insert sample products
    $products = [
        ['Laptop', 'Electronics', 999.99, 10],
        ['Mouse', 'Electronics', 29.99, 50],
        ['Keyboard', 'Electronics', 79.99, 30],
        ['Chair', 'Furniture', 199.99, 15],
        ['Desk', 'Furniture', 299.99, 8],
        ['Book', 'Education', 19.99, 100]
    ];

    foreach ($products as $product) {
        $database->raw("INSERT INTO products (name, category, price, stock) VALUES (?, ?, ?, ?)", $product)->execute();
    }

    // Insert sample orders
    $orders = [
        [1, 2, '2024-01-15', 'Ahmed Ali'],
        [2, 5, '2024-01-16', 'Fatima Hassan'],
        [3, 1, '2024-01-17', 'Omar Khalil'],
        [1, 1, '2024-01-18', 'Layla Ahmed'],
        [4, 3, '2024-01-19', 'Yusuf Ibrahim']
    ];

    foreach ($orders as $order) {
        $database->raw("INSERT INTO orders (product_id, quantity, order_date, customer_name) VALUES (?, ?, ?, ?)", $order)->execute();
    }

    echo "✓ Sample data inserted\n\n";

    echo "2. Basic Multi-Result Query Example:\n";

    // Create a stored procedure that returns multiple result sets
    $database->raw("DROP PROCEDURE IF EXISTS GetBusinessReport")->execute();
    $database->raw("
        CREATE PROCEDURE GetBusinessReport()
        BEGIN
            -- Result Set 1: Product inventory
            SELECT 'Product Inventory' as report_section, name, category, price, stock 
            FROM products 
            ORDER BY category, name;
            
            -- Result Set 2: Sales summary by category
            SELECT 'Sales by Category' as report_section,
                   p.category,
                   COUNT(o.id) as total_orders,
                   SUM(o.quantity) as total_quantity,
                   SUM(o.quantity * p.price) as total_revenue
            FROM products p
            LEFT JOIN orders o ON p.id = o.product_id
            GROUP BY p.category
            ORDER BY total_revenue DESC;
            
            -- Result Set 3: Recent orders
            SELECT 'Recent Orders' as report_section,
                   o.customer_name,
                   p.name as product_name,
                   o.quantity,
                   o.order_date,
                   (o.quantity * p.price) as order_total
            FROM orders o
            JOIN products p ON o.product_id = p.id
            ORDER BY o.order_date DESC
            LIMIT 10;
        END
    ")->execute();

    // Execute the multi-result procedure
    $result = $database->raw("CALL GetBusinessReport()")->execute();

    if ($result instanceof MultiResultSet) {
        echo "✓ Multi-result query executed successfully!\n";
        echo "Number of result sets: " . $result->count() . "\n\n";

        // Process each result set
        for ($i = 0; $i < $result->count(); $i++) {
            $resultSet = $result->getResultSet($i);
            
            if ($resultSet->getRowsCount() > 0) {
                $firstRow = $resultSet->getRows()[0];
                
                if (isset($firstRow['report_section'])) {
                    echo "--- {$firstRow['report_section']} ---\n";
                    
                    foreach ($resultSet as $row) {
                        if ($row['report_section'] === 'Product Inventory') {
                            echo "  {$row['category']}: {$row['name']} - $" . number_format($row['price'], 2) . " (Stock: {$row['stock']})\n";
                        } elseif ($row['report_section'] === 'Sales by Category') {
                            echo "  {$row['category']}: {$row['total_orders']} orders, {$row['total_quantity']} items, $" . number_format($row['total_revenue'], 2) . " revenue\n";
                        } elseif ($row['report_section'] === 'Recent Orders') {
                            echo "  {$row['customer_name']}: {$row['product_name']} x{$row['quantity']} = $" . number_format($row['order_total'], 2) . " ({$row['order_date']})\n";
                        }
                    }
                    echo "\n";
                }
            }
        }
    }

    echo "3. Advanced Multi-Result with Parameters:\n";

    // Create a parameterized stored procedure
    $database->raw("DROP PROCEDURE IF EXISTS GetCategoryAnalysis")->execute();
    $database->raw("
        CREATE PROCEDURE GetCategoryAnalysis(IN category_filter VARCHAR(50))
        BEGIN
            -- Result Set 1: Products in category
            SELECT 'Products in Category' as report_section,
                   name, price, stock
            FROM products 
            WHERE category = category_filter
            ORDER BY price DESC;
            
            -- Result Set 2: Category statistics
            SELECT 'Category Statistics' as report_section,
                   category_filter as category,
                   COUNT(*) as product_count,
                   AVG(price) as avg_price,
                   MIN(price) as min_price,
                   MAX(price) as max_price,
                   SUM(stock) as total_stock;
            
            -- Result Set 3: Orders for this category
            SELECT 'Category Orders' as report_section,
                   o.customer_name,
                   p.name as product_name,
                   o.quantity,
                   o.order_date
            FROM orders o
            JOIN products p ON o.product_id = p.id
            WHERE p.category = category_filter
            ORDER BY o.order_date DESC;
        END
    ")->execute();

    // Execute with parameter using raw()
    $categoryResult = $database->raw("CALL GetCategoryAnalysis(?)", ['Electronics'])->execute();

    if ($categoryResult instanceof MultiResultSet) {
        echo "✓ Parameterized multi-result query executed for 'Electronics'!\n\n";

        for ($i = 0; $i < $categoryResult->count(); $i++) {
            $rs = $categoryResult->getResultSet($i);
            
            if ($rs->getRowsCount() > 0) {
                $firstRow = $rs->getRows()[0];
                
                if (isset($firstRow['report_section'])) {
                    echo "--- {$firstRow['report_section']} ---\n";
                    
                    foreach ($rs as $row) {
                        if ($row['report_section'] === 'Products in Category') {
                            echo "  {$row['name']}: $" . number_format($row['price'], 2) . " (Stock: {$row['stock']})\n";
                        } elseif ($row['report_section'] === 'Category Statistics') {
                            echo "  Category: {$row['category']}\n";
                            echo "  Product Count: {$row['product_count']}\n";
                            echo "  Average Price: $" . number_format($row['avg_price'], 2) . "\n";
                            echo "  Price Range: $" . number_format($row['min_price'], 2) . " - $" . number_format($row['max_price'], 2) . "\n";
                            echo "  Total Stock: {$row['total_stock']}\n";
                        } elseif ($row['report_section'] === 'Category Orders') {
                            echo "  {$row['customer_name']}: {$row['product_name']} x{$row['quantity']} ({$row['order_date']})\n";
                        }
                    }
                    echo "\n";
                }
            }
        }
    }

    echo "4. Working with Individual Result Sets:\n";

    // Execute and work with specific result sets
    $businessReport = $database->raw("CALL GetBusinessReport()")->execute();

    if ($businessReport instanceof MultiResultSet) {
        // Get specific result sets
        $inventoryResults = $businessReport->getResultSet(0);
        $salesResults = $businessReport->getResultSet(1);
        $ordersResults = $businessReport->getResultSet(2);

        echo "✓ Extracted individual result sets:\n";
        echo "  - Inventory results: " . $inventoryResults->getRowsCount() . " products\n";
        echo "  - Sales results: " . $salesResults->getRowsCount() . " categories\n";
        echo "  - Orders results: " . $ordersResults->getRowsCount() . " orders\n\n";

        // Process specific result set
        echo "Low stock products (< 20 items):\n";
        foreach ($inventoryResults as $product) {
            if ($product['stock'] < 20) {
                echo "  ⚠️  {$product['name']}: {$product['stock']} remaining\n";
            }
        }
        echo "\n";

        // Find best selling category
        $bestCategory = null;
        $bestRevenue = 0;
        
        foreach ($salesResults as $category) {
            if ($category['total_revenue'] > $bestRevenue) {
                $bestRevenue = $category['total_revenue'];
                $bestCategory = $category['category'];
            }
        }
        
        if ($bestCategory) {
            echo "🏆 Best performing category: {$bestCategory} ($" . number_format($bestRevenue, 2) . " revenue)\n\n";
        }
    }

    echo "5. Multi-Result with Complex Logic:\n";

    // Create a procedure with conditional logic
    $database->raw("DROP PROCEDURE IF EXISTS GetDynamicReport")->execute();
    $database->raw("
        CREATE PROCEDURE GetDynamicReport(IN report_type VARCHAR(20))
        BEGIN
            IF report_type = 'summary' THEN
                SELECT 'Summary Report' as report_section,
                       'Products' as metric, COUNT(*) as value FROM products
                UNION ALL
                SELECT 'Summary Report' as report_section,
                       'Orders' as metric, COUNT(*) as value FROM orders
                UNION ALL
                SELECT 'Summary Report' as report_section,
                       'Categories' as metric, COUNT(DISTINCT category) as value FROM products;
                       
                SELECT 'Revenue by Month' as report_section,
                       DATE_FORMAT(o.order_date, '%Y-%m') as month,
                       SUM(o.quantity * p.price) as revenue
                FROM orders o
                JOIN products p ON o.product_id = p.id
                GROUP BY DATE_FORMAT(o.order_date, '%Y-%m')
                ORDER BY month;
            ELSE
                SELECT 'Detailed Report' as report_section,
                       p.name, p.category, p.price, p.stock,
                       COALESCE(SUM(o.quantity), 0) as total_sold
                FROM products p
                LEFT JOIN orders o ON p.id = o.product_id
                GROUP BY p.id, p.name, p.category, p.price, p.stock
                ORDER BY total_sold DESC;
            END IF;
        END
    ")->execute();

    // Test with different parameters
    echo "Summary Report:\n";
    $summaryResult = $database->raw("CALL GetDynamicReport(?)", ['summary'])->execute();
    
    if ($summaryResult instanceof MultiResultSet) {
        for ($i = 0; $i < $summaryResult->count(); $i++) {
            $rs = $summaryResult->getResultSet($i);
            foreach ($rs as $row) {
                if (isset($row['metric'])) {
                    echo "  {$row['metric']}: {$row['value']}\n";
                } elseif (isset($row['month'])) {
                    echo "  {$row['month']}: $" . number_format($row['revenue'], 2) . "\n";
                }
            }
        }
    }

    echo "\n6. Cleanup:\n";
    $database->raw("DROP PROCEDURE IF EXISTS GetBusinessReport")->execute();
    $database->raw("DROP PROCEDURE IF EXISTS GetCategoryAnalysis")->execute();
    $database->raw("DROP PROCEDURE IF EXISTS GetDynamicReport")->execute();
    $database->raw("DROP TABLE orders")->execute();
    $database->raw("DROP TABLE products")->execute();
    echo "✓ Test tables and procedures dropped\n";

} catch (Exception $e) {
    echo "✗ Error: ".$e->getMessage()."\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";

    // Clean up on error
    try {
        $database->raw("DROP PROCEDURE IF EXISTS GetBusinessReport")->execute();
        $database->raw("DROP PROCEDURE IF EXISTS GetCategoryAnalysis")->execute();
        $database->raw("DROP PROCEDURE IF EXISTS GetDynamicReport")->execute();
        $database->raw("DROP TABLE IF EXISTS orders")->execute();
        $database->raw("DROP TABLE IF EXISTS products")->execute();
    } catch (Exception $cleanupError) {
        // Ignore cleanup errors
    }
}

echo "\n=== Example Complete ===\n";
