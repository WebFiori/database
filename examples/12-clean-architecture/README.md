# Clean Architecture

This example demonstrates how to implement clean architecture with separation between Domain and Infrastructure layers.

## What This Example Shows

- Pure domain entities (no framework dependencies)
- Repository interface in Domain layer
- Database implementation in Infrastructure layer
- Dependency inversion principle

## Files

- `example.php` - Main example code
- `Domain/User.php` - Domain entity
- `Domain/UserRepositoryInterface.php` - Repository contract
- `Infrastructure/Repository/MySQLUserRepository.php` - Database implementation

## Running the Example

```bash
php example.php
```

## Expected Output

The example will demonstrate how domain entities remain framework-agnostic while infrastructure handles database operations.

## Related Examples

- [11-repository-pattern](../11-repository-pattern/) - Repository basics
- [04-entity-mapping](../04-entity-mapping/) - Entity class concepts
- [10-attribute-based-tables](../10-attribute-based-tables/) - Combine with attribute-based definitions
