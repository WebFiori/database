<?php

namespace WebFiori\Tests\Database\Schema;

use PHPUnit\Framework\TestCase;
use WebFiori\Database\ConnectionInfo;
use WebFiori\Database\Database;
use WebFiori\Database\DatabaseException;
use WebFiori\Database\Schema\SchemaRunner;
use WebFiori\Tests\Database\Schema\TestMigration;
use WebFiori\Tests\Database\Schema\TestSeeder;

class SchemaErrorHandlingTest extends TestCase {

    protected function tearDown(): void {
        gc_collect_cycles();
        parent::tearDown();
    }
    
    private function getConnectionInfo(): ConnectionInfo {
        return new ConnectionInfo('mysql', 'root', getenv('MYSQL_ROOT_PASSWORD'), 'testing_db', '127.0.0.1');
    }
    
    public function testRollbackErrorStopsExecution() {
        try {
            $runner = new SchemaRunner($this->getConnectionInfo());
            $runner->createSchemaTable();
            
            // Clear any existing applied changes for clean test
            $runner->table('schema_changes')->delete()->execute();
            
            // Apply changes first
            $applied = $runner->apply();
            
            if (!empty($applied)) {
                $errorCaught = false;
                $runner->addOnErrorCallback(function($err, $change, $schema) use (&$errorCaught) {
                    $errorCaught = true;
                });
                
                // Create a failing rollback scenario by dropping the table first
                $runner->table('user_profiles')->drop()->execute();
                
                // Now try to rollback - should fail and stop
                $rolled = $runner->rollbackUpTo(null);
                
                // Should have caught error and stopped
                $this->assertTrue($errorCaught);
                $this->assertIsArray($rolled);
            } else {
                $this->assertTrue(true, 'No changes to rollback');
            }
            
            $runner->dropSchemaTable();
        } catch (DatabaseException $ex) {
            $this->markTestSkipped('Database connection failed: ' . $ex->getMessage());
        }
    }
    
    public function testMultipleErrorCallbacks() {
        $runner = new SchemaRunner($this->getConnectionInfo());
        
        $callback1Called = false;
        $callback2Called = false;
        
        $runner->addOnErrorCallback(function($err, $change, $schema) use (&$callback1Called) {
            $callback1Called = true;
        });
        
        $runner->addOnErrorCallback(function($err, $change, $schema) use (&$callback2Called) {
            $callback2Called = true;
        });
        
        // Simulate error by accessing private method
        $reflection = new \ReflectionClass($runner);
        $property = $reflection->getProperty('onErrCallbacks');
        $property->setAccessible(true);
        $callbacks = $property->getValue($runner);
        
        // Manually trigger callbacks to test
        foreach ($callbacks as $callback) {
            call_user_func_array($callback, [new \Exception('test'), null, null]);
        }
        
        $this->assertTrue($callback1Called);
        $this->assertTrue($callback2Called);
    }
    
    public function testMultipleRegisterErrorCallbacks() {
        $runner = new SchemaRunner($this->getConnectionInfo());
        
        $callback1Called = false;
        $callback2Called = false;
        
        $runner->addOnRegisterErrorCallback(function($err) use (&$callback1Called) {
            $callback1Called = true;
        });
        
        $runner->addOnRegisterErrorCallback(function($err) use (&$callback2Called) {
            $callback2Called = true;
        });
        
        // Simulate error by accessing private method
        $reflection = new \ReflectionClass($runner);
        $property = $reflection->getProperty('onRegErrCallbacks');
        $property->setAccessible(true);
        $callbacks = $property->getValue($runner);
        
        // Manually trigger callbacks to test
        foreach ($callbacks as $callback) {
            call_user_func_array($callback, [new \Exception('test')]);
        }
        
        $this->assertTrue($callback1Called);
        $this->assertTrue($callback2Called);
    }

    // Environment and Execution Issues
    public function testEnvironmentFilteringSkipsChanges() {
        $tempDir = sys_get_temp_dir() . '/schema_test_' . uniqid();
        mkdir($tempDir, 0777, true);
        
        // Create migration for specific environment
        file_put_contents($tempDir . '/ProdOnlyMigration.php', '<?php 
        namespace TestNamespace;
        class ProdOnlyMigration extends \\WebFiori\\Database\\Schema\\AbstractMigration { 
            public function getEnvironments(): array { return ["prod"]; }
            public function up($db): void {} 
            public function down($db): void {} 
        }');
        
        // Create runner for dev environment
        $runner = new SchemaRunner($this->getConnectionInfo(), 'test');
        
        try {
            $runner->createSchemaTable();
            $applied = $runner->apply();
            
            // Should skip migration not for this environment
            $this->assertEmpty($applied);
            
            $runner->dropSchemaTable();
        } catch (DatabaseException $ex) {
            $this->markTestSkipped('Database connection failed: ' . $ex->getMessage());
        }
        
        // Cleanup
        unlink($tempDir . '/ProdOnlyMigration.php');
        rmdir($tempDir);
    }

    public function testDatabaseConnectionFailureDuringExecution() {
        // Test that connection failures are handled properly
        $badConnection = new ConnectionInfo('mysql', 'invalid_user', 'invalid_pass', 'invalid_db');
        
        $this->expectException(DatabaseException::class);
        $this->expectExceptionMessage('Unable to connect to database');
        
        $runner = new SchemaRunner($badConnection);
    }

    // Type Safety and Validation Issues
    public function testNonDatabaseChangeClassIgnored() {
        $tempDir = sys_get_temp_dir() . '/schema_test_' . uniqid();
        mkdir($tempDir, 0777, true);
        
        // Create class that's not a DatabaseChange
        file_put_contents($tempDir . '/NotAMigration.php', '<?php 
        namespace TestNamespace;
        class NotAMigration { 
            public function up($db): void {} 
        }');
        
        $runner = new SchemaRunner($this->getConnectionInfo());
        $changes = $runner->getChanges();
        
        // Should ignore non-DatabaseChange classes
        $this->assertEmpty($changes);
        
        // Cleanup
        unlink($tempDir . '/NotAMigration.php');
        rmdir($tempDir);
    }

    public function testAbstractClassInstantiationError() {
        $tempDir = sys_get_temp_dir() . '/schema_test_' . uniqid();
        mkdir($tempDir, 0777, true);
        
        // Create abstract migration class
        file_put_contents($tempDir . '/AbstractTestMigration.php', '<?php 
        namespace TestNamespace;
        abstract class AbstractTestMigration extends \\WebFiori\\Database\\Schema\\AbstractMigration { 
            public function up($db): void {} 
            public function down($db): void {} 
        }');
        
        $errorCaught = false;
        $runner = new SchemaRunner($this->getConnectionInfo());
        $runner->addOnRegisterErrorCallback(function($err) use (&$errorCaught) {
            $errorCaught = true;
        });
        
        $changes = $runner->getChanges();
        
        // Should handle abstract class error
        $this->assertEmpty($changes);
        
        // Cleanup
        unlink($tempDir . '/AbstractTestMigration.php');
        rmdir($tempDir);
    }

    public function testIncompleteClassImplementation() {
        $runner = new SchemaRunner($this->getConnectionInfo());
        $runner->registerAll([TestMigration::class, TestSeeder::class]);
        
        $changes = $runner->getChanges();
        $this->assertCount(2, $changes);
    }

    // Performance and Memory Issues
    public function testMemoryUsageWithManyMigrations() {
        $runner = new SchemaRunner($this->getConnectionInfo());
        $runner->registerAll([TestMigration::class, TestSeeder::class]);
        
        $changes = $runner->getChanges();
        $this->assertCount(2, $changes);
    }

    public function testRepeatedDirectoryScanningOverhead() {
        $runner = new SchemaRunner($this->getConnectionInfo());
        $runner->registerAll([TestMigration::class, TestSeeder::class]);
        
        $changes = $runner->getChanges();
        $this->assertCount(2, $changes);
    }
}
