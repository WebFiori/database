# Connection Pooling

This example demonstrates how to use the built-in connection pool to reuse database connections efficiently.

## What This Example Shows

- How the pool automatically reuses connections
- Configuring pool limits
- Explicitly releasing connections
- Cleaning up all connections

## Files

- [`example.php`](example.php) - Main example code

## How It Works

The `ConnectionPool` is a singleton that sits between `Database` instances and raw connections. When `Database::getConnection()` is called, it acquires a connection from the pool. When the `Database` object is destroyed or `close()` is called, the connection returns to the pool for reuse.

```
Database::getConnection() → ConnectionPool::acquire() → reuse idle or create new
Database::close()         → ConnectionPool::release() → return to idle pool
```

## Key Points

- **Automatic**: No code changes needed — existing `Database` usage benefits from pooling automatically
- **Configurable**: Adjust `maxTotal` and `maxPerKey` to match your environment
- **Safe**: Dead connections are detected and discarded on reuse
- **Test-friendly**: Call `ConnectionPool::reset()` in test tearDown to clean up

## Related Examples

- [01-basic-connection](../01-basic-connection/) - Basic database connection setup
- [06-migrations](../06-migrations/) - Schema migrations (benefits from pooling)
