<?php

require_once __DIR__ . '/../../vendor/autoload.php';

use WebFiori\Database\ConnectionInfo;
use WebFiori\Database\ConnectionPool;
use WebFiori\Database\Database;

// Connection pool works automatically — no special setup needed.
// Every Database instance acquires connections from the shared pool.

$connInfo = new ConnectionInfo('mysql', 'root', '123456', 'my_db', 'localhost', 3306);

// First Database instance acquires a connection from the pool
$db1 = new Database($connInfo);
echo "Active connections: " . ConnectionPool::getInstance()->getActiveCount() . "\n"; // 1

// Release connection back to pool
$db1->close();
echo "Active: " . ConnectionPool::getInstance()->getActiveCount() . "\n"; // 0
echo "Idle: " . ConnectionPool::getInstance()->getIdleCount() . "\n";   // 1

// Next instance reuses the idle connection (no new handshake)
$db2 = new Database($connInfo);
echo "Active: " . ConnectionPool::getInstance()->getActiveCount() . "\n"; // 1
echo "Idle: " . ConnectionPool::getInstance()->getIdleCount() . "\n";   // 0

// Configure pool limits
ConnectionPool::getInstance()->setMaxTotal(50);   // Max 50 active connections
ConnectionPool::getInstance()->setMaxPerKey(10);  // Max 10 idle per host/db combo

// Clean up everything (useful in tests or shutdown)
ConnectionPool::getInstance()->closeAll();
echo "After closeAll - Active: " . ConnectionPool::getInstance()->getActiveCount() . "\n"; // 0
echo "After closeAll - Idle: " . ConnectionPool::getInstance()->getIdleCount() . "\n";   // 0
