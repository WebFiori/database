<?php

namespace WebFiori\Tests\Database\Common;

use PHPUnit\Framework\TestCase;
use WebFiori\Database\ColOption;
use WebFiori\Database\ConnectionInfo;
use WebFiori\Database\ConnectionPool;
use WebFiori\Database\Database;
use WebFiori\Database\DataType;

/**
 * Tests that stale bindings from abandoned queries do not corrupt subsequent
 * update queries or repository operations.
 */
class StaleBindingsTest extends TestCase {
    private Database $db;

    protected function setUp(): void {
        $conn = new ConnectionInfo('sqlite', '', '', ':memory:');
        $this->db = new Database($conn);
        $this->db->createBlueprint('users')->addColumns([
            'id' => [ColOption::TYPE => DataType::INT, ColOption::PRIMARY => true, ColOption::AUTO_INCREMENT => true],
            'name' => [ColOption::TYPE => DataType::VARCHAR, ColOption::SIZE => 100],
            'age' => [ColOption::TYPE => DataType::INT],
        ]);
        $this->db->table('users')->createTable()->execute();
        $this->db->table('users')->insert(['name' => 'Alice', 'age' => 30])->execute();
        $this->db->table('users')->insert(['name' => 'Bob', 'age' => 25])->execute();
    }

    protected function tearDown(): void {
        $this->db->close();
        ConnectionPool::reset();
    }

    public function testUpdateResetsBindingsFromAbandonedQuery() {
        // Abandon a select with stale binding
        $this->db->table('users')->select()->where('age', 999);

        // update() resets bindings before adding its own
        $this->db->table('users')->update(['name' => 'Alice Updated'])->where('id', 1)->execute();

        $result = $this->db->table('users')->select()->where('id', 1)->execute();
        $this->assertEquals('Alice Updated', $result->getRows()[0]['name']);
    }

    public function testUpdateAfterExceptionDoesNotCorrupt() {
        try {
            $this->db->table('users')->select()->where('age', 100);
            throw new \RuntimeException('crash');
        } catch (\RuntimeException $e) {
            // stale binding 100
        }

        // update() should work correctly despite stale bindings
        $this->db->table('users')->update(['name' => 'Bob Updated'])->where('id', 2)->execute();

        $result = $this->db->table('users')->select()->where('id', 2)->execute();
        $this->assertEquals('Bob Updated', $result->getRows()[0]['name']);
    }

    public function testDatabaseSelectClearsBindings() {
        // Abandon query via table()->select() chain
        $this->db->table('users')->select()->where('age', 999);

        // Database::select() calls clear() which resets bindings
        $result = $this->db->select(['*'])->where('name', 'Alice')->execute();
        $this->assertEquals(1, $result->getRowsCount());
    }

    public function testDatabaseDeleteClearsBindings() {
        // Abandon query
        $this->db->table('users')->select()->where('age', 999);

        // Database::delete() calls clear() which resets bindings
        $this->db->table('users');
        $this->db->delete()->where('name', 'Bob')->execute();

        $result = $this->db->table('users')->select()->execute();
        $this->assertEquals(1, $result->getRowsCount());
    }
}
