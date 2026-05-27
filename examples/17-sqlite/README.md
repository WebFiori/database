# SQLite Database

This example demonstrates using the WebFiori Database Abstraction Layer with SQLite — no external database server required.

## What This Example Shows

- Connecting to an in-memory SQLite database (`:memory:`)
- Connecting to a file-based SQLite database
- Creating tables with INTEGER PRIMARY KEY AUTOINCREMENT
- Full CRUD operations (Create, Read, Update, Delete)
- WHERE clauses, aggregates (COUNT, MAX, MIN)
- Pagination with LIMIT/OFFSET
- Transactions with automatic rollback on failure
- Type affinity mapping (INT→integer, VARCHAR→text, DECIMAL→real)

## Files

- [`example.php`](example.php) - Complete SQLite usage example

## Running the Example

```bash
php example.php
```

No database server needed — SQLite runs in-process.

## SQLite Connection

```php
use WebFiori\Database\ConnectionInfo;
use WebFiori\Database\Database;

// In-memory (data lost when connection closes)
$conn = new ConnectionInfo('sqlite', '', '', ':memory:');

// File-based (persistent)
$conn = new ConnectionInfo('sqlite', '', '', '/path/to/database.db');

$db = new Database($conn);
```

The `user` and `password` parameters are ignored for SQLite. The `dbname` parameter is the file path or `:memory:`.

## Type Mapping

SQLite uses type affinity. All types are automatically mapped:

| PHP DataType Constant | SQLite Affinity |
|---|---|
| `DataType::INT`, `DataType::BIGINT` | `integer` |
| `DataType::FLOAT`, `DataType::DECIMAL`, `DataType::DOUBLE` | `real` |
| `DataType::VARCHAR`, `DataType::TEXT`, `DataType::DATETIME` | `text` |
| `DataType::BLOB`, `DataType::BINARY` | `blob` |
| `DataType::BOOL` | `integer` (0/1) |

## SQLite-Specific Notes

- **Auto-increment**: Use `ColOption::AUTO_INCREMENT => true` — generates `INTEGER PRIMARY KEY AUTOINCREMENT`
- **Foreign keys**: Enforced via `PRAGMA foreign_keys = ON` (set automatically on connect)
- **Booleans**: Stored as integers (0/1)
- **Dates**: Stored as TEXT in ISO-8601 format
- **No ALTER TABLE MODIFY**: Column type changes require table recreation
- **Concurrent writes**: File-level locking; best for dev/testing/single-writer apps

## Related Examples

- [01-basic-connection](../01-basic-connection/) - MySQL/MSSQL connections
- [02-basic-queries](../02-basic-queries/) - CRUD operations
- [05-transactions](../05-transactions/) - Transaction handling
- [10-attribute-based-tables](../10-attribute-based-tables/) - PHP 8 attributes (works with SQLite)
