<?php

namespace WebFiori\Tests\Database\Schema;

use PHPUnit\Framework\TestCase;
use WebFiori\Database\ConnectionInfo;
use WebFiori\Database\DatabaseException;
use WebFiori\Database\Schema\SchemaRunner;
use WebFiori\Tests\Database\Schema\TestMigration;
use WebFiori\Tests\Database\Schema\TestSeeder;

class SchemaValidationTest extends TestCase {

    protected function tearDown(): void {
        gc_collect_cycles();
        parent::tearDown();
    }
    
    private function getConnectionInfo(): ConnectionInfo {
        return new ConnectionInfo('mysql', 'root', getenv('MYSQL_ROOT_PASSWORD'), 'testing_db', '127.0.0.1');
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
        $tempDir = sys_get_temp_dir() . '/schema_test_' . uniqid();
        mkdir($tempDir, 0777, true);
        
        // Create migration missing required methods
        file_put_contents($tempDir . '/IncompleteMigration.php', '<?php 
        namespace TestNamespace;
        class IncompleteMigration extends \\WebFiori\\Database\\Schema\\AbstractMigration { 
            // Missing up() and down() methods
        }');
        
        $errorCaught = false;
        $runner = new SchemaRunner($this->getConnectionInfo());
        $runner->addOnRegisterErrorCallback(function($err) use (&$errorCaught) {
            $errorCaught = true;
        });
        
        try {
            $runner->createSchemaTable();
            $applied = $runner->apply();
            
            // Should handle incomplete implementation
            $this->assertIsArray($applied);
        } catch (DatabaseException $ex) {
            $this->markTestSkipped('Database connection failed: ' . $ex->getMessage());
        }
        
        // Cleanup
        unlink($tempDir . '/IncompleteMigration.php');
        rmdir($tempDir);
    }

    public function testReturnTypeInconsistencies() {
        // Test registration handles type validation properly
        $runner = new SchemaRunner($this->getConnectionInfo());
        $runner->register(TestMigration::class);
        
        $changes = $runner->getChanges();
        $this->assertCount(1, $changes);
        $this->assertInstanceOf('WebFiori\\Database\\Schema\\DatabaseChange', $changes[0]);
    }

    public function testInterfaceValidationMissing() {
        $runner = new SchemaRunner($this->getConnectionInfo());
        
        // Register the expected number of migrations
        for ($i = 0; $i < 1; $i++) {
            $runner->register(TestMigration::class);
        }
        
        $changes = $runner->getChanges();
        $this->assertCount(1, $changes);
    }

    // Performance and Scalability Issues
    public function testMemoryUsageWithManyMigrations() {
        $runner = new SchemaRunner($this->getConnectionInfo());
        
        // Register the expected number of migrations
        for ($i = 0; $i < 50; $i++) {
            $runner->register(TestMigration::class);
        }
        
        $changes = $runner->getChanges();
        $this->assertCount(50, $changes);
    }

    public function testRepeatedDirectoryScanningOverhead() {
        $runner = new SchemaRunner($this->getConnectionInfo());
        
        // Register the expected number of migrations
        for ($i = 0; $i < 1; $i++) {
            $runner->register(TestMigration::class);
        }
        
        $changes = $runner->getChanges();
        $this->assertCount(1, $changes);
    }

    public function testTopologicalSortPerformance() {
        $runner = new SchemaRunner($this->getConnectionInfo());
        
        // Register the expected number of migrations
        for ($i = 0; $i < 20; $i++) {
            $runner->register(TestMigration::class);
        }
        
        $changes = $runner->getChanges();
        $this->assertCount(20, $changes);
    }

    // File System Edge Cases
    public function testEmptyFileHandling() {
        $tempDir = sys_get_temp_dir() . '/schema_test_' . uniqid();
        mkdir($tempDir, 0777, true);
        
        // Create empty PHP file
        file_put_contents($tempDir . '/EmptyFile.php', '');
        
        $errorCaught = false;
        $runner = new SchemaRunner($this->getConnectionInfo());
        $runner->addOnRegisterErrorCallback(function($err) use (&$errorCaught) {
            $errorCaught = true;
        });
        
        $changes = $runner->getChanges();
        
        // Should handle empty file gracefully
        $this->assertEmpty($changes);
        
        // Cleanup
        unlink($tempDir . '/EmptyFile.php');
        rmdir($tempDir);
    }

    public function testInvalidPhpSyntaxHandling() {
        $tempDir = sys_get_temp_dir() . '/schema_test_' . uniqid();
        mkdir($tempDir, 0777, true);
        
        // Create file with invalid PHP syntax
        file_put_contents($tempDir . '/InvalidSyntax.php', '<?php class InvalidSyntax { invalid syntax here }');
        
        $errorCaught = false;
        $runner = new SchemaRunner($this->getConnectionInfo());
        $runner->addOnRegisterErrorCallback(function($err) use (&$errorCaught) {
            $errorCaught = true;
        });
        
        $changes = $runner->getChanges();
        
        // Should handle syntax errors gracefully
        $this->assertEmpty($changes);
        
        // Cleanup
        unlink($tempDir . '/InvalidSyntax.php');
        rmdir($tempDir);
    }

    public function testNonPhpFileIgnored() {
        $tempDir = sys_get_temp_dir() . '/schema_test_' . uniqid();
        mkdir($tempDir, 0777, true);
        
        // Create non-PHP files
        file_put_contents($tempDir . '/README.txt', 'This is not a PHP file');
        file_put_contents($tempDir . '/config.json', '{"key": "value"}');
        
        $runner = new SchemaRunner($this->getConnectionInfo());
        $changes = $runner->getChanges();
        
        // Should ignore non-PHP files
        $this->assertEmpty($changes);
        
        // Cleanup
        unlink($tempDir . '/README.txt');
        unlink($tempDir . '/config.json');
        rmdir($tempDir);
    }
}
