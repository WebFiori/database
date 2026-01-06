# Clean Architecture

This example demonstrates clean architecture with separation between Domain and Infrastructure layers, using `AbstractRepository` and PHP 8 attributes.

## What This Example Shows

- Pure domain entities (no framework dependencies)
- Table definitions using PHP 8 attributes (`#[Table]`, `#[Column]`)
- Repository extending `AbstractRepository` for data access
- Building tables with `AttributeTableBuilder`

## Files

- [`example.php`](example.php) - Main example code
- [`Domain/User.php`](Domain/User.php) - Pure domain entity
- [`Infrastructure/Schema/UserTable.php`](Infrastructure/Schema/UserTable.php) - Table definition with attributes
- [`Infrastructure/Repository/UserRepository.php`](Infrastructure/Repository/UserRepository.php) - Repository using AbstractRepository

## Running the Example

```bash
php example.php
```

## Expected Output

The example demonstrates how domain entities remain framework-agnostic while infrastructure handles database operations using attributes and AbstractRepository.

## Related Examples

- [10-attribute-based-tables](../10-attribute-based-tables/) - More on PHP 8 attributes
- [11-repository-pattern](../11-repository-pattern/) - Repository basics
- [04-entity-mapping](../04-entity-mapping/) - Entity class concepts
