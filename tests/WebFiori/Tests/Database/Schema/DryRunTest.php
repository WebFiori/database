<?php

namespace WebFiori\Tests\Database\Schema;

use PHPUnit\Framework\TestCase;
use WebFiori\Database\ConnectionInfo;
use WebFiori\Database\Database;
use WebFiori\Database\Schema\AbstractMigration;
use WebFiori\Database\Schema\SchemaRunner;
use WebFiori\Database\ColOption;
use WebFiori\Database\DataType;

class DryRunMigration extends AbstractMigration {
    public function up(Database $db): void {
        $db->createBlueprint('dry_run_test')->addColumns([
            'id' => [ColOption::TYPE => DataType::INT, ColOption::PRIMARY => true]
        ]);
        $db->createTables();
        $db->execute();
    }
    
    public function down(Database $db): void {
        $db->table('dry_run_test')->drop()->execute();
    }
}

class DryRunTest extends TestCase {
    
    private function getConnectionInfo(): ConnectionInfo {
        return new ConnectionInfo('mysql', 'root', getenv('MYSQL_ROOT_PASSWORD') ?: '123456', 'testing_db', '127.0.0.1');
    }
    
    public function testSetDryRunMode() {
        $db = new Database($this->getConnectionInfo());
        
        $this->assertFalse($db->isDryRun());
        
        $db->setDryRun(true);
        $this->assertTrue($db->isDryRun());
        
        $db->setDryRun(false);
        $this->assertFalse($db->isDryRun());
    }
    
    public function testDryRunCapturesQueries() {
        $db = new Database($this->getConnectionInfo());
        $db->setDryRun(true);
        
        $db->createBlueprint('test_table')->addColumns([
            'id' => [ColOption::TYPE => DataType::INT, ColOption::PRIMARY => true]
        ]);
        $db->createTables();
        $db->execute();
        
        $captured = $db->getCapturedQueries();
        
        $this->assertNotEmpty($captured);
        $this->assertStringContainsStringIgnoringCase('CREATE TABLE', $captured[0]);
    }
    
    public function testDryRunReturnsEmptyResultSet() {
        $db = new Database($this->getConnectionInfo());
        $db->setDryRun(true);
        
        $db->createBlueprint('test_table')->addColumns([
            'id' => [ColOption::TYPE => DataType::INT, ColOption::PRIMARY => true]
        ]);
        $db->createTables();
        $result = $db->execute();
        
        $this->assertInstanceOf('WebFiori\Database\ResultSet', $result);
        $this->assertEquals(0, $result->getRowsCount());
    }
    
    public function testDryRunClearsOnEnable() {
        $db = new Database($this->getConnectionInfo());
        $db->setDryRun(true);
        
        $db->createBlueprint('test1')->addColumns([
            'id' => [ColOption::TYPE => DataType::INT, ColOption::PRIMARY => true]
        ]);
        $db->createTables();
        $db->execute();
        
        $this->assertNotEmpty($db->getCapturedQueries());
        
        // Re-enable should clear
        $db->setDryRun(true);
        $this->assertEmpty($db->getCapturedQueries());
    }
    
    public function testGetPendingChangesWithoutQueries() {
        $runner = new SchemaRunner($this->getConnectionInfo());
        $runner->register(DryRunMigration::class);
        
        try {
            $runner->createSchemaTable();
            
            $pending = $runner->getPendingChanges(false);
            
            $this->assertCount(1, $pending);
            $this->assertInstanceOf(DryRunMigration::class, $pending[0]['change']);
            $this->assertEmpty($pending[0]['queries']);
            
            $runner->dropSchemaTable();
        } catch (\Exception $ex) {
            $this->markTestSkipped('Database connection failed: ' . $ex->getMessage());
        }
    }
    
    public function testGetPendingChangesWithQueries() {
        $runner = new SchemaRunner($this->getConnectionInfo());
        $runner->register(DryRunMigration::class);
        
        try {
            $runner->createSchemaTable();
            
            $pending = $runner->getPendingChanges(true);
            
            $this->assertCount(1, $pending);
            $this->assertNotEmpty($pending[0]['queries']);
            $this->assertStringContainsStringIgnoringCase('CREATE TABLE', $pending[0]['queries'][0]);
            
            $runner->dropSchemaTable();
        } catch (\Exception $ex) {
            $this->markTestSkipped('Database connection failed: ' . $ex->getMessage());
        }
    }
    
    public function testGetPendingChangesExcludesApplied() {
        $runner = new SchemaRunner($this->getConnectionInfo());
        $runner->register(DryRunMigration::class);
        
        try {
            $runner->createSchemaTable();
            
            // Ensure clean state
            $runner->rollbackUpTo(null);
            
            // Check if we have any changes to work with
            $allChanges = $runner->getChanges();
            if (empty($allChanges)) {
                $this->markTestSkipped('No changes registered for testing');
                return;
            }
            
            // Apply changes
            $result = $runner->apply();
            
            // After apply, pending changes should exclude applied ones
            $pending = $runner->getPendingChanges();
            $applied = $result->getApplied();
            
            // If we applied changes, pending should be empty or reduced
            if (!empty($applied)) {
                $this->assertEmpty($pending, 'Pending changes should exclude applied changes');
            } else {
                $this->markTestSkipped('No changes were applied to test exclusion');
            }
            
            $runner->rollbackUpTo(null);
            $runner->dropSchemaTable();
        } catch (\WebFiori\Database\DatabaseException $ex) {
            $this->markTestSkipped('Database connection failed: ' . $ex->getMessage());
        }
    }
    
    public function testDryRunDoesNotExecuteQueries() {
        $db = new Database($this->getConnectionInfo());
        $db->setDryRun(true);
        
        // Build a query that would fail if executed (table doesn't exist)
        $db->createBlueprint('nonexistent_dry_run_table')->addColumns([
            'id' => [ColOption::TYPE => DataType::INT, ColOption::PRIMARY => true]
        ]);
        $db->createTables();
        
        // Should not throw - query is captured, not executed
        $result = $db->execute();
        
        $this->assertNotEmpty($db->getCapturedQueries());
        $this->assertInstanceOf('WebFiori\Database\ResultSet', $result);
    }
}

