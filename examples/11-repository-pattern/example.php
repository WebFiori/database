<?php

require_once '../../vendor/autoload.php';
require_once __DIR__.'/Product.php';
require_once __DIR__.'/ProductRepository.php';

use WebFiori\Database\ColOption;
use WebFiori\Database\ConnectionInfo;
use WebFiori\Database\Database;
use WebFiori\Database\DataType;

const SEP = "────────────────────────────────────────────────────────────────────\n";

echo "=== WebFiori Database Repository Pattern Example ===\n\n";

try {
    $connection = new ConnectionInfo('mysql', 'root', '123456', 'testing_db');
    $database = new Database($connection);

    echo SEP;
    echo "1. Setting up Database:\n";

    $database->createBlueprint('products')->addColumns([
        'id' => [ColOption::TYPE => DataType::INT, ColOption::PRIMARY => true, ColOption::AUTO_INCREMENT => true],
        'name' => [ColOption::TYPE => DataType::VARCHAR, ColOption::SIZE => 100],
        'category' => [ColOption::TYPE => DataType::VARCHAR, ColOption::SIZE => 50],
        'price' => [ColOption::TYPE => DataType::DECIMAL, ColOption::SIZE => 10],
        'stock' => [ColOption::TYPE => DataType::INT]
    ]);

    $database->table('products')->drop(true)->execute();
    $database->table('products')->createTable()->execute();
    echo "   ✓ Products table created\n\n";

    echo SEP;
    echo "2. Creating Repository:\n";
    $productRepo = new ProductRepository($database);
    echo "   ✓ ProductRepository created\n\n";

    echo SEP;
    echo "3. Saving Products (Create):\n";
    $products = [
        new Product('Laptop', 'Electronics', 999.99, 15),
        new Product('Mouse', 'Electronics', 29.99, 50),
        new Product('Keyboard', 'Electronics', 79.99, 5),
        new Product('Chair', 'Furniture', 199.99, 8),
        new Product('Desk', 'Furniture', 299.99, 3),
        new Product('Book', 'Education', 19.99, 100)
    ];

    foreach ($products as $product) {
        $productRepo->save($product);
        echo "   ✓ Saved: {$product->name}\n";
    }
    echo "\n";

    echo SEP;
    echo "4. Finding All Products (Read):\n";
    $allProducts = $productRepo->findAll();
    echo "   Total products: ".count($allProducts)."\n";
    foreach ($allProducts as $p) {
        echo "   - {$p->name} ({$p->category}): \${$p->price} - Stock: {$p->stock}\n";
    }
    echo "\n";

    echo SEP;
    echo "5. Finding by ID:\n";
    $product = $productRepo->findById(1);
    if ($product) {
        echo "   Found: {$product->name} - \${$product->price}\n\n";
    }

    echo SEP;
    echo "6. Custom Query - Find by Category:\n";
    $electronics = $productRepo->findByCategory('Electronics');
    echo "   Electronics products: ".count($electronics)."\n";
    foreach ($electronics as $p) {
        echo "   - {$p->name}: \${$p->price}\n";
    }
    echo "\n";

    echo SEP;
    echo "7. Custom Query - Find Low Stock:\n";
    $lowStock = $productRepo->findLowStock(10);
    echo "   Low stock products (< 10): ".count($lowStock)."\n";
    foreach ($lowStock as $p) {
        echo "   ⚠️  {$p->name}: {$p->stock} remaining\n";
    }
    echo "\n";

    echo SEP;
    echo "8. Updating a Product:\n";
    $product = $productRepo->findById(3);
    if ($product) {
        echo "   Before: {$product->name} - Stock: {$product->stock}\n";
        $product->stock = 25;
        $productRepo->save($product);
        $updated = $productRepo->findById(3);
        echo "   After: {$updated->name} - Stock: {$updated->stock}\n\n";
    }

    echo SEP;
    echo "9. Pagination (Offset-based):\n";
    $page1 = $productRepo->paginate(1, 3);
    echo "   Page 1 (3 per page):\n";
    echo "   Total items: {$page1->getTotalItems()}\n";
    echo "   Total pages: {$page1->getTotalPages()}\n";
    foreach ($page1->getItems() as $p) {
        echo "   - {$p->name}\n";
    }
    echo "\n";

    echo SEP;
    echo "10. Counting Products:\n";
    echo "    Total products in database: ".$productRepo->count()."\n\n";

    echo SEP;
    echo "11. Deleting a Product:\n";
    $productRepo->deleteById(6);
    echo "    ✓ Deleted product with ID 6\n";
    echo "    Products remaining: ".$productRepo->count()."\n\n";

    echo SEP;
    echo "12. Cleanup:\n";
    $database->table('products')->drop()->execute();
    echo "    ✓ Products table dropped\n";

} catch (Exception $e) {
    echo "✗ Error: ".$e->getMessage()."\n";
    try {
        $database->table('products')->drop(true)->execute();
    } catch (Exception $cleanupError) {}
}

echo "\n" . SEP;
echo "=== Example Complete ===\n";
