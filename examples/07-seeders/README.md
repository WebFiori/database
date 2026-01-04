# Database Seeders

This example demonstrates how to create and run database seeders using WebFiori's seeder system.

## What This Example Shows

- Creating seeder classes that extend AbstractSeeder
- Implementing seed() method for data insertion
- Running seeders using SchemaRunner
- Environment-specific seeding

## Files

- `example.php` - Main example code
- `UsersSeeder.php` - Seeder for user data
- `CategoriesSeeder.php` - Seeder for category data

## Running the Example

```bash
php example.php
```

## Expected Output

The example will create seeder classes, run them to populate the database with initial data, and show the seeded records.


## Related Examples

- [06-migrations](../06-migrations/) - Create tables before seeding
- [05-transactions](../05-transactions/) - Wrap seeding in transactions
- [11-repository-pattern](../11-repository-pattern/) - Use repositories for data insertion
