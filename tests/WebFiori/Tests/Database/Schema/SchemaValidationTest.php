<?php

namespace WebFiori\Tests\Database\Schema;

use PHPUnit\Framework\TestCase;
use WebFiori\Database\ConnectionInfo;
use WebFiori\Database\DatabaseException;
use WebFiori\Database\Schema\SchemaRunner;

class SchemaValidationTest extends TestCase {
    
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
        
        $runner = new SchemaRunner($tempDir, 'TestNamespace', $this->getConnectionInfo());
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
        $runner = new SchemaRunner($tempDir, 'TestNamespace', $this->getConnectionInfo());
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
        $runner = new SchemaRunner($tempDir, 'TestNamespace', $this->getConnectionInfo());
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
        $this->markTestSkipped('PHP fatal errors for method signature incompatibility cannot be caught or handled gracefully. This is a compile-time error that occurs during require_once and cannot be recovered from.');
    }

    public function testInterfaceValidationMissing() {
        $tempDir = sys_get_temp_dir() . '/schema_test_' . uniqid();
        mkdir($tempDir, 0777, true);
        
        // Create class that extends DatabaseChange but doesn't implement required methods properly
        file_put_contents($tempDir . '/BadImplementation.php', '<?php 
        namespace TestNamespace;
        class BadImplementation extends \\WebFiori\\Database\\Schema\\DatabaseChange { 
            public function execute($db): void {} 
            public function rollback($db): void {} 
            public function getType(): string { return "bad"; }
        }');
        
        $runner = new SchemaRunner($tempDir, 'TestNamespace', $this->getConnectionInfo());
        $changes = $runner->getChanges();
        
        // Should detect the change even with bad implementation
        $this->assertCount(1, $changes);
        
        // Cleanup
        unlink($tempDir . '/BadImplementation.php');
        rmdir($tempDir);
    }

    // Performance and Scalability Issues
    public function testMemoryUsageWithManyMigrations() {
        $tempDir = sys_get_temp_dir() . '/schema_test_' . uniqid();
        mkdir($tempDir, 0777, true);
        
        // Create many migration files
        for ($i = 0; $i < 50; $i++) {
            file_put_contents($tempDir . "/Migration{$i}.php", "<?php 
            namespace TestNamespace;
            class Migration{$i} extends \\WebFiori\\Database\\Schema\\AbstractMigration { 
                public function up(\$db): void {} 
                public function down(\$db): void {} 
            }");
        }
        
        $memoryBefore = memory_get_usage();
        $runner = new SchemaRunner($tempDir, 'TestNamespace', $this->getConnectionInfo());
        $changes = $runner->getChanges();
        $memoryAfter = memory_get_usage();
        
        // Should load all migrations but memory usage should be reasonable
        $this->assertCount(50, $changes);
        $memoryUsed = $memoryAfter - $memoryBefore;
        $this->assertLessThan(25 * 1024 * 1024, $memoryUsed, 'Memory usage too high'); // Less than 25MB
        
        // Cleanup
        for ($i = 0; $i < 50; $i++) {
            unlink($tempDir . "/Migration{$i}.php");
        }
        rmdir($tempDir);
    }

    public function testRepeatedDirectoryScanningOverhead() {
        $tempDir = sys_get_temp_dir() . '/schema_test_' . uniqid();
        mkdir($tempDir, 0777, true);
        
        // Create migration
        file_put_contents($tempDir . '/TestMigration.php', '<?php 
        namespace TestNamespace;
        class TestMigration extends \\WebFiori\\Database\\Schema\\AbstractMigration { 
            public function up($db): void {} 
            public function down($db): void {} 
        }');
        
        $startTime = microtime(true);
        
        // Create multiple runners (each scans directory)
        for ($i = 0; $i < 10; $i++) {
            $runner = new SchemaRunner($tempDir, 'TestNamespace', $this->getConnectionInfo());
            $changes = $runner->getChanges();
            $this->assertCount(1, $changes);
        }
        
        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;
        
        // Should complete reasonably quickly
        $this->assertLessThan(2.0, $executionTime, 'Directory scanning too slow');
        
        // Cleanup
        unlink($tempDir . '/TestMigration.php');
        rmdir($tempDir);
    }

    public function testTopologicalSortPerformance() {
        $tempDir = sys_get_temp_dir() . '/schema_test_' . uniqid();
        mkdir($tempDir, 0777, true);
        
        // Create migrations with complex dependency chain
        for ($i = 0; $i < 20; $i++) {
            $deps = $i > 0 ? "public function getDependencies(): array { return [\"TestNamespace\\\\Migration" . ($i-1) . "\"]; }" : "";
            file_put_contents($tempDir . "/Migration{$i}.php", "<?php 
            namespace TestNamespace;
            class Migration{$i} extends \\WebFiori\\Database\\Schema\\AbstractMigration { 
                {$deps}
                public function up(\$db): void {} 
                public function down(\$db): void {} 
            }");
        }
        
        $startTime = microtime(true);
        $runner = new SchemaRunner($tempDir, 'TestNamespace', $this->getConnectionInfo());
        $changes = $runner->getChanges();
        $endTime = microtime(true);
        
        $executionTime = $endTime - $startTime;
        
        // Should sort dependencies efficiently
        $this->assertCount(20, $changes);
        $this->assertLessThan(1.0, $executionTime, 'Topological sort too slow');
        
        // Verify correct order
        for ($i = 0; $i < 20; $i++) {
            $this->assertEquals("TestNamespace\\Migration{$i}", $changes[$i]->getName());
        }
        
        // Cleanup
        for ($i = 0; $i < 20; $i++) {
            unlink($tempDir . "/Migration{$i}.php");
        }
        rmdir($tempDir);
    }

    // File System Edge Cases
    public function testEmptyFileHandling() {
        $tempDir = sys_get_temp_dir() . '/schema_test_' . uniqid();
        mkdir($tempDir, 0777, true);
        
        // Create empty PHP file
        file_put_contents($tempDir . '/EmptyFile.php', '');
        
        $errorCaught = false;
        $runner = new SchemaRunner($tempDir, 'TestNamespace', $this->getConnectionInfo());
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
        $runner = new SchemaRunner($tempDir, 'TestNamespace', $this->getConnectionInfo());
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
        
        $runner = new SchemaRunner($tempDir, 'TestNamespace', $this->getConnectionInfo());
        $changes = $runner->getChanges();
        
        // Should ignore non-PHP files
        $this->assertEmpty($changes);
        
        // Cleanup
        unlink($tempDir . '/README.txt');
        unlink($tempDir . '/config.json');
        rmdir($tempDir);
    }
}
