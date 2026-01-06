# Eager Loading

This example demonstrates how to avoid the N+1 query problem using eager loading with the `with()` method.

## The N+1 Problem

Without eager loading, fetching related data causes N+1 queries:

```php
$authors = $authorRepo->findAll();  // 1 query

foreach ($authors as $author) {
    $posts = $postRepo->findByAuthorId($author->id);  // N queries!
}
// Total: 1 + N queries (bad!)
```

## Solution: Eager Loading

With eager loading, related data is fetched in batches:

```php
$authors = $authorRepo->with(['posts'])->findAll();
// Query 1: SELECT * FROM authors
// Query 2: SELECT * FROM posts WHERE author-id IN (1, 2, 3, ...)
// Total: 2 queries (good!)

foreach ($authors as $author) {
    // $author->posts is already populated!
    foreach ($author->posts as $post) {
        echo $post->title;
    }
}
```

## Architecture (Clean Separation)

```
Domain/                    # Pure entities - no DB knowledge
├── Author.php
└── Post.php

Infrastructure/            # Database concerns
├── AuthorsTable.php       # Table definition + relationships
├── PostsTable.php
├── AuthorRepository.php   # Links table to entity
└── PostRepository.php
```

## Defining Relationships

### HasMany (One-to-Many)

```php
#[Table(name: 'authors')]
#[HasMany(
    entity: Post::class,
    foreignKey: 'author-id',
    property: 'posts',
    table: 'posts'
)]
class AuthorsTable {}
```

### BelongsTo (Many-to-One)

```php
#[Table(name: 'posts')]
class PostsTable {
    #[Column(name: 'author-id', type: DataType::INT)]
    #[ForeignKey(table: AuthorsTable::class, column: 'id', property: 'author')]
    public int $authorId;
}
```

## Repository Setup

```php
class AuthorRepository extends AbstractRepository {
    // Link to table definition for relationship discovery
    protected function getTableClass(): string {
        return AuthorsTable::class;
    }

    // Map related rows to entities
    protected function relatedToEntity(string $relation, array $row): object {
        if ($relation === 'posts') {
            $post = new Post();
            $post->id = (int) $row['id'];
            $post->title = $row['title'];
            return $post;
        }
        return (object) $row;
    }
}
```

## Usage

```php
// HasMany: Author with their posts
$authors = $authorRepo->with(['posts'])->findAll();

// BelongsTo: Posts with their author
$posts = $postRepo->with(['author'])->findAll();

// Works with findById
$author = $authorRepo->with(['posts'])->findById(1);

// Works with pagination
$page = $authorRepo->with(['posts'])->paginate(1, 20);
```

## Files

- [`example.php`](example.php) - Main example demonstrating eager loading
- [`Author.php`](Author.php) - Domain entity
- [`Post.php`](Post.php) - Domain entity
- [`AuthorsTable.php`](AuthorsTable.php) - Table definition with HasMany
- [`PostsTable.php`](PostsTable.php) - Table definition with BelongsTo
- [`AuthorRepository.php`](AuthorRepository.php) - Repository implementation
- [`PostRepository.php`](PostRepository.php) - Repository implementation

## Running the Example

```bash
php example.php
```

## Query Comparison

| Approach | Authors | Posts per Author | Total Queries |
|----------|---------|------------------|---------------|
| N+1 (bad) | 100 | any | 101 |
| Eager loading | 100 | any | 2 |

## Related Examples

- [11-repository-pattern](../11-repository-pattern/) - Basic repository usage
- [12-clean-architecture](../12-clean-architecture/) - Domain/Infrastructure separation
- [13-pagination](../13-pagination/) - Pagination techniques
