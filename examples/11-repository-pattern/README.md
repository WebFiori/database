# Repository Pattern

This example demonstrates how to use the Repository pattern with `AbstractRepository` for data access.

## What This Example Shows

- Extending `AbstractRepository` for CRUD operations
- Implementing `toEntity()` and `toArray()` methods
- Using built-in methods: `findAll()`, `findById()`, `save()`, `deleteById()`
- Creating custom query methods
- Pagination with `paginate()`

## Files

- `example.php` - Main example code with entity and repository classes

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
