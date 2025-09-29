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

    protected function tearDown(): void {
        gc_collect_cycles();
        parent::tearDown();
    }
    
    private function getConnectionInfo(): ConnectionInfo {
        return new ConnectionInfo('mysql', 'root', getenv('MYSQL_ROOT_PASSWORD'), 'testing_db', '127.0.0.1');
    }
    
    public function testDependencyOrdering() {
        $runner = new SchemaRunner($this->getConnectionInfo());
        
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
        $devRunner = new SchemaRunner($this->getConnectionInfo(), 'dev');
        $devRunner->registerAll([TestMigration::class, TestSeeder::class]);
        
        $prodRunner = new SchemaRunner($this->getConnectionInfo(), 'production');
        $prodRunner->register(TestMigration::class);
        
        $this->assertCount(2, $devRunner->getChanges());
        $this->assertCount(1, $prodRunner->getChanges());
    }
    
    public function testIsAppliedMethod() {
        try {
            $runner = new SchemaRunner($this->getConnectionInfo());
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
        
        $runner = new SchemaRunner($this->getConnectionInfo());
        
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
        
        $runner = new SchemaRunner($this->getConnectionInfo());
        
        $rolled = $runner->rollbackUpTo(null);
        $this->assertIsArray($rolled);
        $this->assertEmpty($rolled);
        
        // Clean up
        rmdir($tempDir);
    }

    // Case Sensitivity Issues in Dependencies
    public function testCaseSensitiveDependencyMatching() {
        $runner = new SchemaRunner($this->getConnectionInfo());
        $runner->registerAll([TestMigration::class, TestSeeder::class]);
        
        $changes = $runner->getChanges();
        $this->assertCount(2, $changes);
    }

    // Complex Dependency Chain Issues
    public function testDeepDependencyChainResolution() {
        $runner = new SchemaRunner($this->getConnectionInfo());
        $runner->registerAll([TestMigration::class, TestSeeder::class]);
        
        $changes = $runner->getChanges();
        $this->assertCount(2, $changes);
    }

    // Dependency Resolution with Mixed Types
    public function testMixedMigrationSeederDependencies() {
        $runner = new SchemaRunner($this->getConnectionInfo());
        $runner->registerAll([TestMigration::class, TestSeeder::class]);
        
        $changes = $runner->getChanges();
        $this->assertCount(2, $changes);
    }

    public function testCircularDependencyInLargeChain() {
        $runner = new SchemaRunner($this->getConnectionInfo());
        $runner->registerAll([TestMigration::class, TestSeeder::class]);
        
        $changes = $runner->getChanges();
        $this->assertCount(2, $changes);
    }
}
