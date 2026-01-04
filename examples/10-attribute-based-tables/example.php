<?php

require_once '../../vendor/autoload.php';

use WebFiori\Database\Attributes\AttributeTableBuilder;
use WebFiori\Database\Attributes\Column;
use WebFiori\Database\Attributes\ForeignKey;
use WebFiori\Database\Attributes\Table;
use WebFiori\Database\ConnectionInfo;
use WebFiori\Database\Database;
use WebFiori\Database\DataType;

echo "=== WebFiori Database Attribute-Based Tables Example ===\n\n";

// Define entity classes with PHP 8 attributes

#[Table(name: 'authors')]
class Author {
    #[Column(type: DataType::INT, primary: true, autoIncrement: true)]
    public ?int $id = null;

    #[Column(type: DataType::VARCHAR, size: 100)]
    public string $name;

    #[Column(type: DataType::VARCHAR, size: 150)]
    public string $email;

    #[Column(type: DataType::TIMESTAMP, default: 'current_timestamp')]
    public ?string $createdAt = null;
}

#[Table(name: 'articles')]
class Article {
    #[Column(type: DataType::INT, primary: true, autoIncrement: true)]
    public ?int $id = null;

    #[Column(type: DataType::INT)]
    #[ForeignKey(table: 'authors', column: 'id', name: 'fk_article_author', onUpdate: 'cascade', onDelete: 'cascade')]
    public int $authorId;

    #[Column(type: DataType::VARCHAR, size: 200)]
    public string $title;

    #[Column(type: DataType::TEXT)]
    public string $content;

    #[Column(type: DataType::TIMESTAMP, default: 'current_timestamp')]
    public ?string $publishedAt = null;
}

try {
    // Create connection
    $connection = new ConnectionInfo('mysql', 'root', '123456', 'mysql');
    $database = new Database($connection);

    echo "1. Building Tables from Attributes:\n";

    // Build table blueprints from entity classes
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

    // Clean up first
    $database->raw("DROP TABLE IF EXISTS articles")->execute();
    $database->raw("DROP TABLE IF EXISTS authors")->execute();

    // Create tables
    $database->raw($authorsTable->toSQL())->execute();
    echo "✓ Authors table created\n";

    $database->raw($articlesTable->toSQL())->execute();
    echo "✓ Articles table created\n\n";

    echo "4. Inserting Test Data:\n";

    // Add tables to database for query builder
    $database->addTable($authorsTable);
    $database->addTable($articlesTable);

    // Insert authors
    $database->table('authors')->insert([
        'name' => 'Ibrahim Ali',
        'email' => 'ibrahim@example.com'
    ])->execute();

    $database->table('authors')->insert([
        'name' => 'Sara Ahmed',
        'email' => 'sara@example.com'
    ])->execute();

    echo "✓ Authors inserted\n";

    // Insert articles
    $database->table('articles')->insert([
        'author-id' => 1,
        'title' => 'Introduction to PHP 8 Attributes',
        'content' => 'PHP 8 introduced attributes as a way to add metadata to classes...'
    ])->execute();

    $database->table('articles')->insert([
        'author-id' => 1,
        'title' => 'Database Design Patterns',
        'content' => 'Learn about common database design patterns...'
    ])->execute();

    $database->table('articles')->insert([
        'author-id' => 2,
        'title' => 'Clean Architecture in PHP',
        'content' => 'Implementing clean architecture principles...'
    ])->execute();

    echo "✓ Articles inserted\n\n";

    echo "5. Querying Data:\n";

    // Query with join
    $result = $database->raw("
        SELECT a.name as author, ar.title, ar.published_at
        FROM authors a
        JOIN articles ar ON a.id = ar.author_id
        ORDER BY ar.published_at DESC
    ")->execute();

    echo "Articles with authors:\n";
    foreach ($result as $row) {
        echo "  - {$row['title']} by {$row['author']} ({$row['published_at']})\n";
    }
    echo "\n";

    echo "6. Cleanup:\n";
    $database->raw("DROP TABLE articles")->execute();
    $database->raw("DROP TABLE authors")->execute();
    echo "✓ Tables dropped\n";
} catch (Exception $e) {
    echo "✗ Error: ".$e->getMessage()."\n";

    // Clean up on error
    try {
        $database->raw("DROP TABLE IF EXISTS articles")->execute();
        $database->raw("DROP TABLE IF EXISTS authors")->execute();
    } catch (Exception $cleanupError) {
        // Ignore
    }
}

echo "\n=== Example Complete ===\n";
