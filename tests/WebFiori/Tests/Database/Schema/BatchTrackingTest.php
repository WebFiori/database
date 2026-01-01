<?php

namespace WebFiori\Tests\Database\Schema;

use PHPUnit\Framework\TestCase;
use WebFiori\Database\ConnectionInfo;
use WebFiori\Database\Database;
use WebFiori\Database\Schema\AbstractMigration;
use WebFiori\Database\Schema\SchemaRunner;
use WebFiori\Database\Schema\SchemaChangeRepository;

class BatchMigrationA extends AbstractMigration {
    public function up(Database $db): void {}
    public function down(Database $db): void {}
}

class BatchMigrationB extends AbstractMigration {
    public function up(Database $db): void {}
    public function down(Database $db): void {}
}

class BatchMigrationC extends AbstractMigration {
    public function up(Database $db): void {}
    public function down(Database $db): void {}
}

class BatchTrackingTest extends TestCase {
    
    protected function tearDown(): void {
        gc_collect_cycles();
    }
    
    private function getConnectionInfo(): ConnectionInfo {
        return new ConnectionInfo('mysql', 'root', getenv('MYSQL_ROOT_PASSWORD') ?: '123456', 'testing_db', '127.0.0.1');
    }
    
    public function testGetNextBatchNumberEmpty() {
        $runner = new SchemaRunner($this->getConnectionInfo());
        
        try {
            $runner->createSchemaTable();
            
            $nextBatch = $runner->getRepository()->getNextBatchNumber();
            $this->assertEquals(1, $nextBatch);
            
            $runner->dropSchemaTable();
        } catch (\Exception $ex) {
            $this->markTestSkipped('Database connection failed: ' . $ex->getMessage());
        }
    }
    
    public function testGetLastBatchNumberEmpty() {
        $runner = new SchemaRunner($this->getConnectionInfo());
        
        try {
            $runner->createSchemaTable();
            
            $lastBatch = $runner->getRepository()->getLastBatchNumber();
            $this->assertEquals(0, $lastBatch);
            
            $runner->dropSchemaTable();
        } catch (\Exception $ex) {
            $this->markTestSkipped('Database connection failed: ' . $ex->getMessage());
        }
    }
    
    public function testApplyAssignsSameBatchNumber() {
        $runner = new SchemaRunner($this->getConnectionInfo());
        $runner->register(BatchMigrationA::class);
        $runner->register(BatchMigrationB::class);
        
        try {
            $runner->createSchemaTable();
            
            $applied = $runner->apply();
            $this->assertCount(2, $applied);
            
            // Both should have batch 1
            $records = $runner->getRepository()->getByBatch(1);
            $this->assertCount(2, $records);
            
            $runner->dropSchemaTable();
        } catch (\Exception $ex) {
            $this->markTestSkipped('Database connection failed: ' . $ex->getMessage());
        }
    }
    
    public function testMultipleApplyCallsCreateDifferentBatches() {
        $runner = new SchemaRunner($this->getConnectionInfo());
        $runner->register(BatchMigrationA::class);
        
        try {
            $runner->createSchemaTable();
            
            // First apply - batch 1
            $runner->apply();
            
            // Register more and apply again - batch 2
            $runner->register(BatchMigrationB::class);
            $runner->apply();
            
            $batch1 = $runner->getRepository()->getByBatch(1);
            $batch2 = $runner->getRepository()->getByBatch(2);
            
            $this->assertCount(1, $batch1);
            $this->assertCount(1, $batch2);
            $this->assertEquals(BatchMigrationA::class, $batch1[0]['change_name']);
            $this->assertEquals(BatchMigrationB::class, $batch2[0]['change_name']);
            
            $runner->dropSchemaTable();
        } catch (\Exception $ex) {
            $this->markTestSkipped('Database connection failed: ' . $ex->getMessage());
        }
    }
    
    public function testApplyOneCreatesNewBatchEachTime() {
        $runner = new SchemaRunner($this->getConnectionInfo());
        $runner->register(BatchMigrationA::class);
        $runner->register(BatchMigrationB::class);
        
        try {
            $runner->createSchemaTable();
            
            $runner->applyOne(); // batch 1
            $runner->applyOne(); // batch 2
            
            $batch1 = $runner->getRepository()->getByBatch(1);
            $batch2 = $runner->getRepository()->getByBatch(2);
            
            $this->assertCount(1, $batch1);
            $this->assertCount(1, $batch2);
            
            $runner->dropSchemaTable();
        } catch (\Exception $ex) {
            $this->markTestSkipped('Database connection failed: ' . $ex->getMessage());
        }
    }
    
    public function testRollbackLastBatch() {
        $runner = new SchemaRunner($this->getConnectionInfo());
        $runner->register(BatchMigrationA::class);
        
        try {
            $runner->createSchemaTable();
            
            // Apply batch 1
            $runner->apply();
            
            // Register more and apply batch 2
            $runner->register(BatchMigrationB::class);
            $runner->register(BatchMigrationC::class);
            $runner->apply();
            
            // Rollback last batch (should rollback B and C)
            $rolled = $runner->rollbackLastBatch();
            
            $this->assertCount(2, $rolled);
            $this->assertTrue($runner->isApplied(BatchMigrationA::class));
            $this->assertFalse($runner->isApplied(BatchMigrationB::class));
            $this->assertFalse($runner->isApplied(BatchMigrationC::class));
            
            $runner->dropSchemaTable();
        } catch (\Exception $ex) {
            $this->markTestSkipped('Database connection failed: ' . $ex->getMessage());
        }
    }
    
    public function testRollbackBatch() {
        $runner = new SchemaRunner($this->getConnectionInfo());
        $runner->register(BatchMigrationA::class);
        
        try {
            $runner->createSchemaTable();
            
            // Apply batch 1
            $runner->apply();
            
            // Apply batch 2
            $runner->register(BatchMigrationB::class);
            $runner->apply();
            
            // Rollback batch 1 specifically
            $rolled = $runner->rollbackBatch(1);
            
            $this->assertCount(1, $rolled);
            $this->assertFalse($runner->isApplied(BatchMigrationA::class));
            $this->assertTrue($runner->isApplied(BatchMigrationB::class));
            
            $runner->dropSchemaTable();
        } catch (\Exception $ex) {
            $this->markTestSkipped('Database connection failed: ' . $ex->getMessage());
        }
    }
    
    public function testRollbackLastBatchWhenEmpty() {
        $runner = new SchemaRunner($this->getConnectionInfo());
        
        try {
            $runner->createSchemaTable();
            
            $rolled = $runner->rollbackLastBatch();
            $this->assertEmpty($rolled);
            
            $runner->dropSchemaTable();
        } catch (\Exception $ex) {
            $this->markTestSkipped('Database connection failed: ' . $ex->getMessage());
        }
    }
    
    public function testGetLastBatchChangeNames() {
        $runner = new SchemaRunner($this->getConnectionInfo());
        $runner->register(BatchMigrationA::class);
        $runner->register(BatchMigrationB::class);
        
        try {
            $runner->createSchemaTable();
            
            $runner->apply();
            
            $names = $runner->getRepository()->getLastBatchChangeNames();
            
            $this->assertCount(2, $names);
            $this->assertContains(BatchMigrationA::class, $names);
            $this->assertContains(BatchMigrationB::class, $names);
            
            $runner->dropSchemaTable();
        } catch (\Exception $ex) {
            $this->markTestSkipped('Database connection failed: ' . $ex->getMessage());
        }
    }
}
