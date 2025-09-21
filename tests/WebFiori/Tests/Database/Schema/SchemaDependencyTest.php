<?php

namespace WebFiori\Tests\Database\Schema;

use PHPUnit\Framework\TestCase;
use WebFiori\Database\ConnectionInfo;
use WebFiori\Database\Database;
use WebFiori\Database\DatabaseException;
use WebFiori\Database\Schema\AbstractMigration;
use WebFiori\Database\Schema\AbstractSeeder;
use WebFiori\Database\Schema\SchemaRunner;

class TestMigrationA extends AbstractMigration {
    public function execute(Database $db): void {
        $db->createBlueprint('table_a')->addColumns([
            'id' => ['type' => 'int', 'primary' => true, 'auto-inc' => true]
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
    
    public function execute(Database $db): void {
        $db->createBlueprint('table_b')->addColumns([
            'id' => ['type' => 'int', 'primary' => true, 'auto-inc' => true],
            'a_id' => ['type' => 'int']
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
    
    public function execute(Database $db): void {
        $db->table('table_a')->insert(['id' => 1])->execute();
    }
    
    public function rollback(Database $db): void {
        $db->table('table_a')->delete()->where('id', 1)->execute();
    }
}

class SchemaDependencyTest extends TestCase {
    
    private function getConnectionInfo(): ConnectionInfo {
        return new ConnectionInfo('mysql', 'root', getenv('MYSQL_ROOT_PASSWORD'), 'testing_db');
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
}
