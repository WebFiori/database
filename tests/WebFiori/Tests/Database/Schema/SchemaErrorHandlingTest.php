<?php

namespace WebFiori\Tests\Database\Schema;

use PHPUnit\Framework\TestCase;
use WebFiori\Database\ConnectionInfo;
use WebFiori\Database\Database;
use WebFiori\Database\DatabaseException;
use WebFiori\Database\Schema\SchemaRunner;

class SchemaErrorHandlingTest extends TestCase {
    
    private function getConnectionInfo(): ConnectionInfo {
        return new ConnectionInfo('mysql', 'root', getenv('MYSQL_ROOT_PASSWORD'), 'testing_db');
    }
    
    public function testRegisterErrorCallback() {
        // Create a directory with invalid PHP files to trigger registration errors
        $tempDir = sys_get_temp_dir() . '/schema_test_' . uniqid();
        mkdir($tempDir);
        
        // Create an invalid PHP file
        file_put_contents($tempDir . '/InvalidChange.php', '<?php invalid syntax');
        
        $errorCaught = false;
        $runner = new SchemaRunner($tempDir, 'InvalidNamespace', $this->getConnectionInfo());
        
        $runner->addOnRegisterErrorCallback(function($err) use (&$errorCaught) {
            $errorCaught = true;
            $this->assertInstanceOf('Throwable', $err);
        });
        
        // Force re-scan to trigger error
        $reflection = new \ReflectionClass($runner);
        $method = $reflection->getMethod('scanPathForChanges');
        $method->setAccessible(true);
        $method->invoke($runner);
        
        // Clean up
        unlink($tempDir . '/InvalidChange.php');
        rmdir($tempDir);
        
        $this->assertTrue($errorCaught);
    }
    
    public function testRollbackErrorStopsExecution() {
        try {
            $runner = new SchemaRunner(__DIR__, 'WebFiori\\Tests\\Database\\Schema', $this->getConnectionInfo());
            $runner->createSchemaTable();
            
            // Apply changes first
            $applied = $runner->apply();
            
            if (!empty($applied)) {
                $errorCaught = false;
                $runner->addOnErrorCallback(function($err, $change, $schema) use (&$errorCaught) {
                    $errorCaught = true;
                });
                
                // Create a failing rollback scenario by dropping the table first
                $runner->table('test_table')->drop()->execute();
                
                // Now try to rollback - should fail and stop
                $rolled = $runner->rollbackUpTo(null);
                
                // Should have caught error and stopped
                $this->assertTrue($errorCaught);
                $this->assertIsArray($rolled);
            }
            
            $runner->dropSchemaTable();
        } catch (DatabaseException $ex) {
            $this->markTestSkipped('Database connection failed: ' . $ex->getMessage());
        }
    }
    
    public function testMultipleErrorCallbacks() {
        $runner = new SchemaRunner(__DIR__, 'WebFiori\\Tests\\Database\\Schema', $this->getConnectionInfo());
        
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
        $runner = new SchemaRunner(__DIR__, 'WebFiori\\Tests\\Database\\Schema', $this->getConnectionInfo());
        
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
}
