<?php

require_once '../../vendor/autoload.php';
require_once __DIR__.'/AuthorRepository.php';
require_once __DIR__.'/PostRepository.php';
require_once __DIR__.'/CommentRepository.php';

use WebFiori\Database\Attributes\AttributeTableBuilder;
use WebFiori\Database\ColOption;
use WebFiori\Database\ConnectionInfo;
use WebFiori\Database\Database;
use WebFiori\Database\DataType;

const SEP = "────────────────────────────────────────────────────────────────────\n";

echo "=== Eager Loading Example ===\n\n";
echo "This example demonstrates how to avoid N+1 queries using eager loading.\n\n";

try {
    $connection = new ConnectionInfo('mysql', 'root', '123456', 'testing_db');
    $database = new Database($connection);

    // Setup tables
    echo SEP;
    echo "1. Creating Tables:\n";

    $database->addTable(AttributeTableBuilder::build(AuthorsTable::class, 'mysql'));
    $database->addTable(AttributeTableBuilder::build(PostsTable::class, 'mysql'));
    $database->addTable(AttributeTableBuilder::build(CommentsTable::class, 'mysql'));

    $database->table('comments')->drop(true)->execute();
    $database->table('posts')->drop(true)->execute();
    $database->table('authors')->drop(true)->execute();

    $database->createTables();
    echo "✓ Tables created\n\n";

    // Seed data
    echo SEP;
    echo "2. Seeding Data:\n";
    $database->table('authors')->insert([
        'cols' => [
            'name'
        ],
        'values' => [
            ['Ahmad Hassan'],
            ['Fatima Ali'],
            ['Omar Khalid']
        ]
    ])->execute();

    $database->table('posts')->insert([
        'cols' => [
            'title', 'author-id'
        ],
        'values' => [
            ['Introduction to PHP', 1],
            ['Database Design', 1],
            ['Clean Architecture', 1],
            ['Web Security', 2],
            ['API Design', 2]
        ]
    ])->execute();

    echo "✓ 3 authors and 5 posts created\n\n";

    $authorRepo = new AuthorRepository($database);
    $postRepo = new PostRepository($database);

    // =============================================
    // WITHOUT Eager Loading (N+1 Problem)
    // =============================================
    echo SEP;
    echo "3. WITHOUT Eager Loading (N+1 Problem):\n";
    echo "   If we fetched posts for each author separately:\n";
    echo "   - 1 query to get all authors\n";
    echo "   - N queries to get posts for each author (3 more queries)\n";
    echo "   - Total: 4 queries for 3 authors\n\n";

    // =============================================
    // WITH Eager Loading - HasMany
    // =============================================
    echo SEP;
    echo "4. WITH Eager Loading - HasMany (Author -> Posts):\n";
    echo "   Using: \$authorRepo->with(['posts'])->findAll()\n\n";

    $authors = $authorRepo->with(['posts'])->findAll();

    foreach ($authors as $author) {
        echo "   {$author->name} ({$author->id}):\n";
        if (empty($author->posts)) {
            echo "     - No posts\n";
        } else {
            foreach ($author->posts as $post) {
                echo "     - {$post->title}\n";
            }
        }
    }
    echo "\n   ✓ Only 2 queries executed (1 for authors + 1 for all posts)\n\n";

    // =============================================
    // WITH Eager Loading - String syntax
    // =============================================
    echo SEP;
    echo "5. WITH Eager Loading - String syntax (single relation):\n";
    echo "   Using: \$postRepo->with('author')->findAll()\n\n";

    $posts = $postRepo->with('author')->findAll();

    foreach ($posts as $post) {
        echo "   \"{$post->title}\" by {$post->author->name}\n";
    }
    echo "\n   ✓ String syntax works for single relation\n\n";

    // =============================================
    // WITH Eager Loading - Multiple relations
    // =============================================
    echo SEP;
    echo "6. WITH Eager Loading - Multiple relations:\n";
    echo "   Using: \$postRepo->with(['author', 'comments'])->findAll()\n\n";

    $posts = $postRepo->with(['author', 'comments'])->findAll();

    foreach ($posts as $post) {
        $commentCount = count($post->comments);
        echo "   \"{$post->title}\" by {$post->author->name} - {$commentCount} comments\n";
    }
    echo "\n   ✓ 3 queries executed (1 for posts + 1 for authors + 1 for comments)\n\n";

    // =============================================
    // WITH JOIN Loading - BelongsTo (single query)
    // =============================================
    echo SEP;
    echo "7. WITH JOIN Loading - BelongsTo using withJoin() (1 query):\n";
    echo "   Using: \$postRepo->withJoin('author')->findAll()\n\n";

    $posts = $postRepo->withJoin('author')->findAll();

    foreach ($posts as $post) {
        echo "   \"{$post->title}\" by {$post->author->name}\n";
    }
    echo "\n   ✓ Only 1 query executed (JOIN)\n\n";

    // =============================================
    // withJoin() prevents hasMany (cartesian product protection)
    // =============================================
    echo SEP;
    echo "8. withJoin() prevents hasMany (cartesian product protection):\n";
    try {
        $authorRepo->withJoin('posts')->findAll();
        echo "   ✗ Should have thrown exception\n";
    } catch (\WebFiori\Database\Repository\RepositoryException $e) {
        echo "   ✓ Exception thrown: " . $e->getMessage() . "\n";
    }
    echo "\n";

    // =============================================
    // Eager Loading with findById
    // =============================================
    echo SEP;
    echo "9. Eager Loading with findById:\n";

    $author = $authorRepo->with('posts')->findById(1);
    echo "   Author: {$author->name}\n";
    echo "   Posts: " . count($author->posts) . "\n\n";

    // =============================================
    // Eager Loading with Pagination
    // =============================================
    echo SEP;
    echo "10. Eager Loading with Pagination:\n";

    $page = $authorRepo->with('posts')->paginate(page: 1, perPage: 2);
    echo "    Page 1 of " . $page->getTotalPages() . " (2 per page):\n";
    foreach ($page->getItems() as $author) {
        echo "    - {$author->name}: " . count($author->posts) . " posts\n";
    }
    echo "\n";

    // =============================================
    // stdClass Relationship (no entity specified)
    // =============================================
    echo SEP;
    echo "11. stdClass Relationship (no entity specified):\n";

    $database->table('comments')->insert([
        'cols' => ['content', 'post-id'],
        'values' => [
            ['Great article!', 1],
            ['Very helpful', 1],
            ['Thanks for sharing', 2]
        ]
    ])->execute();

    $commentRepo = new CommentRepository($database);
    $comments = $commentRepo->with('post')->findAll();

    echo "    Comments with related posts (stdClass):\n";
    foreach ($comments as $comment) {
        $postTitle = $comment->post->title ?? 'N/A';
        echo "    - \"{$comment->content}\" on post: \"{$postTitle}\"\n";
        echo "      post type: " . get_class($comment->post) . "\n";
    }
    echo "\n";

    // Cleanup
    echo SEP;
    echo "12. Cleanup:\n";
    $database->table('comments')->drop()->execute();
    $database->table('posts')->drop()->execute();
    $database->table('authors')->drop()->execute();
    echo "✓ Tables dropped\n";

} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    try {
        $database->table('comments')->drop(true)->execute();
        $database->table('posts')->drop(true)->execute();
        $database->table('authors')->drop(true)->execute();
    } catch (Exception $cleanupError) {}
}

echo "\n" . SEP;
echo "=== Example Complete ===\n";
