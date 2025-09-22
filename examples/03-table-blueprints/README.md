# Table Blueprints

This example demonstrates how to create database table structures using WebFiori's blueprint system.

## What This Example Shows

- Creating table blueprints with columns and constraints
- Using different data types and column options
- Creating tables from blueprints
- Adding foreign key relationships
- **Extending MySQLTable class to create custom table blueprints**

## Files

- `example.php` - Main example code
- `UserTable.php` - Custom table class extending MySQLTable

## Running the Example

```bash
php example.php
```

## Expected Output

The example will create table blueprints using both the fluent API and custom table classes, generate SQL statements, and create the actual tables in the database.
