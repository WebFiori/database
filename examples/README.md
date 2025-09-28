# WebFiori Database Examples

This directory contains practical examples demonstrating how to use the WebFiori Database Abstraction Layer.

## Examples Overview

1. **[01-basic-connection](01-basic-connection/)** - How to establish database connections
2. **[02-basic-queries](02-basic-queries/)** - CRUD operations (Insert, Select, Update, Delete)
3. **[03-table-blueprints](03-table-blueprints/)** - Creating and managing database table structures
4. **[04-entity-mapping](04-entity-mapping/)** - Working with entity classes and object mapping
5. **[05-transactions](05-transactions/)** - Database transactions for data integrity
6. **[06-migrations](06-migrations/)** - Database schema migrations and versioning
7. **[07-seeders](07-seeders/)** - Database data seeding and population
8. **[08-performance-monitoring](08-performance-monitoring/)** - Query performance tracking and analysis

## Prerequisites

- PHP 8.1 or higher
- MySQL or MSSQL database server
- Composer dependencies installed

## Running Examples

Each example is self-contained and can be run independently:

```bash
cd examples/01-basic-connection
php example.php
```

## Database Setup

Most examples assume a MySQL database with the following configuration:
- Host: localhost
- Username: root
- Password: 123456
- Database: mysql (using system database for examples)

You can modify the connection parameters in each example as needed.

## Example Features Demonstrated

### 01-basic-connection
- Creating `ConnectionInfo` objects
- Establishing database connections
- Testing connections with simple queries

### 02-basic-queries
- Table creation and management
- INSERT operations with data validation
- SELECT operations with filtering and conditions
- UPDATE operations with WHERE clauses
- DELETE operations with conditions
- Query result handling

### 03-table-blueprints
- Creating table blueprints with column definitions
- Using different data types (INT, VARCHAR, TEXT, TIMESTAMP)
- Setting column constraints (PRIMARY KEY, NOT NULL, AUTO_INCREMENT)
- Creating foreign key relationships
- Generating and executing CREATE TABLE statements

### 04-entity-mapping
- Generating entity classes from table blueprints
- Mapping database records to PHP objects
- Working with mapped objects and their methods
- Filtering and manipulating object collections

### 05-transactions
- Creating database transactions for data integrity
- Handling successful transaction commits
- Automatic rollback on transaction failures
- Error handling within transactions
- Complex multi-table operations

### 06-migrations
- Creating migration classes extending `AbstractMigration`
- Using `SchemaRunner` for migration management
- Registering migrations with the schema runner
- Applying and rolling back migrations
- Schema change tracking and versioning

### 07-seeders
- Creating seeder classes extending `AbstractSeeder`
- Using `SchemaRunner` for seeder management
- Registering seeders with the schema runner
- Populating database with sample data
- Environment-specific seeding

### 08-performance-monitoring
- Configuring performance monitoring settings
- Tracking query execution times and statistics
- Identifying slow queries and performance bottlenecks
- Using `PerformanceAnalyzer` for detailed analysis
- Performance optimization recommendations

## Notes

- All examples include proper error handling and cleanup
- Generated files (like entity classes) are automatically cleaned up
- Examples use temporary tables that are dropped after execution
- Each example is thoroughly tested and produces expected output
