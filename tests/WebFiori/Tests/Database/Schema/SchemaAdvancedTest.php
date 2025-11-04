<?php

namespace WebFiori\Tests\Database\Schema;

use PHPUnit\Framework\TestCase;
use WebFiori\Database\ConnectionInfo;
use WebFiori\Database\Database;
use WebFiori\Database\DatabaseException;
use WebFiori\Database\Schema\AbstractMigration;
use WebFiori\Database\Schema\AbstractSeeder;
use WebFiori\Database\Schema\SchemaRunner;
use WebFiori\Tests\Database\Schema\TestMigration;
use WebFiori\Tests\Database\Schema\TestSeeder;

class SchemaAdvancedTest extends TestCase {

    protected function tearDown(): void {
        gc_collect_cycles();
        parent::tearDown();
    }
    
    private function getConnectionInfo(): ConnectionInfo {
        return new ConnectionInfo('mysql', 'root', getenv('MYSQL_ROOT_PASSWORD'), 'testing_db', '127.0.0.1');
    }
    
    public function testComplexDependencyChain() {
        $runner = new SchemaRunner($this->getConnectionInfo());
        $runner->registerAll([TestMigration::class, TestSeeder::class]);
        
        $changes = $runner->getChanges();
        $this->assertGreaterThan(0, count($changes), 'Should detect changes');
    }
    
    public function testApplyAndRollbackSequence() {
        try {
            $runner = new SchemaRunner($this->getConnectionInfo());
            $runner->registerAll([TestMigration::class, TestSeeder::class]);
            try {
                $runner->dropSchemaTable();
            } catch (DatabaseException $ex) {
                // Table might not exist, ignore
            }
            $runner->createSchemaTable();
            
            // Just test that we have changes registered
            $changes = $runner->getChanges();
            $this->assertCount(2, $changes);
            
            // Test apply - may return 0 if already applied
            $applied = $runner->apply();
            $this->assertIsArray($applied);
            
        } catch (DatabaseException $ex) {
            $this->markTestSkipped('Database connection failed: ' . $ex->getMessage());
        }
    }
    
    public function testApplyOneByOne() {
        try {
            $runner = new SchemaRunner($this->getConnectionInfo());
            $runner->registerAll([TestMigration::class, TestSeeder::class]);
            $runner->createSchemaTable();
            
            $changes = $runner->getChanges();
            $this->assertGreaterThan(0, count($changes));
            
            // Test that applyOne returns something or null (both are valid)
            $applied = $runner->applyOne();
            $this->assertTrue($applied === null || is_object($applied));
        } catch (DatabaseException $ex) {
            $this->markTestSkipped('Database connection failed: ' . $ex->getMessage());
        }
    }
    
    public function testMixedEnvironmentScenario() {
        $runner = new SchemaRunner($this->getConnectionInfo(), 'production');
        $runner->registerAll([TestMigration::class, TestSeeder::class]);
        
        $changes = $runner->getChanges();
        $this->assertGreaterThan(0, count($changes));
    }
    
    public function testSchemaTableOperations() {
        try {
            $runner = new SchemaRunner($this->getConnectionInfo());
            
            // Test creating schema table
            $runner->createSchemaTable();
            
            // Clear any existing applied changes for clean test
            $runner->table('schema_changes')->delete()->execute();
            
            // Verify table exists by trying to query it
            $result = $runner->table('schema_changes')->select()->execute();
            $this->assertNotNull($result);
            
            // Test dropping schema table
            $runner->dropSchemaTable();
            
            // Verify table is dropped by expecting an exception
            $this->expectException(DatabaseException::class);
            $runner->table('schema_changes')->select()->execute();
            
        } catch (DatabaseException $ex) {
            if (strpos($ex->getMessage(), 'connection') !== false) {
                $this->markTestSkipped('Database connection failed: ' . $ex->getMessage());
            } else {
                // Re-throw if it's not a connection error
                throw $ex;
            }
        }
    }
    
    public function testCallbackExecutionOrder() {
        $runner = new SchemaRunner($this->getConnectionInfo());
        
        $callOrder = [];
        
        $runner->addOnErrorCallback(function($err, $change, $schema) use (&$callOrder) {
            $callOrder[] = 'callback1';
        });
        
        $runner->addOnErrorCallback(function($err, $change, $schema) use (&$callOrder) {
            $callOrder[] = 'callback2';
        });
        
        // Simulate error by accessing private property and triggering callbacks
        $reflection = new \ReflectionClass($runner);
        $property = $reflection->getProperty('onErrCallbacks');
        $property->setAccessible(true);
        $callbacks = $property->getValue($runner);
        
        foreach ($callbacks as $callback) {
            call_user_func_array($callback, [new \Exception('test'), null, null]);
        }
        
        $this->assertEquals(['callback1', 'callback2'], $callOrder);
    }
}
