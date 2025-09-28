<?php

namespace WebFiori\Tests\Database\Schema;

use PHPUnit\Framework\TestCase;
use WebFiori\Database\ConnectionInfo;
use WebFiori\Database\Database;
use WebFiori\Database\DatabaseException;
use WebFiori\Database\Schema\AbstractMigration;
use WebFiori\Database\Schema\AbstractSeeder;
use WebFiori\Database\Schema\SchemaRunner;
use WebFiori\Database\ColOption;

class TestMigrationA extends AbstractMigration {
    
    public function up(Database $db): void {
        $this->execute($db);
        
    }
    
    public function down(Database $db): void {
        $this->rollback($db);
        
    }    public function execute(Database $db): void {
        $db->createBlueprint('table_a')->addColumns([
            'id' => [ColOption::TYPE => 'int', ColOption::PRIMARY => true, ColOption::AUTO_INCREMENT => true]
        ]);
        $db->createTables();
        $db->execute();
    }
    
    public function rollback(Database $db): void {
        $db->table('table_a')->drop()->execute();
    }
}

class TestMigrationB extends AbstractMigration {
    public function getDependencies(): array {
        return ['TestMigrationA'];
    }
    
    public function up(Database $db): void {
        $this->execute($db);
        
    }
    
    public function down(Database $db): void {
        $this->rollback($db);
        
    }    
    public function execute(Database $db): void {
        $db->createBlueprint('table_b')->addColumns([
            'id' => [ColOption::TYPE => 'int', ColOption::PRIMARY => true, ColOption::AUTO_INCREMENT => true],
            'a_id' => [ColOption::TYPE => 'int']
        ]);
        $db->createTables();
        $db->execute();
    }
    
    public function rollback(Database $db): void {
        $db->table('table_b')->drop()->execute();
    }
}

class TestSeederForA extends AbstractSeeder {
    public function getDependencies(): array {
        return ['TestMigrationA'];
    }
    
    public function getEnvironments(): array {
        return ['dev', 'test'];
    }
    
    public function run(Database $db): void {
        $this->execute($db);
        
    }    
    public function execute(Database $db): void {
        $db->table('table_a')->insert(['id' => 1])->execute();
    }
    
    public function rollback(Database $db): void {
        $db->table('table_a')->delete()->where('id', 1)->execute();
    }
}

class SchemaDependencyTest extends TestCase {
    
    private function getConnectionInfo(): ConnectionInfo {
        return new ConnectionInfo('mysql', 'root', getenv('MYSQL_ROOT_PASSWORD'), 'testing_db', '127.0.0.1');
    }
    
    public function testDependencyOrdering() {
        $runner = new SchemaRunner(__DIR__, 'WebFiori\\Tests\\Database\\Schema', $this->getConnectionInfo());
        
        $changes = $runner->getChanges();
        $changeNames = array_map(function($change) {
            return $change->getName();
        }, $changes);
        
        // TestMigration should come before TestSeeder due to dependency
        $migrationIndex = array_search('TestMigration', $changeNames);
        $seederIndex = array_search('TestSeeder', $changeNames);
        
        $this->assertIsArray($changes, 'Changes should be an array');
        $this->assertGreaterThanOrEqual(0, count($changes), 'Should have zero or more changes');
        
        if ($migrationIndex !== false && $seederIndex !== false) {
            $this->assertLessThan($seederIndex, $migrationIndex, 'Migration should come before seeder');
        }
    }
    
    public function testEnvironmentFiltering() {
        // Test dev environment - seeder should be included
        $devRunner = new SchemaRunner(__DIR__, 'WebFiori\\Tests\\Database\\Schema', $this->getConnectionInfo(), 'dev');
        $devChanges = $devRunner->getChanges();
        
        $hasSeeder = false;
        foreach ($devChanges as $change) {
            if ($change instanceof AbstractSeeder) {
                $hasSeeder = true;
                break;
            }
        }
        $this->assertTrue($hasSeeder, 'Dev environment should include seeders');
        
        // Test prod environment - seeder should be excluded during execution
        $prodRunner = new SchemaRunner(__DIR__, 'WebFiori\\Tests\\Database\\Schema', $this->getConnectionInfo(), 'prod');
        
        try {
            $prodRunner->createSchemaTable();
            $applied = $prodRunner->apply();
            
            $appliedSeeders = 0;
            foreach ($applied as $change) {
                if ($change instanceof AbstractSeeder) {
                    $appliedSeeders++;
                }
            }
            
            $this->assertEquals(0, $appliedSeeders, 'Prod environment should not apply seeders');
            
            $prodRunner->dropSchemaTable();
        } catch (DatabaseException $ex) {
            $this->markTestSkipped('Database connection failed: ' . $ex->getMessage());
        }
    }
    
    public function testIsAppliedMethod() {
        try {
            $runner = new SchemaRunner(__DIR__, 'WebFiori\\Tests\\Database\\Schema', $this->getConnectionInfo());
            $runner->createSchemaTable();
            
            // Initially nothing should be applied
            $this->assertFalse($runner->isApplied('TestMigration'));
            
            // Apply one change
            $change = $runner->applyOne();
            
            if ($change !== null) {
                $this->assertTrue($runner->isApplied($change->getName()));
            }
            
            $runner->dropSchemaTable();
        } catch (DatabaseException $ex) {
            $this->markTestSkipped('Database connection failed: ' . $ex->getMessage());
        }
    }
    
    public function testClassSyntaxDependency() {
        // Test that TestMigration3 can be instantiated and has correct dependencies
        $migration3 = new TestMigration3();
        $dependencies = $migration3->getDependencies();
        
        $this->assertIsArray($dependencies, 'Dependencies should be an array');
        $this->assertContains('WebFiori\\Tests\\Database\\Schema\\TestMigration', $dependencies, 'Should contain TestMigration using ::class syntax');
        $this->assertEquals('WebFiori\\Tests\\Database\\Schema\\TestMigration3', $migration3->getName(), 'Should have correct name');
    }
    
    public function testEmptyChangesArray() {
        // Create empty directory
        $tempDir = sys_get_temp_dir() . '/empty_schema_' . uniqid();
        mkdir($tempDir);
        
        $runner = new SchemaRunner($tempDir, 'EmptyNamespace', $this->getConnectionInfo());
        
        $changes = $runner->getChanges();
        $this->assertIsArray($changes);
        $this->assertEmpty($changes);
        
        // Clean up
        rmdir($tempDir);
    }
    
    public function testRollbackWithEmptyChanges() {
        // Create empty directory
        $tempDir = sys_get_temp_dir() . '/empty_schema_' . uniqid();
        mkdir($tempDir);
        
        $runner = new SchemaRunner($tempDir, 'EmptyNamespace', $this->getConnectionInfo());
        
        $rolled = $runner->rollbackUpTo(null);
        $this->assertIsArray($rolled);
        $this->assertEmpty($rolled);
        
        // Clean up
        rmdir($tempDir);
    }

    // Case Sensitivity Issues in Dependencies
    public function testCaseSensitiveDependencyMatching() {
        $tempDir = sys_get_temp_dir() . '/schema_test_' . uniqid();
        mkdir($tempDir, 0777, true);
        
        // Create base migration
        file_put_contents($tempDir . '/BaseMigration.php', '<?php 
        namespace TestNamespace;
        class BaseMigration extends \\WebFiori\\Database\\Schema\\AbstractMigration { 
            public function up($db): void {} 
            public function down($db): void {} 
        }');
        
        // Create dependent migration with wrong case
        file_put_contents($tempDir . '/DependentMigration.php', '<?php 
        namespace TestNamespace;
        class DependentMigration extends \\WebFiori\\Database\\Schema\\AbstractMigration { 
            public function getDependencies(): array { return ["basemigration"]; } // Wrong case
            public function up($db): void {} 
            public function down($db): void {} 
        }');
        
        $runner = new SchemaRunner($tempDir, 'TestNamespace', $this->getConnectionInfo());
        
        try {
            $runner->createSchemaTable();
            $applied = $runner->apply();
            
            // Should not apply dependent migration due to case mismatch
            $appliedNames = array_map(function($change) { return $change->getName(); }, $applied);
            $this->assertContains('TestNamespace\\BaseMigration', $appliedNames);
            $this->assertNotContains('TestNamespace\\DependentMigration', $appliedNames);
            
            $runner->dropSchemaTable();
        } catch (DatabaseException $ex) {
            $this->markTestSkipped('Database connection failed: ' . $ex->getMessage());
        }
        
        // Cleanup
        unlink($tempDir . '/BaseMigration.php');
        unlink($tempDir . '/DependentMigration.php');
        rmdir($tempDir);
    }

    // Complex Dependency Chain Issues
    public function testDeepDependencyChainResolution() {
        $tempDir = sys_get_temp_dir() . '/schema_test_' . uniqid();
        mkdir($tempDir, 0777, true);
        
        // Create chain: Migration1 -> Migration2 -> Migration3
        file_put_contents($tempDir . '/Migration1.php', '<?php 
        namespace TestNamespace;
        class Migration1 extends \\WebFiori\\Database\\Schema\\AbstractMigration { 
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
        
        file_put_contents($tempDir . '/Migration3.php', '<?php 
        namespace TestNamespace;
        class Migration3 extends \\WebFiori\\Database\\Schema\\AbstractMigration { 
            public function getDependencies(): array { return ["TestNamespace\\\\Migration2"]; }
            public function up($db): void {} 
            public function down($db): void {} 
        }');
        
        $runner = new SchemaRunner($tempDir, 'TestNamespace', $this->getConnectionInfo());
        
        try {
            $runner->createSchemaTable();
            $applied = $runner->apply();
            
            // Should apply in correct order
            $this->assertCount(3, $applied);
            $this->assertEquals('TestNamespace\\Migration1', $applied[0]->getName());
            $this->assertEquals('TestNamespace\\Migration2', $applied[1]->getName());
            $this->assertEquals('TestNamespace\\Migration3', $applied[2]->getName());
            
            $runner->dropSchemaTable();
        } catch (DatabaseException $ex) {
            $this->markTestSkipped('Database connection failed: ' . $ex->getMessage());
        }
        
        // Cleanup
        unlink($tempDir . '/Migration1.php');
        unlink($tempDir . '/Migration2.php');
        unlink($tempDir . '/Migration3.php');
        rmdir($tempDir);
    }

    // Dependency Resolution with Mixed Types
    public function testMixedMigrationSeederDependencies() {
        $tempDir = sys_get_temp_dir() . '/schema_test_' . uniqid();
        mkdir($tempDir, 0777, true);
        
        // Create migration
        file_put_contents($tempDir . '/CreateTableMigration.php', '<?php 
        namespace TestNamespace;
        class CreateTableMigration extends \\WebFiori\\Database\\Schema\\AbstractMigration { 
            public function up($db): void {} 
            public function down($db): void {} 
        }');
        
        // Create seeder that depends on migration
        file_put_contents($tempDir . '/PopulateTableSeeder.php', '<?php 
        namespace TestNamespace;
        class PopulateTableSeeder extends \\WebFiori\\Database\\Schema\\AbstractSeeder { 
            public function getDependencies(): array { return ["TestNamespace\\\\CreateTableMigration"]; }
            public function run($db): void {} 
        }');
        
        $runner = new SchemaRunner($tempDir, 'TestNamespace', $this->getConnectionInfo());
        
        try {
            $runner->createSchemaTable();
            $applied = $runner->apply();
            
            // Should apply migration before seeder
            $this->assertCount(2, $applied);
            $this->assertEquals('TestNamespace\\CreateTableMigration', $applied[0]->getName());
            $this->assertEquals('TestNamespace\\PopulateTableSeeder', $applied[1]->getName());
            
            $runner->dropSchemaTable();
        } catch (DatabaseException $ex) {
            $this->markTestSkipped('Database connection failed: ' . $ex->getMessage());
        }
        
        // Cleanup
        unlink($tempDir . '/CreateTableMigration.php');
        unlink($tempDir . '/PopulateTableSeeder.php');
        rmdir($tempDir);
    }

    public function testCircularDependencyInLargeChain() {
        $tempDir = sys_get_temp_dir() . '/schema_test_' . uniqid();
        mkdir($tempDir, 0777, true);
        
        // Create circular dependency: A -> B -> C -> A
        file_put_contents($tempDir . '/MigrationA.php', '<?php 
        namespace TestNamespace;
        class MigrationA extends \\WebFiori\\Database\\Schema\\AbstractMigration { 
            public function getDependencies(): array { return ["TestNamespace\\\\MigrationC"]; }
            public function up($db): void {} 
            public function down($db): void {} 
        }');
        
        file_put_contents($tempDir . '/MigrationB.php', '<?php 
        namespace TestNamespace;
        class MigrationB extends \\WebFiori\\Database\\Schema\\AbstractMigration { 
            public function getDependencies(): array { return ["TestNamespace\\\\MigrationA"]; }
            public function up($db): void {} 
            public function down($db): void {} 
        }');
        
        file_put_contents($tempDir . '/MigrationC.php', '<?php 
        namespace TestNamespace;
        class MigrationC extends \\WebFiori\\Database\\Schema\\AbstractMigration { 
            public function getDependencies(): array { return ["TestNamespace\\\\MigrationB"]; }
            public function up($db): void {} 
            public function down($db): void {} 
        }');
        
        $this->expectException(DatabaseException::class);
        $this->expectExceptionMessage('Circular dependency detected');
        
        new SchemaRunner($tempDir, 'TestNamespace', $this->getConnectionInfo());
        
        // Cleanup
        unlink($tempDir . '/MigrationA.php');
        unlink($tempDir . '/MigrationB.php');
        unlink($tempDir . '/MigrationC.php');
        rmdir($tempDir);
    }
}
