# Attribute-Based Tables

This example demonstrates how to define database tables using PHP 8 attributes.

## What This Example Shows

- Using `#[Table]` attribute for table definition
- Using `#[Column]` attribute for column properties
- Using `#[ForeignKey]` attribute for relationships
- Building tables with `AttributeTableBuilder`

## Files

- `example.php` - Main example code with entity classes

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
