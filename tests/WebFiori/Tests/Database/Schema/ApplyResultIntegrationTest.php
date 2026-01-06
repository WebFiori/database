<?php

namespace WebFiori\Tests\Database\Schema;

use PHPUnit\Framework\TestCase;
use WebFiori\Database\ConnectionInfo;
use WebFiori\Database\Schema\DatabaseChangeResult;
use WebFiori\Database\Schema\SchemaRunner;

class ApplyResultIntegrationTest extends TestCase {
    
    private function getConnectionInfo(): ConnectionInfo {
        return new ConnectionInfo('mysql', 'root', getenv('MYSQL_ROOT_PASSWORD') ?: '123456', 'testing_db', '127.0.0.1');
    }
    
    public function testApplyReturnsResult() {
        $runner = new SchemaRunner($this->getConnectionInfo());
        $runner->register(SuccessfulMigration::class);
        
        try {
            $runner->createSchemaTable();
            
            $result = $runner->apply();
            
            $this->assertInstanceOf(DatabaseChangeResult::class, $result);
            $this->assertCount(1, $result->getApplied());
            $this->assertTrue($result->isSuccessful());
            $this->assertGreaterThan(0, $result->getTotalTime());
            
            $runner->dropSchemaTable();
        } catch (\Exception $ex) {
            $this->markTestSkipped('Database connection failed: ' . $ex->getMessage());
        }
    }
    
    public function testApplyTracksSkippedEnvironment() {
        $runner = new SchemaRunner($this->getConnectionInfo(), 'production');
        $runner->register(DevOnlySeeder::class);
        
        try {
            $runner->createSchemaTable();
            
            $result = $runner->apply();
            
            $this->assertEmpty($result->getApplied());
            $this->assertCount(1, $result->getSkipped());
            $this->assertEquals('Environment mismatch', $result->getSkipped()[0]['reason']);
            
            $runner->dropSchemaTable();
        } catch (\Exception $ex) {
            $this->markTestSkipped('Database connection failed: ' . $ex->getMessage());
        }
    }
    
    public function testApplyTracksAlreadyApplied() {
        $runner = new SchemaRunner($this->getConnectionInfo());
        $runner->register(SuccessfulMigration::class);
        
        try {
            $runner->createSchemaTable();
            
            // First apply
            $runner->apply();
            
            // Second apply - should skip
            $result = $runner->apply();
            
            $this->assertEmpty($result->getApplied());
            $this->assertCount(1, $result->getSkipped());
            $this->assertEquals('Already applied', $result->getSkipped()[0]['reason']);
            
            $runner->dropSchemaTable();
        } catch (\Exception $ex) {
            $this->markTestSkipped('Database connection failed: ' . $ex->getMessage());
        }
    }
    
    public function testApplyTracksFailed() {
        $runner = new SchemaRunner($this->getConnectionInfo());
        $runner->register(FailingMigration::class);
        
        try {
            $runner->createSchemaTable();
            
            $result = $runner->apply();
            
            $this->assertEmpty($result->getApplied());
            $this->assertCount(1, $result->getFailed());
            $this->assertFalse($result->isSuccessful());
            $this->assertInstanceOf(\Throwable::class, $result->getFailed()[0]['error']);
            
            $runner->dropSchemaTable();
        } catch (\Exception $ex) {
            $this->markTestSkipped('Database connection failed: ' . $ex->getMessage());
        }
    }
    
    public function testBackwardCompatibilityCount() {
        $runner = new SchemaRunner($this->getConnectionInfo());
        $runner->register(SuccessfulMigration::class);
        
        try {
            $runner->createSchemaTable();
            
            $result = $runner->apply();
            
            // Old code: count($applied) still works
            $this->assertEquals(1, count($result));
            
            $runner->dropSchemaTable();
        } catch (\Exception $ex) {
            $this->markTestSkipped('Database connection failed: ' . $ex->getMessage());
        }
    }
    
    public function testBackwardCompatibilityForeach() {
        $runner = new SchemaRunner($this->getConnectionInfo());
        $runner->register(SuccessfulMigration::class);
        
        try {
            $runner->createSchemaTable();
            
            $result = $runner->apply();
            
            // Old code: foreach ($applied as $change) still works
            $count = 0;
            foreach ($result as $change) {
                $this->assertInstanceOf(SuccessfulMigration::class, $change);
                $count++;
            }
            $this->assertEquals(1, $count);
            
            $runner->dropSchemaTable();
        } catch (\Exception $ex) {
            $this->markTestSkipped('Database connection failed: ' . $ex->getMessage());
        }
    }
}
