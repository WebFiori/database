<?php

require_once '../../vendor/autoload.php';
require_once __DIR__.'/Article.php';

use WebFiori\Database\Attributes\AttributeTableBuilder;
use WebFiori\Database\ConnectionInfo;
use WebFiori\Database\Database;

const SEP = "────────────────────────────────────────────────────────────────────\n";

echo "=== Active Record Model Example ===\n\n";
echo "This example shows Entity + Repository merged into a single Model class.\n\n";

try {
    $connection = new ConnectionInfo('mysql', 'root', '123456', 'testing_db');
    $database = new Database($connection);

    echo SEP;
    echo "1. Creating Table from Model Attributes:\n";

    $table = AttributeTableBuilder::build(Article::class, 'mysql');
    $database->addTable($table);
    $database->table('articles')->drop(true)->execute();
    $database->table('articles')->createTable()->execute();
    echo "   ✓ Articles table created from class attributes\n\n";

    echo SEP;
    echo "2. Using the Model:\n";

    $article1 = new Article($database);
    $article1->title = 'Introduction to WebFiori';
    $article1->content = 'WebFiori is a PHP framework...';
    $article1->authorName = 'Ahmad Hassan';
    $article1->save();

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

    echo "   ✓ 3 articles saved\n\n";

    echo SEP;
    echo "3. Querying with Repository Methods:\n";

    $articleModel = new Article($database);

    $all = $articleModel->findAll();
    echo "   All articles ({$articleModel->count()}):\n";
    foreach ($all as $article) {
        echo "   - {$article->title} by {$article->authorName}\n";
    }
    echo "\n";

    $byAuthor = $articleModel->findByAuthor('Ahmad Hassan');
    echo "   Articles by Ahmad Hassan:\n";
    foreach ($byAuthor as $article) {
        echo "   - {$article->title}\n";
    }
    echo "\n";

    echo SEP;
    echo "4. Update and Delete:\n";

    $first = $articleModel->findById(1);
    $first->title = 'Updated: Introduction to WebFiori';
    $first->save();
    echo "   ✓ Article updated\n";

    $first->id = 2;
    $first->deleteById();
    echo "   ✓ Article deleted\n";

    echo "\n   Remaining articles:\n";
    foreach ($articleModel->findAll() as $article) {
        echo "   - [{$article->id}] {$article->title}\n";
    }
    echo "\n";

    echo SEP;
    echo "5. Cleanup:\n";
    $database->table('articles')->drop()->execute();
    echo "   ✓ Table dropped\n";

} catch (Exception $e) {
    echo "✗ Error: ".$e->getMessage()."\n";
    try {
        $database->table('articles')->drop(true)->execute();
    } catch (Exception $cleanupError) {}
}

echo "\n" . SEP;
echo "=== Example Complete ===\n";
