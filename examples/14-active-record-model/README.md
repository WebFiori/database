# Active Record Model

This example demonstrates merging Entity and Repository into a single Model class, similar to the Active Record pattern used in many MVC frameworks.

## Concept

Instead of separating Entity and Repository:

```
Separate (Repository Pattern):
├── Product.php        (Entity - data only)
└── ProductRepository.php  (Repository - database operations)

Merged (Active Record):
└── Article.php        (Model - data + database operations)
```

## How It Works

The `Article` class:
1. **Extends `AbstractRepository`** - Inherits all CRUD operations
2. **Uses `#[Table]` and `#[Column]` attributes** - Defines table structure
3. **Has public properties** - Holds entity data
4. **Implements mapping methods** - `toEntity()` and `toArray()`

```php
#[Table(name: 'articles')]
class Article extends AbstractRepository {
    #[Column(type: DataType::INT, primary: true, autoIncrement: true)]
    public ?int $id = null;

    #[Column(type: DataType::VARCHAR, size: 200)]
    public string $title = '';
    
    // ... more properties
}
```

## Usage

```php
// Create and save directly
$article = new Article($database);
$article->title = 'My Article';
$article->content = 'Content here...';
$article->save();  // Saves itself

// Query using any instance
$articles = $article->findAll();
$one = $article->findById(1);

// Update
$one->title = 'Updated Title';
$one->save();
```

## Trade-offs

| Approach | Pros | Cons |
|----------|------|------|
| **Merged (Active Record)** | Simple, less files, familiar to MVC developers | Harder to test, mixed responsibilities |
| **Separate (Repository)** | Testable, flexible, clean separation | More files, more boilerplate |

Choose based on project complexity:
- **Small projects** → Active Record is simpler
- **Large projects** → Repository pattern scales better

## Files

- [`example.php`](example.php) - Main example code
- [`Article.php`](Article.php) - Model class with attributes

## Running the Example

```bash
php example.php
```

## Related Examples

- [10-attribute-based-tables](../10-attribute-based-tables/) - Using attributes for table definition
- [11-repository-pattern](../11-repository-pattern/) - Separate Entity + Repository approach
- [12-clean-architecture](../12-clean-architecture/) - Full separation with domain layer
