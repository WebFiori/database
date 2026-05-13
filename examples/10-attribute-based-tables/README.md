# Attribute-Based Tables

This example demonstrates how to define database tables using PHP 8 attributes.

## What This Example Shows

- Using `#[Table]` attribute for table definition
- Using `#[Column]` attribute for column properties
- Using `#[ForeignKey]` attribute for relationships
- Building tables with `AttributeTableBuilder`
- Registering tables with `addTableFromClass()` and `addTablesFromClasses()`

## Files

- [`example.php`](example.php) - Main example code
- [`Author.php`](Author.php) - Author table definition with attributes
- [`Article.php`](Article.php) - Article table definition with attributes and foreign key

## Running the Example

```bash
php example.php
```

## Expected Output

The example will define entity classes with attributes, build table blueprints from them, and create the tables in the database.

## Related Examples

- [03-table-blueprints](../03-table-blueprints/) - Traditional blueprint approach
- [04-entity-mapping](../04-entity-mapping/) - Entity class generation
- [12-clean-architecture](../12-clean-architecture/) - Use attributes with clean architecture
