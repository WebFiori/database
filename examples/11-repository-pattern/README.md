# Repository Pattern

This example demonstrates how to use the Repository pattern with `AbstractRepository` for data access.

## Entity + Repository = Model

In traditional MVC frameworks, a "Model" class often combines data and database logic together (Active Record pattern). The Repository pattern separates these into two focused classes:

| Component | Responsibility |
|-----------|----------------|
| **Entity** (`Product`) | Plain data object holding state. No database logic. |
| **Repository** (`ProductRepository`) | Handles all database operations for that entity. |

```
Traditional MVC Model = Entity + Repository
```

## Why Use This Approach?

1. **Single Responsibility** - Each class has one job. Entities hold data, repositories handle persistence.

2. **Testability** - Entities can be unit tested without a database. Repositories can be mocked in service tests.

3. **Flexibility** - Swap database implementations without changing entity code. Easy to switch from MySQL to PostgreSQL or add caching.

4. **Clean Domain Logic** - Entities stay focused on business rules, not database concerns.

5. **Reusable Queries** - Common queries live in the repository and can be reused across the application.

## What This Example Shows

- Extending `AbstractRepository` for CRUD operations
- Implementing `toEntity()` and `toArray()` for mapping
- Using built-in methods: `findAll()`, `findById()`, `save()`, `deleteById()`
- Creating custom query methods (`findByCategory()`, `findLowStock()`)
- Pagination with `paginate()`

## Files

- [`example.php`](example.php) - Main example code
- [`Product.php`](Product.php) - Product entity class
- [`ProductRepository.php`](ProductRepository.php) - Repository extending AbstractRepository

## Running the Example

```bash
php example.php
```

## Expected Output

The example will demonstrate all repository operations including create, read, update, delete, and pagination.

## Related Examples

- [04-entity-mapping](../04-entity-mapping/) - Entity class basics
- [12-clean-architecture](../12-clean-architecture/) - Repository with domain separation
- [13-pagination](../13-pagination/) - Advanced pagination techniques
