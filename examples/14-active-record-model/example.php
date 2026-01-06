<?php

require_once '../../vendor/autoload.php';
require_once __DIR__.'/Article.php';

use WebFiori\Database\Attributes\AttributeTableBuilder;
use WebFiori\Database\ConnectionInfo;
use WebFiori\Database\Database;

echo "=== Active Record Model Example ===\n\n";
echo "This example shows Entity + Repository merged into a single Model class.\n\n";

try {
    $connection = new ConnectionInfo('mysql', 'root', '123456', 'mysql');
    $database = new Database($connection);

    echo "1. Creating Table from Model Attributes:\n";

    // Build table from Article class attributes
    $table = AttributeTableBuilder::build(Article::class, 'mysql');
    $database->addTable($table);
    $database->table('articles')->createTable()->execute();
    echo "✓ Articles table created from class attributes\n\n";

    echo "2. Using the Model:\n";

    // Create and save articles directly
    $article1 = new Article($database);
    $article1->title = 'Introduction to WebFiori';
    $article1->content = 'WebFiori is a PHP framework...';
    $article1->authorName = 'Ahmad Hassan';
    $article1->save();  // Saves itself

    $article2 = new Article($database);
    $article2->title = 'Database Patterns';
    $article2->content = 'Understanding repository pattern...';
    $article2->authorName = 'Fatima Ali';
    $article2->save();

    $article3 = new Article($database);
    $article3->title = 'Advanced PHP';
    $article3->content = 'PHP 8 features and attributes...';
    $article3->authorName = 'Ahmad Hassan';
    $article3->save();

    echo "✓ Articles saved\n\n";

    echo "3. Querying with Repository Methods:\n";

    // Use any instance for queries
    $articleModel = new Article($database);

    // findAll()
    $all = $articleModel->findAll();
    echo "All articles ({$articleModel->count()}):\n";
    foreach ($all as $article) {
        echo "  - {$article->title} by {$article->authorName}\n";
    }
    echo "\n";

    // Custom query method
    $byAuthor = $articleModel->findByAuthor('Ahmad Hassan');
    echo "Articles by Ahmad Hassan:\n";
    foreach ($byAuthor as $article) {
        echo "  - {$article->title}\n";
    }
    echo "\n";

    echo "4. Update and Delete:\n";

    // Update
    $first = $articleModel->findById(1);
    $first->title = 'Updated: Introduction to WebFiori';
    $first->save();  // Saves itself
    echo "✓ Article updated\n";

    // Delete
    $first->id = 2;
    $first->deleteById();
    echo "✓ Article deleted\n";

    echo "\nRemaining articles:\n";
    foreach ($articleModel->findAll() as $article) {
        echo "  - [{$article->id}] {$article->title}\n";
    }
    echo "\n";

    echo "5. Cleanup:\n";
    $database->raw("DROP TABLE articles")->execute();
    echo "✓ Table dropped\n";

} catch (Exception $e) {
    echo "✗ Error: ".$e->getMessage()."\n";
    try {
        $database->raw("DROP TABLE IF EXISTS articles")->execute();
    } catch (Exception $cleanupError) {}
}

echo "\n=== Example Complete ===\n";
