<?php

namespace WebFiori\Tests\Database\Schema;

use PHPUnit\Framework\TestCase;
use WebFiori\Database\ConnectionInfo;
use WebFiori\Database\DatabaseException;
use WebFiori\Database\Schema\SchemaRunner;

class SchemaTrackingTest extends TestCase {
    
    private function getConnectionInfo(): ConnectionInfo {
        return new ConnectionInfo('mysql', 'root', getenv('MYSQL_ROOT_PASSWORD'), 'testing_db', '127.0.0.1');
    }
    
    // Schema Table Existence Issues
    public function testSchemaTableNotExistsHandling() {
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

    public function testCorruptedSchemaTableHandling() {
        try {
            $runner = new SchemaRunner(__DIR__, 'WebFiori\\Tests\\Database\\Schema', $this->getConnectionInfo());
            $runner->createSchemaTable();
            
            // Corrupt the schema table by dropping a column
            $runner->setQuery("ALTER TABLE schema_changes DROP COLUMN change_name")->execute();
            
            // Should handle corrupted table gracefully
            $errorCaught = false;
            try {
                $runner->isApplied('TestMigration');
            } catch (DatabaseException $ex) {
                $errorCaught = true;
            }
            
            $this->assertTrue($errorCaught, 'Should catch error from corrupted schema table');
            
            $runner->dropSchemaTable();
        } catch (DatabaseException $ex) {
            $this->markTestSkipped('Database connection failed: ' . $ex->getMessage());
        }
    }

    // Duplicate Detection Issues
    public function testDuplicateChangeTracking() {
        $tempDir = sys_get_temp_dir() . '/schema_test_' . uniqid();
        mkdir($tempDir, 0777, true);
        
        // Create migration
        file_put_contents($tempDir . '/TestMigration.php', '<?php 
        namespace TestNamespace;
        class TestMigration extends \\WebFiori\\Database\\Schema\\AbstractMigration { 
            public function up($db): void {} 
            public function down($db): void {} 
        }');
        
        try {
            $runner = new SchemaRunner($tempDir, 'TestNamespace', $this->getConnectionInfo());
            $runner->createSchemaTable();
            
            // Apply migration
            $applied1 = $runner->apply();
            $this->assertCount(1, $applied1);
            
            // Try to apply again - should not apply duplicate
            $applied2 = $runner->apply();
            $this->assertEmpty($applied2);
            
            $runner->dropSchemaTable();
        } catch (DatabaseException $ex) {
            $this->markTestSkipped('Database connection failed: ' . $ex->getMessage());
        }
        
        // Cleanup
        unlink($tempDir . '/TestMigration.php');
        rmdir($tempDir);
    }

    public function testManualSchemaTableCorruption() {
        $tempDir = sys_get_temp_dir() . '/schema_test_' . uniqid();
        mkdir($tempDir, 0777, true);
        
        // Create migration
        file_put_contents($tempDir . '/TestMigration.php', '<?php 
        namespace TestNamespace;
        class TestMigration extends \\WebFiori\\Database\\Schema\\AbstractMigration { 
            public function up($db): void {} 
            public function down($db): void {} 
        }');
        
        try {
            $runner = new SchemaRunner($tempDir, 'TestNamespace', $this->getConnectionInfo());
            $runner->createSchemaTable();
            
            // Apply migration
            $runner->apply();
            
            // Manually insert duplicate record
            $runner->table('schema_changes')->insert([
                'change_name' => 'TestNamespace\\TestMigration',
                'type' => 'migration',
                'applied-on' => date('Y-m-d H:i:s')
            ])->execute();
            
            // Should still detect as applied despite duplicate
            $this->assertTrue($runner->isApplied('TestNamespace\\TestMigration'));
            
            $runner->dropSchemaTable();
        } catch (DatabaseException $ex) {
            $this->markTestSkipped('Database connection failed: ' . $ex->getMessage());
        }
        
        // Cleanup
        unlink($tempDir . '/TestMigration.php');
        rmdir($tempDir);
    }

    // Name Collision Issues
    public function testSimilarNameHandling() {
        $tempDir = sys_get_temp_dir() . '/schema_test_' . uniqid();
        mkdir($tempDir, 0777, true);
        
        // Create migrations with similar names
        file_put_contents($tempDir . '/UserMigration.php', '<?php 
        namespace TestNamespace;
        class UserMigration extends \\WebFiori\\Database\\Schema\\AbstractMigration { 
            public function up($db): void {} 
            public function down($db): void {} 
        }');
        
        file_put_contents($tempDir . '/UsersMigration.php', '<?php 
        namespace TestNamespace;
        class UsersMigration extends \\WebFiori\\Database\\Schema\\AbstractMigration { 
            public function up($db): void {} 
            public function down($db): void {} 
        }');
        
        $runner = new SchemaRunner($tempDir, 'TestNamespace', $this->getConnectionInfo());
        
        // Should distinguish between similar names
        $this->assertTrue($runner->hasChange('UserMigration'));
        $this->assertTrue($runner->hasChange('UsersMigration'));
        $this->assertFalse($runner->hasChange('NonExistentMigration'));
        
        // Cleanup
        unlink($tempDir . '/UserMigration.php');
        unlink($tempDir . '/UsersMigration.php');
        rmdir($tempDir);
    }

    public function testPartialNameMatching() {
        $tempDir = sys_get_temp_dir() . '/schema_test_' . uniqid();
        mkdir($tempDir, 0777, true);
        
        // Create migration
        file_put_contents($tempDir . '/CreateUsersTableMigration.php', '<?php 
        namespace TestNamespace;
        class CreateUsersTableMigration extends \\WebFiori\\Database\\Schema\\AbstractMigration { 
            public function up($db): void {} 
            public function down($db): void {} 
        }');
        
        $runner = new SchemaRunner($tempDir, 'TestNamespace', $this->getConnectionInfo());
        
        // Test different name formats
        $this->assertTrue($runner->hasChange('CreateUsersTableMigration'));
        $this->assertTrue($runner->hasChange('TestNamespace\\CreateUsersTableMigration'));
        $this->assertFalse($runner->hasChange('UsersTableMigration')); // Partial match should fail
        
        // Cleanup
        unlink($tempDir . '/CreateUsersTableMigration.php');
        rmdir($tempDir);
    }

    // Transaction and Consistency Issues
    public function testInconsistentTrackingAfterFailure() {
        $tempDir = sys_get_temp_dir() . '/schema_test_' . uniqid();
        mkdir($tempDir, 0777, true);
        
        // Create migration that fails after partial execution
        file_put_contents($tempDir . '/PartialFailureMigration.php', '<?php 
        namespace TestNamespace;
        class PartialFailureMigration extends \\WebFiori\\Database\\Schema\\AbstractMigration { 
            public function up($db): void { 
                $db->setQuery("CREATE TABLE test_table (id INT)");
                $db->execute();
                throw new Exception("Failure after table creation"); 
            } 
            public function down($db): void { 
                $db->setQuery("DROP TABLE IF EXISTS test_table");
                $db->execute();
            } 
        }');
        
        try {
            $runner = new SchemaRunner($tempDir, 'TestNamespace', $this->getConnectionInfo());
            $runner->createSchemaTable();
            
            $errorCaught = false;
            $runner->addOnErrorCallback(function($err, $change, $schema) use (&$errorCaught) {
                $errorCaught = true;
            });
            
            $applied = $runner->apply();
            
            // Should catch error but not mark as applied
            $this->assertTrue($errorCaught);
            $this->assertFalse($runner->isApplied('TestNamespace\\PartialFailureMigration'));
            
            // Clean up the partially created table
            try {
                $runner->setQuery("DROP TABLE IF EXISTS test_table")->execute();
            } catch (DatabaseException $ex) {
                // Ignore cleanup errors
            }
            
            $runner->dropSchemaTable();
        } catch (DatabaseException $ex) {
            $this->markTestSkipped('Database connection failed: ' . $ex->getMessage());
        }
        
        // Cleanup
        unlink($tempDir . '/PartialFailureMigration.php');
        rmdir($tempDir);
    }

    public function testSchemaTableRecreationAfterDrop() {
        try {
            $runner = new SchemaRunner(__DIR__, 'WebFiori\\Tests\\Database\\Schema', $this->getConnectionInfo());
            
            // Create and drop schema table
            $runner->createSchemaTable();
            $runner->dropSchemaTable();
            
            // Should handle missing table gracefully
            $this->assertFalse($runner->isApplied('TestMigration'));
            
            // Recreate table
            $runner->createSchemaTable();
            
            // Should work normally after recreation
            $this->assertFalse($runner->isApplied('TestMigration'));
            
            $runner->dropSchemaTable();
        } catch (DatabaseException $ex) {
            $this->markTestSkipped('Database connection failed: ' . $ex->getMessage());
        }
    }
}
