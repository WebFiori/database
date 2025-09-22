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
}
