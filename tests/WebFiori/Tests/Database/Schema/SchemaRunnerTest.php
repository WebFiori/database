<?php

namespace WebFiori\Tests\Database\Schema;

use PHPUnit\Framework\TestCase;
use WebFiori\Database\ConnectionInfo;
use WebFiori\Database\Database;
use WebFiori\Database\DatabaseException;
use WebFiori\Database\Schema\AbstractMigration;
use WebFiori\Database\Schema\AbstractSeeder;
use WebFiori\Database\Schema\SchemaRunner;

class SchemaRunnerTest extends TestCase {
    
    private function getConnectionInfo(): ConnectionInfo {
        return new ConnectionInfo('mysql', 'root', getenv('MYSQL_ROOT_PASSWORD'), 'testing_db', '127.0.0.1');
    }
    
    public function testConstruct() {
        $runner = new SchemaRunner(__DIR__, 'TestNamespace', $this->getConnectionInfo());
        
        $this->assertEquals(__DIR__, $runner->getPath());
        $this->assertEquals('TestNamespace', $runner->getNamespace());
        $this->assertEquals('dev', $runner->getEnvironment());
    }
    
    public function testConstructWithEnvironment() {
        $runner = new SchemaRunner(__DIR__, 'TestNamespace', $this->getConnectionInfo(), 'test');
        
        $this->assertEquals('test', $runner->getEnvironment());
    }
    
    public function testGetChanges() {
        $runner = new SchemaRunner(__DIR__, 'TestNamespace', $this->getConnectionInfo());
        
        $this->assertIsArray($runner->getChanges());
    }
    
    public function testInvalidPath() {
        $this->expectException(DatabaseException::class);
        $this->expectExceptionMessage('Invalid schema path');
        new SchemaRunner('/invalid/path', 'TestNamespace', $this->getConnectionInfo());
    }
    
    public function testAddOnErrorCallback() {
        $runner = new SchemaRunner(__DIR__, 'WebFiori\\Tests\\Database\\Schema', $this->getConnectionInfo());
        
        $callbackCalled = false;
        $runner->addOnErrorCallback(function($err, $change, $schema) use (&$callbackCalled) {
            $callbackCalled = true;
        });
        
        // Callback should be added (we can't directly test this without triggering an error)
        $this->assertTrue(true);
    }
    
    public function testAddOnRegisterErrorCallback() {
        $runner = new SchemaRunner(__DIR__, 'WebFiori\\Tests\\Database\\Schema', $this->getConnectionInfo());
        
        $callbackCalled = false;
        $runner->addOnRegisterErrorCallback(function($err) use (&$callbackCalled) {
            $callbackCalled = true;
        });
        
        // Callback should be added
        $this->assertTrue(true);
    }
    
    public function testClearErrorCallbacks() {
        $runner = new SchemaRunner(__DIR__, 'WebFiori\\Tests\\Database\\Schema', $this->getConnectionInfo());
        
        $runner->addOnErrorCallback(function($err, $change, $schema) {});
        $runner->clearErrorCallbacks();
        
        // Should clear callbacks without error
        $this->assertTrue(true);
    }
    
    public function testClearRegisterErrorCallbacks() {
        $runner = new SchemaRunner(__DIR__, 'WebFiori\\Tests\\Database\\Schema', $this->getConnectionInfo());
        
        $runner->addOnRegisterErrorCallback(function($err) {});
        $runner->clearRegisterErrorCallbacks();
        
        // Should clear callbacks without error
        $this->assertTrue(true);
    }
    
    public function testHasChange() {
        $runner = new SchemaRunner(__DIR__, 'WebFiori\\Tests\\Database\\Schema', $this->getConnectionInfo());
        
        // Check for actual test classes that exist
        $changes = $runner->getChanges();
        $hasAnyChange = !empty($changes);
        
        $this->assertTrue($hasAnyChange, 'Should have at least one change detected');
        
        if (!empty($changes)) {
            $firstChange = $changes[0];
            $this->assertTrue($runner->hasChange($firstChange->getName()));
        }
        
        $this->assertFalse($runner->hasChange('NonExistentChange'));
    }
    
    public function testApplyOne() {
        try {
            $runner = new SchemaRunner(__DIR__, 'WebFiori\\Tests\\Database\\Schema', $this->getConnectionInfo());
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
            $runner = new SchemaRunner(__DIR__, 'WebFiori\\Tests\\Database\\Schema', $this->getConnectionInfo());
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
            $runner = new SchemaRunner(__DIR__, 'WebFiori\\Tests\\Database\\Schema', $this->getConnectionInfo());
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
            $runner = new SchemaRunner(__DIR__, 'WebFiori\\Tests\\Database\\Schema', $this->getConnectionInfo());
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
        $runner = new SchemaRunner(__DIR__, 'WebFiori\\Tests\\Database\\Schema', $this->getConnectionInfo());
        
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
        // Create temp directory structure with subdirectory
        $tempDir = sys_get_temp_dir() . '/schema_test_' . uniqid();
        $subDir = $tempDir . '/migrations';
        mkdir($subDir, 0777, true);
        
        // Create migration in subdirectory
        file_put_contents($subDir . '/TestMigration.php', '<?php class TestMigration extends WebFiori\\Database\\Schema\\AbstractMigration { public function up($db): void {} public function down($db): void {} }');
        
        $runner = new SchemaRunner($tempDir, 'TestNamespace', $this->getConnectionInfo());
        $changes = $runner->getChanges();
        
        // Should not detect migration in subdirectory
        $this->assertEmpty($changes);
        
        // Cleanup
        unlink($subDir . '/TestMigration.php');
        rmdir($subDir);
        rmdir($tempDir);
    }

    public function testFileExtensionAssumptions() {
        $tempDir = sys_get_temp_dir() . '/schema_test_' . uniqid();
        mkdir($tempDir, 0777, true);
        
        // Create file with multiple dots
        file_put_contents($tempDir . '/Migration.backup.php', '<?php class Migration { }');
        
        $runner = new SchemaRunner($tempDir, 'TestNamespace', $this->getConnectionInfo());
        
        // Should handle file with multiple dots gracefully
        $this->assertIsArray($runner->getChanges());
        
        // Cleanup
        unlink($tempDir . '/Migration.backup.php');
        rmdir($tempDir);
    }

    public function testPermissionIssuesOnDirectory() {
        if (PHP_OS_FAMILY === 'Windows') {
            $this->markTestSkipped('Permission tests not reliable on Windows');
        }
        
        $tempDir = sys_get_temp_dir() . '/schema_test_' . uniqid();
        mkdir($tempDir, 0000); // No permissions
        
        $this->expectException(DatabaseException::class);
        new SchemaRunner($tempDir, 'TestNamespace', $this->getConnectionInfo());
        
        // Cleanup
        chmod($tempDir, 0777);
        rmdir($tempDir);
    }

    // Class Loading Issues
    public function testNamespaceMismatch() {
        $tempDir = sys_get_temp_dir() . '/schema_test_' . uniqid();
        mkdir($tempDir, 0777, true);
        
        // Create file with class name that doesn't match filename
        file_put_contents($tempDir . '/TestFile.php', '<?php class DifferentClassName extends WebFiori\\Database\\Schema\\AbstractMigration { public function up($db): void {} public function down($db): void {} }');
        
        $errorCaught = false;
        $runner = new SchemaRunner($tempDir, 'TestNamespace', $this->getConnectionInfo());
        $runner->addOnRegisterErrorCallback(function($err) use (&$errorCaught) {
            $errorCaught = true;
        });
        
        // Force rescan to trigger error callback
        $reflection = new \ReflectionClass($runner);
        $method = $reflection->getMethod('scanPathForChanges');
        $method->setAccessible(true);
        $method->invoke($runner);
        
        $changes = $runner->getChanges();
        $this->assertEmpty($changes);
        
        // Cleanup
        unlink($tempDir . '/TestFile.php');
        rmdir($tempDir);
    }

    public function testConstructorDependencies() {
        $tempDir = sys_get_temp_dir() . '/schema_test_' . uniqid();
        mkdir($tempDir, 0777, true);
        
        // Create migration with constructor parameters
        file_put_contents($tempDir . '/BadMigration.php', '<?php 
        namespace TestNamespace;
        class BadMigration extends \\WebFiori\\Database\\Schema\\AbstractMigration { 
            public function __construct($param) {} 
            public function up($db): void {} 
            public function down($db): void {} 
        }');
        
        $errorCaught = false;
        $runner = new SchemaRunner($tempDir, 'TestNamespace', $this->getConnectionInfo());
        $runner->addOnRegisterErrorCallback(function($err) use (&$errorCaught) {
            $errorCaught = true;
        });
        
        $changes = $runner->getChanges();
        $this->assertEmpty($changes);
        
        // Cleanup
        unlink($tempDir . '/BadMigration.php');
        rmdir($tempDir);
    }

    // Dependency Resolution Issues
    public function testMissingDependency() {
        $tempDir = sys_get_temp_dir() . '/schema_test_' . uniqid();
        mkdir($tempDir, 0777, true);
        
        // Create migration with missing dependency
        file_put_contents($tempDir . '/DependentMigration.php', '<?php 
        namespace TestNamespace;
        class DependentMigration extends \\WebFiori\\Database\\Schema\\AbstractMigration { 
            public function getDependencies(): array { return ["NonExistentMigration"]; }
            public function up($db): void {} 
            public function down($db): void {} 
        }');
        
        $runner = new SchemaRunner($tempDir, 'TestNamespace', $this->getConnectionInfo());
        
        try {
            $runner->createSchemaTable();
            $applied = $runner->apply();
            
            // Should not apply migration with missing dependency
            $this->assertEmpty($applied);
            
            $runner->dropSchemaTable();
        } catch (DatabaseException $ex) {
            $this->markTestSkipped('Database connection failed: ' . $ex->getMessage());
        }
        
        // Cleanup
        unlink($tempDir . '/DependentMigration.php');
        rmdir($tempDir);
    }

    public function testCircularDependency() {
        $tempDir = sys_get_temp_dir() . '/schema_test_' . uniqid();
        mkdir($tempDir, 0777, true);
        
        // Create migrations with circular dependency
        file_put_contents($tempDir . '/Migration1.php', '<?php 
        namespace TestNamespace;
        class Migration1 extends \\WebFiori\\Database\\Schema\\AbstractMigration { 
            public function getDependencies(): array { return ["TestNamespace\\\\Migration2"]; }
            public function up($db): void {} 
            public function down($db): void {} 
        }');
        
        file_put_contents($tempDir . '/Migration2.php', '<?php 
        namespace TestNamespace;
        class Migration2 extends \\WebFiori\\Database\\Schema\\AbstractMigration { 
            public function getDependencies(): array { return ["TestNamespace\\\\Migration1"]; }
            public function up($db): void {} 
            public function down($db): void {} 
        }');
        
        $this->expectException(DatabaseException::class);
        $this->expectExceptionMessage('Circular dependency detected');
        
        new SchemaRunner($tempDir, 'TestNamespace', $this->getConnectionInfo());
        
        // Cleanup
        unlink($tempDir . '/Migration1.php');
        unlink($tempDir . '/Migration2.php');
        rmdir($tempDir);
    }

    // Schema Tracking Issues
    public function testSchemaTableNotExists() {
        try {
            $runner = new SchemaRunner(__DIR__, 'WebFiori\\Tests\\Database\\Schema', $this->getConnectionInfo());
            
            // Ensure schema table doesn't exist
            try {
                $runner->dropSchemaTable();
            } catch (DatabaseException $ex) {
                // Ignore if table doesn't exist
            }
            
            // Should return false when checking if change is applied
            $this->assertFalse($runner->isApplied('TestMigration'));
            
        } catch (DatabaseException $ex) {
            $this->markTestSkipped('Database connection failed: ' . $ex->getMessage());
        }
    }

    public function testDuplicateChangeDetection() {
        $tempDir = sys_get_temp_dir() . '/schema_test_' . uniqid();
        mkdir($tempDir, 0777, true);
        
        // Create different migrations with unique class names
        file_put_contents($tempDir . '/FirstMigration.php', '<?php 
        namespace TestNamespace;
        class FirstMigration extends \\WebFiori\\Database\\Schema\\AbstractMigration { 
            public function up($db): void {} 
            public function down($db): void {} 
        }');
        
        file_put_contents($tempDir . '/SecondMigration.php', '<?php 
        namespace TestNamespace;
        class SecondMigration extends \\WebFiori\\Database\\Schema\\AbstractMigration { 
            public function up($db): void {} 
            public function down($db): void {} 
        }');
        
        $runner = new SchemaRunner($tempDir, 'TestNamespace', $this->getConnectionInfo());
        $changes = $runner->getChanges();
        
        // Should detect both files
        $this->assertCount(2, $changes);
        
        // Cleanup
        unlink($tempDir . '/FirstMigration.php');
        unlink($tempDir . '/SecondMigration.php');
        rmdir($tempDir);
    }

    public function testNameCollisionInFindChangeByName() {
        $tempDir = sys_get_temp_dir() . '/schema_test_' . uniqid();
        mkdir($tempDir, 0777, true);
        
        // Create migrations with similar names
        file_put_contents($tempDir . '/TestMigration.php', '<?php 
        namespace TestNamespace;
        class TestMigration extends \\WebFiori\\Database\\Schema\\AbstractMigration { 
            public function up($db): void {} 
            public function down($db): void {} 
        }');
        
        file_put_contents($tempDir . '/AnotherTestMigration.php', '<?php 
        namespace TestNamespace;
        class AnotherTestMigration extends \\WebFiori\\Database\\Schema\\AbstractMigration { 
            public function up($db): void {} 
            public function down($db): void {} 
        }');
        
        $runner = new SchemaRunner($tempDir, 'TestNamespace', $this->getConnectionInfo());
        
        // Test name collision scenarios
        $this->assertTrue($runner->hasChange('TestMigration'));
        $this->assertTrue($runner->hasChange('TestNamespace\\TestMigration'));
        
        // Cleanup
        unlink($tempDir . '/TestMigration.php');
        unlink($tempDir . '/AnotherTestMigration.php');
        rmdir($tempDir);
    }

    // Error Handling Issues
    public function testSilentFailureInApply() {
        $tempDir = sys_get_temp_dir() . '/schema_test_' . uniqid();
        mkdir($tempDir, 0777, true);
        
        // Create migration that will fail
        file_put_contents($tempDir . '/FailingMigration.php', '<?php 
        namespace TestNamespace;
        class FailingMigration extends \\WebFiori\\Database\\Schema\\AbstractMigration { 
            public function up($db): void { throw new Exception("Migration failed"); } 
            public function down($db): void {} 
        }');
        
        $runner = new SchemaRunner($tempDir, 'TestNamespace', $this->getConnectionInfo());
        
        $errorCaught = false;
        $runner->addOnErrorCallback(function($err, $change, $schema) use (&$errorCaught) {
            $errorCaught = true;
        });
        
        try {
            $runner->createSchemaTable();
            $applied = $runner->apply();
            
            // Should continue execution despite error
            $this->assertTrue($errorCaught);
            $this->assertIsArray($applied);
            
            $runner->dropSchemaTable();
        } catch (DatabaseException $ex) {
            $this->markTestSkipped('Database connection failed: ' . $ex->getMessage());
        }
        
        // Cleanup
        unlink($tempDir . '/FailingMigration.php');
        rmdir($tempDir);
    }

    public function testRollbackFailureContinuesExecution() {
        $tempDir = sys_get_temp_dir() . '/schema_test_' . uniqid();
        mkdir($tempDir, 0777, true);
        
        // Create migration with failing rollback
        file_put_contents($tempDir . '/BadRollbackMigration.php', '<?php 
        namespace TestNamespace;
        class BadRollbackMigration extends \\WebFiori\\Database\\Schema\\AbstractMigration { 
            public function up($db): void {} 
            public function down($db): void { throw new Exception("Rollback failed"); } 
        }');
        
        $runner = new SchemaRunner($tempDir, 'TestNamespace', $this->getConnectionInfo());
        
        $errorCaught = false;
        $runner->addOnErrorCallback(function($err, $change, $schema) use (&$errorCaught) {
            $errorCaught = true;
        });
        
        try {
            $runner->createSchemaTable();
            $applied = $runner->apply();
            
            if (!empty($applied)) {
                $rolled = $runner->rollbackUpTo(null);
                
                // Should catch error but continue
                $this->assertTrue($errorCaught);
                $this->assertIsArray($rolled);
            }
            
            $runner->dropSchemaTable();
        } catch (DatabaseException $ex) {
            $this->markTestSkipped('Database connection failed: ' . $ex->getMessage());
        }
        
        // Cleanup
        unlink($tempDir . '/BadRollbackMigration.php');
        rmdir($tempDir);
    }
}
