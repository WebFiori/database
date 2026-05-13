# Database Migrations

This example demonstrates how to create and run database migrations using WebFiori's migration system.

## What This Example Shows

- Creating migration classes that extend AbstractMigration
- Implementing up() and down() methods for schema changes
- Running migrations using SchemaRunner
- Rolling back migrations
- Connection-targeted migrations (restricting migrations to specific databases)

## Files

- [`example.php`](example.php) - Main example code
- [`CreateUsersTableMigration.php`](CreateUsersTableMigration.php) - Migration to create users table
- [`AddEmailIndexMigration.php`](AddEmailIndexMigration.php) - Migration to add email index
- [`CreateReportsTableMigration.php`](CreateReportsTableMigration.php) - Migration targeting a specific connection

## Running the Example

```bash
php example.php
```

## Expected Output

The example will create migration classes, run them to modify the database schema, and demonstrate rollback functionality.


## Related Examples

- [03-table-blueprints](../03-table-blueprints/) - Define table structures
- [07-seeders](../07-seeders/) - Populate data after migrations
- [05-transactions](../05-transactions/) - Understand rollback behavior
