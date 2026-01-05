<?php

require_once '../../vendor/autoload.php';
require_once __DIR__.'/Author.php';
require_once __DIR__.'/Article.php';

use WebFiori\Database\Attributes\AttributeTableBuilder;
use WebFiori\Database\ConnectionInfo;
use WebFiori\Database\Database;

echo "=== WebFiori Database Attribute-Based Tables Example ===\n\n";

try {
    $connection = new ConnectionInfo('mysql', 'root', '123456', 'mysql');
    $database = new Database($connection);

    echo "1. Building Tables from Attributes:\n";

    $authorsTable = AttributeTableBuilder::build(Author::class, 'mysql');
    $articlesTable = AttributeTableBuilder::build(Article::class, 'mysql');

    echo "✓ Authors table blueprint created\n";
    echo "  Columns: ".implode(', ', array_keys($authorsTable->getCols()))."\n";

    echo "✓ Articles table blueprint created\n";
    echo "  Columns: ".implode(', ', array_keys($articlesTable->getCols()))."\n\n";

    echo "2. Generated SQL:\n";
    echo "Authors table:\n".$authorsTable->toSQL()."\n\n";
    echo "Articles table:\n".$articlesTable->toSQL()."\n\n";

    echo "3. Creating Tables in Database:\n";

    $database->raw("DROP TABLE IF EXISTS articles")->execute();
    $database->raw("DROP TABLE IF EXISTS authors")->execute();

    $database->raw($authorsTable->toSQL())->execute();
    echo "✓ Authors table created\n";

    $database->raw($articlesTable->toSQL())->execute();
    echo "✓ Articles table created\n\n";

    echo "4. Inserting Test Data:\n";

    $database->addTable($authorsTable);
    $database->addTable($articlesTable);

    $database->table('authors')->insert(['name' => 'Ibrahim Ali', 'email' => 'ibrahim@example.com'])->execute();
    $database->table('authors')->insert(['name' => 'Sara Ahmed', 'email' => 'sara@example.com'])->execute();
    echo "✓ Authors inserted\n";

    $database->table('articles')->insert(['author-id' => 1, 'title' => 'Introduction to PHP 8 Attributes', 'content' => 'PHP 8 introduced attributes...'])->execute();
    $database->table('articles')->insert(['author-id' => 1, 'title' => 'Database Design Patterns', 'content' => 'Learn about patterns...'])->execute();
    $database->table('articles')->insert(['author-id' => 2, 'title' => 'Clean Architecture in PHP', 'content' => 'Implementing clean architecture...'])->execute();
    echo "✓ Articles inserted\n\n";

    echo "5. Querying Data:\n";

    $result = $database->raw("
        SELECT a.name as author, ar.title, ar.`published-at`
        FROM authors a JOIN articles ar ON a.id = ar.`author-id`
        ORDER BY ar.`published-at` DESC
    ")->execute();

    echo "Articles with authors:\n";
    foreach ($result as $row) {
        echo "  - {$row['title']} by {$row['author']} ({$row['published-at']})\n";
    }
    echo "\n";

    echo "6. Cleanup:\n";
    $database->raw("DROP TABLE articles")->execute();
    $database->raw("DROP TABLE authors")->execute();
    echo "✓ Tables dropped\n";
} catch (Exception $e) {
    echo "✗ Error: ".$e->getMessage()."\n";
    try {
        $database->raw("DROP TABLE IF EXISTS articles")->execute();
        $database->raw("DROP TABLE IF EXISTS authors")->execute();
    } catch (Exception $cleanupError) {}
}

echo "\n=== Example Complete ===\n";
