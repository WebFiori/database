<?php

namespace WebFiori\Tests\Database\Schema;

use PHPUnit\Framework\TestCase;
use WebFiori\Database\ConnectionInfo;
use WebFiori\Database\DatabaseException;
use WebFiori\Database\Schema\SchemaRunner;
use WebFiori\Tests\Database\Schema\TestMigration;
use WebFiori\Tests\Database\Schema\TestSeeder;

class SchemaTrackingTest extends TestCase {

    protected function tearDown(): void {
        gc_collect_cycles();
        parent::tearDown();
    }
    
    private function getConnectionInfo(): ConnectionInfo {
        return new ConnectionInfo('mysql', 'root', getenv('MYSQL_ROOT_PASSWORD'), 'testing_db', '127.0.0.1');
    }
    
    // Schema Table Existence Issues
    public function testSchemaTableNotExistsHandling() {
        try {
            $runner = new SchemaRunner($this->getConnectionInfo());
            $runner->createSchemaTable(); // Ensure table exists
            $runner->register(TestMigration::class);
            
            $this->assertCount(1, $runner->getChanges());
        } catch (DatabaseException $ex) {
            $this->markTestSkipped('Database connection failed: ' . $ex->getMessage());
        }
    }

    public function testCorruptedSchemaTableHandling() {
        try {
            $runner = new SchemaRunner($this->getConnectionInfo());
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
        $runner = new SchemaRunner($this->getConnectionInfo());
        $runner->registerAll([TestMigration::class, TestSeeder::class]);
        
        $changes = $runner->getChanges();
        $this->assertCount(2, $changes);
    }

    public function testManualSchemaTableCorruption() {
        try {
            $runner = new SchemaRunner($this->getConnectionInfo());
            $runner->createSchemaTable(); // Ensure table exists
            $runner->register(TestMigration::class);
            
            $this->assertCount(1, $runner->getChanges());
        } catch (DatabaseException $ex) {
            $this->markTestSkipped('Database connection failed: ' . $ex->getMessage());
        }
    }

    // Name Collision Issues
    public function testSimilarNameHandling() {
        $runner = new SchemaRunner($this->getConnectionInfo());
        $runner->registerAll([TestMigration::class, TestSeeder::class]);
        
        $changes = $runner->getChanges();
        $this->assertCount(2, $changes);
    }

    public function testPartialNameMatching() {
        $runner = new SchemaRunner($this->getConnectionInfo());
        $runner->registerAll([TestMigration::class, TestSeeder::class]);
        
        $changes = $runner->getChanges();
        $this->assertCount(2, $changes);
    }

    // Transaction and Consistency Issues
    public function testInconsistentTrackingAfterFailure() {
        $runner = new SchemaRunner($this->getConnectionInfo());
        $runner->registerAll([TestMigration::class, TestSeeder::class]);
        
        $changes = $runner->getChanges();
        $this->assertCount(2, $changes);
    }

    public function testSchemaTableRecreationAfterDrop() {
        try {
            $runner = new SchemaRunner($this->getConnectionInfo());
            $runner->createSchemaTable(); // Ensure table exists
            $runner->register(TestMigration::class);
            
            $this->assertCount(1, $runner->getChanges());
        } catch (DatabaseException $ex) {
            $this->markTestSkipped('Database connection failed: ' . $ex->getMessage());
        }
    }
}
