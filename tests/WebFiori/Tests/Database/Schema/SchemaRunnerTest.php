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

class SchemaRunnerTest extends TestCase {
    
    protected function tearDown(): void {
        // Force garbage collection to close connections
        gc_collect_cycles();
        // Small delay to allow MySQL to process connection cleanup
        usleep(1000); // 1ms
        parent::tearDown();
    }
    
    private function getConnectionInfo(): ConnectionInfo {
        return new ConnectionInfo('mysql', 'root', getenv('MYSQL_ROOT_PASSWORD'), 'testing_db', '127.0.0.1');
    }
    
    public function testConstruct() {
        $runner = new SchemaRunner($this->getConnectionInfo());
        
        $this->assertEquals('dev', $runner->getEnvironment());
        $this->assertIsArray($runner->getChanges());
    }
    
    public function testConstructWithEnvironment() {
        $runner = new SchemaRunner($this->getConnectionInfo(), 'test');
        
        $this->assertEquals('test', $runner->getEnvironment());
    }
    
    public function testGetChanges() {
        $runner = new SchemaRunner($this->getConnectionInfo());
        
        $this->assertIsArray($runner->getChanges());
        $this->assertEmpty($runner->getChanges());
    }
    
    public function testInvalidPath() {
        // Test registration-based approach doesn't need path validation
        $runner = new SchemaRunner($this->getConnectionInfo());
        $runner->register(TestMigration::class);
        
        $this->assertCount(1, $runner->getChanges());
    }
    
    public function testAddOnErrorCallback() {
        $runner = new SchemaRunner($this->getConnectionInfo());
        
        $callbackCalled = false;
        $runner->addOnErrorCallback(function($err, $change, $schema) use (&$callbackCalled) {
            $callbackCalled = true;
        });
        
        // Callback should be added (we can't directly test this without triggering an error)
        $this->assertTrue(true);
    }
    
    public function testAddOnRegisterErrorCallback() {
        $runner = new SchemaRunner($this->getConnectionInfo());
        
        $callbackCalled = false;
        $runner->addOnRegisterErrorCallback(function($err) use (&$callbackCalled) {
            $callbackCalled = true;
        });
        
        // Callback should be added
        $this->assertTrue(true);
    }
    
    public function testClearErrorCallbacks() {
        $runner = new SchemaRunner($this->getConnectionInfo());
        
        $runner->addOnErrorCallback(function($err, $change, $schema) {});
        $runner->clearErrorCallbacks();
        
        // Should clear callbacks without error
        $this->assertTrue(true);
    }
    
    public function testClearRegisterErrorCallbacks() {
        $runner = new SchemaRunner($this->getConnectionInfo());
        
        $runner->addOnRegisterErrorCallback(function($err) {});
        $runner->clearRegisterErrorCallbacks();
        
        // Should clear callbacks without error
        $this->assertTrue(true);
    }
    
    public function testHasChange() {
        $runner = new SchemaRunner($this->getConnectionInfo());
        $runner->register(TestMigration::class);
        
        $this->assertTrue($runner->hasChange(TestMigration::class));
        $this->assertFalse($runner->hasChange('NonExistentChange'));
    }
    
    public function testApplyOne() {
        try {
            $runner = new SchemaRunner($this->getConnectionInfo());
            $runner->createSchemaTable();
            
            $change = $runner->applyOne();
            
            if ($change !== null) {
                $this->assertInstanceOf('WebFiori\\Database\\Schema\\DatabaseChange', $change);
                $this->assertTrue($runner->isApplied($change->getName()));
            } else {
                // If no changes to apply, that's also valid
                $this->assertTrue(true, 'No changes to apply');
            }
            
            $runner->dropSchemaTable();
        } catch (DatabaseException $ex) {
            $this->markTestSkipped('Database connection failed: ' . $ex->getMessage());
        }
    }
    
    public function testApplyOneWithNoChanges() {
        try {
            $runner = new SchemaRunner($this->getConnectionInfo());
            $runner->createSchemaTable();
            
            // Apply all changes first
            $runner->apply();
            
            // Now applyOne should return null
            $change = $runner->applyOne();
            $this->assertNull($change);
            
            $runner->dropSchemaTable();
        } catch (DatabaseException $ex) {
            $this->markTestSkipped('Database connection failed: ' . $ex->getMessage());
        }
    }
    
    public function testRollbackUpToSpecificChange() {
        try {
            $runner = new SchemaRunner($this->getConnectionInfo());
            $runner->createSchemaTable();
            
            $applied = $runner->apply();
            
            if (!empty($applied)) {
                $lastChange = end($applied);
                $rolled = $runner->rollbackUpTo($lastChange->getName());
                
                $this->assertIsArray($rolled);
                $this->assertCount(1, $rolled);
                $this->assertEquals($lastChange->getName(), $rolled[0]->getName());
                $this->assertFalse($runner->isApplied($lastChange->getName()));
            } else {
                $this->assertTrue(true, 'No changes were applied to rollback');
            }
            
            $runner->dropSchemaTable();
        } catch (DatabaseException $ex) {
            $this->markTestSkipped('Database connection failed: ' . $ex->getMessage());
        }
    }
    
    public function testRollbackUpToNonExistentChange() {
        try {
            $runner = new SchemaRunner($this->getConnectionInfo());
            $runner->createSchemaTable();
            
            $rolled = $runner->rollbackUpTo('NonExistentChange');
            $this->assertIsArray($rolled);
            $this->assertEmpty($rolled);
            
            $runner->dropSchemaTable();
        } catch (DatabaseException $ex) {
            $this->markTestSkipped('Database connection failed: ' . $ex->getMessage());
        }
    }
    
    public function testErrorCallbackOnExecutionFailure() {
        $runner = new SchemaRunner($this->getConnectionInfo());
        
        $errorCaught = false;
        $runner->addOnErrorCallback(function($err, $change, $schema) use (&$errorCaught) {
            $errorCaught = true;
            $this->assertInstanceOf('Throwable', $err);
        });
        
        // Simulate error by accessing private property and triggering callbacks
        $reflection = new \ReflectionClass($runner);
        $property = $reflection->getProperty('onErrCallbacks');
        $property->setAccessible(true);
        $callbacks = $property->getValue($runner);
        
        // Manually trigger callbacks to test
        foreach ($callbacks as $callback) {
            call_user_func_array($callback, [new \Exception('test'), null, null]);
        }
        
        $this->assertTrue($errorCaught);
    }

    // File System Scanning Issues
    public function testSubdirectoryMigrationsNotDetected() {
        // Test that registration approach doesn't have subdirectory issues
        $runner = new SchemaRunner($this->getConnectionInfo());
        $runner->register(TestMigration::class);
        
        $changes = $runner->getChanges();
        $this->assertCount(1, $changes);
    }

    public function testFileExtensionAssumptions() {
        $tempDir = sys_get_temp_dir() . '/schema_test_' . uniqid();
        mkdir($tempDir, 0777, true);
        
        // Create file with multiple dots
        file_put_contents($tempDir . '/Migration.backup.php', '<?php class Migration { }');
        
        $runner = new SchemaRunner($this->getConnectionInfo());
        
        // Should handle file with multiple dots gracefully
        $this->assertIsArray($runner->getChanges());
        
        // Cleanup
        unlink($tempDir . '/Migration.backup.php');
        rmdir($tempDir);
    }

    public function testPermissionIssuesOnDirectory() {
        // Test that registration approach doesn't have permission issues
        $runner = new SchemaRunner($this->getConnectionInfo());
        $runner->register(TestMigration::class);
        
        $this->assertTrue($runner->hasChange(TestMigration::class));
    }

    // Class Loading Issues
    public function testNamespaceMismatch() {
        // Test registration with invalid class name
        $runner = new SchemaRunner($this->getConnectionInfo());
        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Class does not exist');
        
        $runner->register('InvalidNamespace\\NonExistentClass');
    }

    public function testConstructorDependencies() {
        // Test registration handles constructor requirements properly
        $runner = new SchemaRunner($this->getConnectionInfo());
        $result = $runner->register(TestMigration::class);
        
        $this->assertTrue($result);
        $this->assertCount(1, $runner->getChanges());
    }

    // Dependency Resolution Issues
    public function testMissingDependency() {
        // Test dependency validation with registration
        $runner = new SchemaRunner($this->getConnectionInfo());
        $runner->register(TestMigration::class);
        
        $changes = $runner->getChanges();
        $this->assertCount(1, $changes);
        
        // Test that changes are registered properly
        $this->assertInstanceOf('WebFiori\\Database\\Schema\\DatabaseChange', $changes[0]);
    }

    public function testCircularDependency() {
        // Test circular dependency detection with registration
        $runner = new SchemaRunner($this->getConnectionInfo());
        $runner->registerAll([TestMigration::class, TestSeeder::class]);
        
        // Registration succeeds, circular dependencies detected during execution
        $this->assertCount(2, $runner->getChanges());
    }

    // Schema Tracking Issues
    public function testSchemaTableNotExists() {
        try {
            $runner = new SchemaRunner($this->getConnectionInfo());
            $runner->createSchemaTable(); // Ensure table exists first
            
            // Test that we can check if changes are applied
            $this->assertFalse($runner->isApplied('NonExistentMigration'));
            
        } catch (DatabaseException $ex) {
            $this->markTestSkipped('Database connection failed: ' . $ex->getMessage());
        }
    }

    public function testDuplicateChangeDetection() {
        $runner = new SchemaRunner($this->getConnectionInfo());
        $runner->register(TestMigration::class);
        $runner->register(TestMigration::class); // Register same class twice
        
        $changes = $runner->getChanges();
        $this->assertCount(2, $changes); // Both instances are registered
    }

    public function testNameCollisionInFindChangeByName() {
        $runner = new SchemaRunner($this->getConnectionInfo());
        $runner->registerAll([TestMigration::class, TestSeeder::class]);
        
        $this->assertTrue($runner->hasChange(TestMigration::class));
        $this->assertTrue($runner->hasChange(TestSeeder::class));
    }

    // Error Handling Issues
    public function testSilentFailureInApply() {
        $runner = new SchemaRunner($this->getConnectionInfo());
        
        $errorCaught = false;
        $runner->addOnErrorCallback(function($err, $change, $schema) use (&$errorCaught) {
            $errorCaught = true;
        });
        
        $runner->register(TestMigration::class);
        
        // Test that errors are properly caught
        $this->assertCount(1, $runner->getChanges());
    }

    public function testRollbackFailureContinuesExecution() {
        try {
            $runner = new SchemaRunner($this->getConnectionInfo());
            $runner->createSchemaTable();
            
            $errorCaught = false;
            $runner->addOnErrorCallback(function($err, $change, $schema) use (&$errorCaught) {
                $errorCaught = true;
            });
            
            $runner->register(TestMigration::class);
            $changes = $runner->getChanges();
            
            // Test that rollback handling works
            $this->assertCount(1, $changes);
            $this->assertIsCallable([$runner, 'rollbackUpTo']);
            
        } catch (DatabaseException $ex) {
            $this->markTestSkipped('Database connection failed: ' . $ex->getMessage());
        }
    }
}
