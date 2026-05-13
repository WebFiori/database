# Webfiori Database Abstraction Layer

Database abstraction layer of WebFiori framework.

<p style="text-align: center">
  <a href="https://github.com/WebFiori/database/actions">
    <img alt="PHP 8 Build Status" src="https://github.com/WebFiori/database/actions/workflows/php85.yaml/badge.svg?branch=main">
  </a>
  <a href="https://codecov.io/gh/WebFiori/database">
    <img alt="CodeCov" src="https://codecov.io/gh/WebFiori/database/branch/main/graph/badge.svg?token=cDF6CxGTFi" />
  </a>
  <a href="https://sonarcloud.io/dashboard?id=WebFiori_database">
      <img alt="Quality Checks" src="https://sonarcloud.io/api/project_badges/measure?project=WebFiori_database&metric=alert_status" />
  </a>
  <a href="https://github.com/WebFiori/database/releases">
      <img alt="Version" src="https://img.shields.io/github/release/WebFiori/database.svg?label=latest" />
  </a>
  <a href="https://packagist.org/packages/webfiori/database">
      <img alt="Downloads" src="https://img.shields.io/packagist/dt/webfiori/database?color=light-green">
  </a>
</p>

## Content 

* [Supported PHP Versions](#supported-php-versions)
* [Supported Databases](#supported-databases)
* [Features](#features)
* [Installation](#installation)
* [Usage](#usage)
  * [Connecting to Database](#connecting-to-database)
  * [Running Basic SQL Queries](#running-basic-sql-queries)
  * [Building Database Structure](#building-database-structure)
  * [Repository Pattern](#repository-pattern)
  * [Active Record Pattern](#active-record-pattern)
  * [Entity Generation](#entity-generation)
  * [Database Migrations](#database-migrations)
  * [Database Seeders](#database-seeders)
  * [Performance Monitoring](#performance-monitoring)
  * [Transactions](#transactions)

## Supported PHP Versions
|                                                                                           Build Status                                                                                            |
|:-------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------:|
| <a target="_blank" href="https://github.com/WebFiori/database/actions/workflows/php81.yaml"><img src="https://github.com/WebFiori/database/actions/workflows/php81.yaml/badge.svg?branch=main"></a> |
| <a target="_blank" href="https://github.com/WebFiori/database/actions/workflows/php82.yaml"><img src="https://github.com/WebFiori/database/actions/workflows/php82.yaml/badge.svg?branch=main"></a> |
| <a target="_blank" href="https://github.com/WebFiori/database/actions/workflows/php83.yaml"><img src="https://github.com/WebFiori/database/actions/workflows/php83.yaml/badge.svg?branch=main"></a> |
| <a target="_blank" href="https://github.com/WebFiori/database/actions/workflows/php84.yaml"><img src="https://github.com/WebFiori/database/actions/workflows/php84.yaml/badge.svg?branch=main"></a> |
| <a target="_blank" href="https://github.com/WebFiori/database/actions/workflows/php85.yaml"><img src="https://github.com/WebFiori/database/actions/workflows/php85.yaml/badge.svg?branch=main"></a> |

## Supported Databases
- MySQL
- MSSQL

## Features
* Building your database structure within PHP
* Fast and easy to use query builder
* Database abstraction which makes it easy to migrate your system to different DBMS
* Repository pattern with `AbstractRepository` for clean data access
* Active Record pattern support for rapid development
* PHP 8 attributes for table definitions
* Database migrations and seeders
* Performance monitoring and query analysis
* Entity generation for object-relational mapping
* Transaction support with automatic rollback

## Installation
To install the library using composer, add following dependency to `composer.json`: `"webfiori/database":"*"`

## Usage

### Connecting to Database

Connecting to a database is simple. First step is to define database connection information using the class `ConnectionInfo`. Later, the instance can be used to establish a connection to the database using the class `Database`.

```php
use WebFiori\Database\ConnectionInfo;
use WebFiori\Database\Database;

$connection = new ConnectionInfo('mysql', 'root', '123456', 'testing_db');
$database = new Database($connection);
```

### Running Basic SQL Queries

For every query, the table must be specified using `Database::table(string $tblName)`. The method returns an `AbstractQuery` instance with methods for building queries:

* `insert(array $cols)`: Construct an insert query.
* `select(array $cols)`: Construct a select query.
* `update(array $cols)`: Construct an update query.
* `delete()`: Construct a delete query.
* `where($col, $val)`: Adds a condition to the query.

After building the query, call `execute()` to run it.

```php
// Insert
$database->table('posts')->insert([
    'title' => 'Super New Post',
    'author' => 'Me'
])->execute();

// Select
$resultSet = $database->table('posts')
    ->select()
    ->where('author', 'Ibrahim')
    ->execute();

foreach ($resultSet as $record) {
    echo $record['title'];
}

// Update
$database->table('posts')->update([
    'title' => 'Updated Title',
])->where('id', 1)->execute();

// Delete
$database->table('posts')->delete()->where('id', 1)->execute();
```

### Building Database Structure

Define database structure in PHP code using blueprints:

```php
use WebFiori\Database\ColOption;
use WebFiori\Database\DataType;

$database->createBlueprint('users')->addColumns([
    'id' => [
        ColOption::TYPE => DataType::INT,
        ColOption::PRIMARY => true,
        ColOption::AUTO_INCREMENT => true
    ],
    'name' => [
        ColOption::TYPE => DataType::VARCHAR,
        ColOption::SIZE => 100
    ],
    'email' => [
        ColOption::TYPE => DataType::VARCHAR,
        ColOption::SIZE => 150
    ]
]);

// Create the table
$database->table('users')->createTable()->execute();
```

You can also register tables from classes that use `#[Table]`/`#[Column]` attributes or extend `Table`:

```php
// Single class
$database->addTableFromClass(Users::class);

// Multiple classes at once
$database->addTablesFromClasses([Users::class, Posts::class, Comments::class]);
```

This works with both attribute-based classes and `Table` subclasses (`MySQLTable`/`MSSQLTable`). If the class engine differs from the connection, it is converted automatically.

### Repository Pattern

The `AbstractRepository` class provides a clean way to handle data access with separation between entities and database logic.

#### Creating an Entity

```php
class Product {
    public ?int $id = null;
    public string $name;
    public float $price;
}
```

#### Creating a Repository

```php
use WebFiori\Database\Repository\AbstractRepository;

class ProductRepository extends AbstractRepository {
    protected function getTableName(): string {
        return 'products';
    }

    protected function getIdField(): string {
        return 'id';
    }

    protected function toEntity(array $row): object {
        $product = new Product();
        $product->id = (int) $row['id'];
        $product->name = $row['name'];
        $product->price = (float) $row['price'];
        return $product;
    }

    protected function toArray(object $entity): array {
        return [
            'id' => $entity->id,
            'name' => $entity->name,
            'price' => $entity->price
        ];
    }
}
```

#### Using the Repository

```php
$repo = new ProductRepository($database);

// Create
$product = new Product();
$product->name = 'Widget';
$product->price = 29.99;
$repo->save($product);

// Read
$product = $repo->findById(1);
$allProducts = $repo->findAll();

// Update
$product->price = 24.99;
$repo->save($product);

// Delete
$repo->deleteById(1);

// Pagination
$page = $repo->paginate(page: 1, perPage: 20);
```

### Active Record Pattern

For rapid development, you can merge entity and repository into a single model class:

```php
use WebFiori\Database\Attributes\Column;
use WebFiori\Database\Attributes\Table;
use WebFiori\Database\DataType;
use WebFiori\Database\Repository\AbstractRepository;

#[Table(name: 'articles')]
class Article extends AbstractRepository {
    #[Column(type: DataType::INT, primary: true, autoIncrement: true)]
    public ?int $id = null;

    #[Column(type: DataType::VARCHAR, size: 200)]
    public string $title = '';

    #[Column(type: DataType::TEXT)]
    public string $content = '';

    protected function getTableName(): string { return 'articles'; }
    protected function getIdField(): string { return 'id'; }
    
    protected function toEntity(array $row): object {
        $article = new self($this->db);
        $article->id = (int) $row['id'];
        $article->title = $row['title'];
        $article->content = $row['content'];
        return $article;
    }

    protected function toArray(object $entity): array {
        return [
            'id' => $entity->id,
            'title' => $entity->title,
            'content' => $entity->content
        ];
    }
}
```

Usage:

```php
// Create and save
$article = new Article($database);
$article->title = 'Hello World';
$article->content = 'My first article';
$article->save();

// Query
$all = $article->findAll();
$one = $article->findById(1);

// Update
$article->title = 'Updated Title';
$article->save();

// Delete
$article->deleteById();

// Reload from database
$fresh = $article->reload();
```

### Entity Generation

Generate entity classes from table blueprints:

```php
$blueprint = $database->getTable('users');

$generator = $blueprint->getEntityGenerator('User', __DIR__, 'App\\Entity');
$generator->generate();
```

### Database Migrations

Version control your database schema changes:

```php
use WebFiori\Database\Schema\AbstractMigration;

class CreateUsersTable extends AbstractMigration {
    public function up(Database $db): void {
        $db->createBlueprint('users')->addColumns([
            'id' => [ColOption::TYPE => DataType::INT, ColOption::PRIMARY => true, ColOption::AUTO_INCREMENT => true],
            'name' => [ColOption::TYPE => DataType::VARCHAR, ColOption::SIZE => 100]
        ]);
        $db->table('users')->createTable()->execute();
    }
    
    public function down(Database $db): void {
        $db->raw("DROP TABLE users")->execute();
    }
}
```

Run migrations:

```php
use WebFiori\Database\Schema\SchemaRunner;

$runner = new SchemaRunner($connectionInfo);
$runner->discoverFromPath(__DIR__ . '/migrations', 'App\\Migrations');
$runner->createSchemaTable();
$runner->apply();
```


#### Connection-Targeted Migrations

In multi-database architectures, you can restrict a migration to specific named connections. This is useful when different databases serve different purposes (e.g., one for user data, another for reporting):

```php
class CreateReportsTable extends AbstractMigration {
    public function getTargetConnections(): array {
        return ['reporting-db']; // Only runs against the 'reporting-db' connection
    }

    public function up(Database $db): void {
        // ...
    }

    public function down(Database $db): void {
        // ...
    }
}
```

Migrations with an empty `getTargetConnections()` (the default) run on all connections. The connection name comes from `ConnectionInfo::getName()`.

### Database Seeders

Populate your database with sample data:

```php
use WebFiori\Database\Schema\AbstractSeeder;

class UsersSeeder extends AbstractSeeder {
    public function run(Database $db): void {
        $db->table('users')->insert([
            'name' => 'Administrator',
            'email' => 'admin@example.com'
        ])->execute();
    }
}
```

### Performance Monitoring

Track and analyze query performance:

```php
use WebFiori\Database\Performance\PerformanceOption;

$database->setPerformanceConfig([
    PerformanceOption::ENABLED => true,
    PerformanceOption::SLOW_QUERY_THRESHOLD => 50
]);

// Execute queries...

$analyzer = $database->getPerformanceMonitor()->getAnalyzer();
echo "Total queries: " . $analyzer->getQueryCount();
echo "Slow queries: " . $analyzer->getSlowQueryCount();
```

### Transactions

Execute multiple operations as a single unit:

```php
$database->transaction(function (Database $db) {
    $db->table('users')->insert(['name' => 'John'])->execute();
    $db->table('profiles')->insert([
        'user_id' => $db->getLastInsertId(),
        'bio' => 'Developer'
    ])->execute();
});
```

## Examples

See the [examples](examples/) directory for complete working examples:

- [01-basic-connection](examples/01-basic-connection/) - Database connections
- [02-basic-queries](examples/02-basic-queries/) - CRUD operations
- [03-table-blueprints](examples/03-table-blueprints/) - Table structures
- [04-entity-mapping](examples/04-entity-mapping/) - Entity generation
- [05-transactions](examples/05-transactions/) - Transaction handling
- [06-migrations](examples/06-migrations/) - Schema migrations
- [07-seeders](examples/07-seeders/) - Data seeding
- [08-performance-monitoring](examples/08-performance-monitoring/) - Query analysis
- [09-multi-result-queries](examples/09-multi-result-queries/) - Stored procedures
- [10-attribute-based-tables](examples/10-attribute-based-tables/) - PHP 8 attributes
- [11-repository-pattern](examples/11-repository-pattern/) - Repository pattern
- [12-clean-architecture](examples/12-clean-architecture/) - Domain separation
- [13-pagination](examples/13-pagination/) - Pagination techniques
- [14-active-record-model](examples/14-active-record-model/) - Active Record pattern
