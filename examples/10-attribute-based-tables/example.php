<?php

require_once '../../vendor/autoload.php';
require_once __DIR__.'/Author.php';
require_once __DIR__.'/Article.php';

use WebFiori\Database\Attributes\AttributeTableBuilder;
use WebFiori\Database\ConnectionInfo;
use WebFiori\Database\Database;

const SEP = "────────────────────────────────────────────────────────────────────\n";

echo "=== WebFiori Database Attribute-Based Tables Example ===\n\n";

try {
    $connection = new ConnectionInfo('mysql', 'root', '123456', 'testing_db');
    $database = new Database($connection);

    echo SEP;
    echo "1. Building Tables from Attributes:\n";

    $authorsTable = AttributeTableBuilder::build(Author::class, 'mysql');
    $articlesTable = AttributeTableBuilder::build(Article::class, 'mysql');

    echo "   ✓ Authors table blueprint created\n";
    echo "     Columns: ".implode(', ', array_keys($authorsTable->getCols()))."\n";

    echo "   ✓ Articles table blueprint created\n";
    echo "     Columns: ".implode(', ', array_keys($articlesTable->getCols()))."\n\n";

    echo SEP;
    echo "2. Generated SQL:\n";
    echo "   Authors table:\n   ".$authorsTable->toSQL()."\n\n";
    echo "   Articles table:\n   ".$articlesTable->toSQL()."\n\n";

    echo SEP;
    echo "3. Creating Tables:\n";

    $database->addTable($authorsTable);
    $database->addTable($articlesTable);

    $database->table('articles')->drop(true)->execute();
    $database->table('authors')->drop(true)->execute();
    $database->createTables();
    echo "   ✓ Tables created\n\n";

    echo SEP;
    echo "4. Inserting Test Data:\n";

    $database->table('authors')->insert(['name' => 'Ibrahim Ali', 'email' => 'ibrahim@example.com'])->execute();
    $database->table('authors')->insert(['name' => 'Sara Ahmed', 'email' => 'sara@example.com'])->execute();
    echo "   ✓ 2 authors inserted\n";

    $database->table('articles')->insert(['author-id' => 1, 'title' => 'Introduction to PHP 8 Attributes', 'content' => 'PHP 8 introduced attributes...'])->execute();
    $database->table('articles')->insert(['author-id' => 1, 'title' => 'Database Design Patterns', 'content' => 'Learn about patterns...'])->execute();
    $database->table('articles')->insert(['author-id' => 2, 'title' => 'Clean Architecture in PHP', 'content' => 'Implementing clean architecture...'])->execute();
    echo "   ✓ 3 articles inserted\n\n";

    echo SEP;
    echo "5. Querying Data:\n";

    $result = $database->raw("
        SELECT a.name as author, ar.title, ar.`published-at`
        FROM authors a JOIN articles ar ON a.id = ar.`author-id`
        ORDER BY ar.`published-at` DESC
    ")->execute();

    echo "   Articles with authors:\n";
    foreach ($result as $row) {
        echo "   - {$row['title']} by {$row['author']} ({$row['published-at']})\n";
    }
    echo "\n";

    echo SEP;
    echo "6. Cleanup:\n";
    $database->table('articles')->drop()->execute();
    $database->table('authors')->drop()->execute();
    echo "   ✓ Tables dropped\n";

} catch (Exception $e) {
    echo "✗ Error: ".$e->getMessage()."\n";
    try {
        $database->table('articles')->drop(true)->execute();
        $database->table('authors')->drop(true)->execute();
    } catch (Exception $cleanupError) {}
}

echo "\n" . SEP;
echo "=== Example Complete ===\n";
