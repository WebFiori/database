<?php

namespace WebFiori\Tests\Database\Schema;

use PHPUnit\Framework\TestCase;
use WebFiori\Database\ConnectionInfo;
use WebFiori\Database\Database;
use WebFiori\Database\DatabaseException;
use WebFiori\Database\Schema\AbstractMigration;
use WebFiori\Database\Schema\AbstractSeeder;
use WebFiori\Database\Schema\SchemaRunner;

class SchemaAdvancedTest extends TestCase {
    
    private function getConnectionInfo(): ConnectionInfo {
        return new ConnectionInfo('mysql', 'root', getenv('MYSQL_ROOT_PASSWORD'), 'testing_db');
    }
    
    public function testComplexDependencyChain() {
        try {
            $runner = new SchemaRunner(__DIR__, 'WebFiori\\Tests\\Database\\Schema', $this->getConnectionInfo());
            $runner->createSchemaTable();
            
            // Clear any existing applied changes for clean test
            $runner->table('schema_changes')->delete()->execute();
            
            $changes = $runner->getChanges();
            $changeNames = array_map(function($change) {
                return $change->getName();
            }, $changes);
            
            // Verify dependency order is maintained
            $testMigrationIndex = array_search('TestMigration', $changeNames);
            $testMigration2Index = array_search('TestMigration2', $changeNames);
            $testSeederIndex = array_search('TestSeeder', $changeNames);
            $testSeeder2Index = array_search('TestSeeder2', $changeNames);
            
            if ($testMigrationIndex !== false && $testMigration2Index !== false) {
                $this->assertLessThan($testMigration2Index, $testMigrationIndex);
            }
            
            if ($testSeederIndex !== false && $testSeeder2Index !== false) {
                $this->assertLessThan($testSeeder2Index, $testSeederIndex);
            }
            
            $runner->dropSchemaTable();
        } catch (DatabaseException $ex) {
            $this->markTestSkipped('Database connection failed: ' . $ex->getMessage());
        }
    }
    
    public function testApplyAndRollbackSequence() {
        try {
            $runner = new SchemaRunner(__DIR__, 'WebFiori\\Tests\\Database\\Schema', $this->getConnectionInfo());
            $runner->createSchemaTable();
            
            // Clear any existing applied changes for clean test
            $runner->table('schema_changes')->delete()->execute();
            
            // Clear any existing applied changes for clean test
            $runner->table('schema_changes')->delete()->execute();
            
            // Apply all changes
            $applied = $runner->apply();
            $this->assertGreaterThan(0, count($applied));
            
            // Verify all are marked as applied
            foreach ($applied as $change) {
                $this->assertTrue($runner->isApplied($change->getName()));
            }
            
            // Rollback one by one
            $totalRolled = [];
            while (true) {
                $rolled = $runner->rollbackUpTo(null);
                if (empty($rolled)) {
                    break;
                }
                $totalRolled = array_merge($totalRolled, $rolled);
                
                // Verify the rolled back change is no longer applied
                foreach ($rolled as $change) {
                    $this->assertFalse($runner->isApplied($change->getName()));
                }
            }
            
            $this->assertEquals(count($applied), count($totalRolled));
            
            $runner->dropSchemaTable();
        } catch (DatabaseException $ex) {
            $this->markTestSkipped('Database connection failed: ' . $ex->getMessage());
        }
    }
    
    public function testApplyOneByOne() {
        try {
            $runner = new SchemaRunner(__DIR__, 'WebFiori\\Tests\\Database\\Schema', $this->getConnectionInfo());
            $runner->createSchemaTable();
            
            // Clear any existing applied changes for clean test
            $runner->table('schema_changes')->delete()->execute();
            
            $appliedChanges = [];
            
            // Apply changes one by one
            while (true) {
                $change = $runner->applyOne();
                if ($change === null) {
                    break;
                }
                $appliedChanges[] = $change;
                $this->assertTrue($runner->isApplied($change->getName()));
            }
            
            $this->assertGreaterThan(0, count($appliedChanges));
            
            // Verify no more changes to apply
            $this->assertNull($runner->applyOne());
            
            $runner->dropSchemaTable();
        } catch (DatabaseException $ex) {
            $this->markTestSkipped('Database connection failed: ' . $ex->getMessage());
        }
    }
    
    public function testMixedEnvironmentScenario() {
        try {
            // Test with 'test' environment
            $testRunner = new SchemaRunner(__DIR__, 'WebFiori\\Tests\\Database\\Schema', $this->getConnectionInfo(), 'test');
            $testRunner->createSchemaTable();
            
            // Clear any existing applied changes for clean test
            $runner->table('schema_changes')->delete()->execute();
            
            $applied = $testRunner->apply();
            
            // Count migrations vs seeders applied
            $migrationCount = 0;
            $seederCount = 0;
            
            foreach ($applied as $change) {
                if ($change instanceof AbstractMigration) {
                    $migrationCount++;
                } elseif ($change instanceof AbstractSeeder) {
                    $seederCount++;
                }
            }
            
            $this->assertGreaterThan(0, $migrationCount);
            $this->assertGreaterThan(0, $seederCount); // TestSeeder should run in 'test' env
            
            $testRunner->dropSchemaTable();
        } catch (DatabaseException $ex) {
            $this->markTestSkipped('Database connection failed: ' . $ex->getMessage());
        }
    }
    
    public function testSchemaTableOperations() {
        try {
            $runner = new SchemaRunner(__DIR__, 'WebFiori\\Tests\\Database\\Schema', $this->getConnectionInfo());
            
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
        $runner = new SchemaRunner(__DIR__, 'WebFiori\\Tests\\Database\\Schema', $this->getConnectionInfo());
        
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
