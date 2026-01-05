# WebFiori Database Examples

This directory contains practical examples demonstrating how to use the WebFiori Database Abstraction Layer.

## Examples Overview

| # | Example | Description |
|---|---------|-------------|
| 01 | [basic-connection](01-basic-connection/) | Establishing database connections |
| 02 | [basic-queries](02-basic-queries/) | CRUD operations (Insert, Select, Update, Delete) |
| 03 | [table-blueprints](03-table-blueprints/) | Creating and managing database table structures |
| 04 | [entity-mapping](04-entity-mapping/) | Working with entity classes and object mapping |
| 05 | [transactions](05-transactions/) | Database transactions for data integrity |
| 06 | [migrations](06-migrations/) | Database schema migrations and versioning |
| 07 | [seeders](07-seeders/) | Database data seeding and population |
| 08 | [performance-monitoring](08-performance-monitoring/) | Query performance tracking and analysis |
| 09 | [multi-result-queries](09-multi-result-queries/) | Multi-result query handling and stored procedures |
| 10 | [attribute-based-tables](10-attribute-based-tables/) | PHP 8 attributes for table definitions |
| 11 | [repository-pattern](11-repository-pattern/) | Repository pattern with AbstractRepository |
| 12 | [clean-architecture](12-clean-architecture/) | Clean architecture with domain/infrastructure separation |
| 13 | [pagination](13-pagination/) | Offset and cursor-based pagination |
| 14 | [active-record-model](14-active-record-model/) | Entity + Repository merged into single Model class |

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
- Using `raw()` method for SQL queries with parameters
- INSERT, SELECT, UPDATE, DELETE operations
- Multi-result queries with stored procedures

### 03-table-blueprints
- Creating table blueprints with `createBlueprint()`
- Using `ColOption` and `DataType` constants
- Setting column constraints (PRIMARY KEY, NOT NULL, AUTO_INCREMENT)
- Creating foreign key relationships with `addReference()`
- Generating and executing CREATE TABLE statements

### 04-entity-mapping
- Generating entity classes from table blueprints using `EntityMapper`
- Auto-generated getters/setters and `map()` method
- Mapping database records to PHP objects

### 05-transactions
- Creating database transactions with `transaction()` method
- Automatic commit on success
- Automatic rollback on exception
- Complex multi-table operations

### 06-migrations
- Creating migration classes extending `AbstractMigration`
- Implementing `up()` and `down()` methods
- Using `SchemaRunner` for migration management
- Applying migrations with `apply()`
- Rolling back with `rollbackUpTo()`
- Schema change tracking

### 07-seeders
- Creating seeder classes extending `AbstractSeeder`
- Implementing `run()` method
- Environment-specific seeding with `getEnvironments()`
- Using `SchemaRunner` for seeder management

### 08-performance-monitoring
- Configuring performance monitoring with `setPerformanceConfig()`
- Using `PerformanceOption` constants
- Tracking query execution times
- Using `PerformanceAnalyzer` for metrics
- Identifying slow queries

### 09-multi-result-queries
- Executing stored procedures returning multiple result sets
- Working with `MultiResultSet` objects
- Processing individual result sets

### 10-attribute-based-tables
- Using PHP 8 attributes: `#[Table]`, `#[Column]`, `#[ForeignKey]`
- Building tables with `AttributeTableBuilder::build()`
- Defining entities with attribute-based schema

### 11-repository-pattern
- Extending `AbstractRepository` for CRUD operations
- Implementing `toEntity()` and `toArray()` methods
- Using built-in methods: `findAll()`, `findById()`, `save()`, `deleteById()`
- Creating custom query methods
- Pagination with `paginate()`

### 13-pagination
- Offset-based pagination with `paginate()`
- Cursor-based pagination with `paginateByCursor()`
- Working with `Page` and `CursorPage` objects
- Pagination with ordering


### 12-clean-architecture
- Separating Domain from Infrastructure
- Pure domain entities (no framework dependencies)
- Repository interface in Domain layer
- Database implementation in Infrastructure layer
- Dependency inversion principle

### 14-active-record-model
- Merging Entity and Repository into a single Model class
- Using attributes to define table structure on the model
- Active Record pattern for simpler projects
- Trade-offs vs Repository pattern
