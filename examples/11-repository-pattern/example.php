<?php

require_once '../../vendor/autoload.php';

use WebFiori\Database\ColOption;
use WebFiori\Database\ConnectionInfo;
use WebFiori\Database\Database;
use WebFiori\Database\DataType;
use WebFiori\Database\Repository\AbstractRepository;

echo "=== WebFiori Database Repository Pattern Example ===\n\n";

// Define a simple entity class
class Product {
    public ?int $id = null;
    public string $name;
    public string $category;
    public float $price;
    public int $stock;

    public function __construct(string $name = '', string $category = '', float $price = 0, int $stock = 0) {
        $this->name = $name;
        $this->category = $category;
        $this->price = $price;
        $this->stock = $stock;
    }
}

// Define a repository for the Product entity
class ProductRepository extends AbstractRepository {
    protected function getTableName(): string {
        return 'products';
    }

    protected function getIdField(): string {
        return 'id';
    }

    protected function toEntity(array $row): object {
        $product = new Product();
        $product->id = (int) $row['id'];
        $product->name = $row['name'];
        $product->category = $row['category'];
        $product->price = (float) $row['price'];
        $product->stock = (int) $row['stock'];
        return $product;
    }

    protected function toArray(object $entity): array {
        return [
            'id' => $entity->id,
            'name' => $entity->name,
            'category' => $entity->category,
            'price' => $entity->price,
            'stock' => $entity->stock
        ];
    }

    // Custom method: find products by category
    public function findByCategory(string $category): array {
        $result = $this->getDatabase()->table($this->getTableName())
            ->select()
            ->where('category', $category)
            ->execute();

        return array_map(fn($row) => $this->toEntity($row), $result->fetchAll());
    }

    // Custom method: find low stock products
    public function findLowStock(int $threshold = 10): array {
        $result = $this->getDatabase()->table($this->getTableName())
            ->select()
            ->where('stock', $threshold, '<')
            ->execute();

        return array_map(fn($row) => $this->toEntity($row), $result->fetchAll());
    }
}

try {
    // Create connection
    $connection = new ConnectionInfo('mysql', 'root', '123456', 'mysql');
    $database = new Database($connection);

    echo "1. Setting up Database:\n";

    // Clean up and create table
    $database->raw("DROP TABLE IF EXISTS products")->execute();

    $database->createBlueprint('products')->addColumns([
        'id' => [ColOption::TYPE => DataType::INT, ColOption::PRIMARY => true, ColOption::AUTO_INCREMENT => true],
        'name' => [ColOption::TYPE => DataType::VARCHAR, ColOption::SIZE => 100],
        'category' => [ColOption::TYPE => DataType::VARCHAR, ColOption::SIZE => 50],
        'price' => [ColOption::TYPE => DataType::DECIMAL, ColOption::SIZE => 10],
        'stock' => [ColOption::TYPE => DataType::INT]
    ]);

    $database->table('products')->createTable();
    $database->execute();
    echo "✓ Products table created\n\n";

    echo "2. Creating Repository:\n";
    $productRepo = new ProductRepository($database);
    echo "✓ ProductRepository created\n\n";

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
        echo "  ✓ Saved: {$product->name}\n";
    }
    echo "\n";

    echo "4. Finding All Products (Read):\n";
    $allProducts = $productRepo->findAll();
    echo "Total products: ".count($allProducts)."\n";
    foreach ($allProducts as $p) {
        echo "  - {$p->name} ({$p->category}): \${$p->price} - Stock: {$p->stock}\n";
    }
    echo "\n";

    echo "5. Finding by ID:\n";
    $product = $productRepo->findById(1);
    if ($product) {
        echo "  Found: {$product->name} - \${$product->price}\n\n";
    }

    echo "6. Custom Query - Find by Category:\n";
    $electronics = $productRepo->findByCategory('Electronics');
    echo "Electronics products: ".count($electronics)."\n";
    foreach ($electronics as $p) {
        echo "  - {$p->name}: \${$p->price}\n";
    }
    echo "\n";

    echo "7. Custom Query - Find Low Stock:\n";
    $lowStock = $productRepo->findLowStock(10);
    echo "Low stock products (< 10): ".count($lowStock)."\n";
    foreach ($lowStock as $p) {
        echo "  ⚠️  {$p->name}: {$p->stock} remaining\n";
    }
    echo "\n";

    echo "8. Updating a Product:\n";
    $product = $productRepo->findById(3);
    if ($product) {
        echo "  Before: {$product->name} - Stock: {$product->stock}\n";
        $product->stock = 25;
        $productRepo->save($product);
        $updated = $productRepo->findById(3);
        echo "  After: {$updated->name} - Stock: {$updated->stock}\n\n";
    }

    echo "9. Pagination (Offset-based):\n";
    $page1 = $productRepo->paginate(1, 3);
    echo "Page 1 (3 per page):\n";
    echo "  Total items: {$page1->getTotalItems()}\n";
    echo "  Total pages: {$page1->getTotalPages()}\n";
    echo "  Items on this page: ".count($page1->getItems())."\n";
    foreach ($page1->getItems() as $p) {
        echo "    - {$p->name}\n";
    }
    echo "\n";

    echo "10. Counting Products:\n";
    $count = $productRepo->count();
    echo "  Total products in database: $count\n\n";

    echo "11. Deleting a Product:\n";
    $productRepo->deleteById(6);
    echo "  ✓ Deleted product with ID 6\n";
    $newCount = $productRepo->count();
    echo "  Products remaining: $newCount\n\n";

    echo "12. Cleanup:\n";
    $database->raw("DROP TABLE products")->execute();
    echo "✓ Products table dropped\n";
} catch (Exception $e) {
    echo "✗ Error: ".$e->getMessage()."\n";

    try {
        $database->raw("DROP TABLE IF EXISTS products")->execute();
    } catch (Exception $cleanupError) {
        // Ignore
    }
}

echo "\n=== Example Complete ===\n";
