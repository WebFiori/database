<?php

namespace WebFiori\Tests\Database\Schema;

use PHPUnit\Framework\TestCase;
use WebFiori\Database\ConnectionInfo;
use WebFiori\Database\Database;
use WebFiori\Database\DatabaseException;
use WebFiori\Database\Schema\AbstractMigration;
use WebFiori\Database\Schema\AbstractSeeder;
use WebFiori\Database\Schema\SchemaRunner;

class SchemaIntegrationTest extends TestCase {
    
    private function getConnectionInfo(): ConnectionInfo {
        return new ConnectionInfo('mysql', 'root', '123456', 'testing_db');
    }
    
    /**
     * @test
     */
    public function testSchemaRunnerWithoutConnection() {
        $this->expectException(DatabaseException::class);
        $runner = new SchemaRunner(__DIR__, 'WebFiori\\Tests\\Database\\Schema', null);
        $runner->createSchemaTable();
    }
    
    /**
     * @test
     */
    public function testFullSchemaWorkflow() {
        try {
            $runner = new SchemaRunner(__DIR__, 'WebFiori\\Tests\\Database\\Schema', $this->getConnectionInfo());
            
            // Create schema tracking table
            $runner->createSchemaTable();
            
            // Check that we have changes detected
            $changes = $runner->getChanges();
            $this->assertGreaterThan(0, count($changes));
            
            // Apply all changes
            $applied = $runner->apply();
            $this->assertIsArray($applied);
            
            // Test that changes are marked as applied
            foreach ($applied as $change) {
                $this->assertTrue($runner->isApplied($change->getName()));
            }
            
            // Test rollback
            if (!empty($applied)) {
                $rolled = $runner->rollbackUpTo(null);
                $this->assertIsArray($rolled);
                $this->assertGreaterThan(0, count($rolled));
            }
            
            // Clean up
            $runner->dropSchemaTable();
            
        } catch (DatabaseException $ex) {
            // Skip test if database connection fails
            $this->markTestSkipped('Database connection failed: ' . $ex->getMessage());
        }
    }
    
    /**
     * @test
     */
    public function testEnvironmentFiltering() {
        try {
            $runner = new SchemaRunner(__DIR__, 'WebFiori\\Tests\\Database\\Schema', $this->getConnectionInfo(), 'prod');
            
            $runner->createSchemaTable();
            
            // Apply changes - seeders with environment restrictions should be skipped
            $applied = $runner->apply();
            
            // Check that only migrations were applied (seeders should be filtered out in prod)
            $migrationCount = 0;
            $seederCount = 0;
            
            foreach ($applied as $change) {
                if ($change instanceof AbstractMigration) {
                    $migrationCount++;
                } elseif ($change instanceof AbstractSeeder) {
                    $seederCount++;
                }
            }
            
            $this->assertGreaterThan(0, $migrationCount);
            // In prod environment, test seeder should not run
            $this->assertEquals(0, $seederCount);
            
            $runner->dropSchemaTable();
            
        } catch (DatabaseException $ex) {
            $this->markTestSkipped('Database connection failed: ' . $ex->getMessage());
        }
    }
}
