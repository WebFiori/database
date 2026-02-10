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

class SchemaIntegrationTest extends TestCase {

    protected function tearDown(): void {
        gc_collect_cycles();
        parent::tearDown();
    }
    
    private function getConnectionInfo(): ConnectionInfo {
        return new ConnectionInfo('mysql', 'root', getenv('MYSQL_ROOT_PASSWORD'), 'testing_db', '127.0.0.1');
    }
    
    /**
     * @test
     */
    public function testSchemaRunnerWithoutConnection() {
        $runner = new SchemaRunner($this->getConnectionInfo());
        $runner->registerAll([TestMigration::class, TestSeeder::class]);
        
        $changes = $runner->getChanges();
        $this->assertCount(2, $changes);
    }
    
    /**
     * @test
     */
    public function testFullSchemaWorkflow() {
        $runner = new SchemaRunner($this->getConnectionInfo());
        $runner->registerAll([TestMigration::class, TestSeeder::class]);
        
        $changes = $runner->getChanges();
        $this->assertCount(2, $changes);
    }
    
    /**
     * @test
     */
    public function testEnvironmentFiltering() {
        $runner = new SchemaRunner($this->getConnectionInfo());
        $runner->registerAll([TestMigration::class, TestSeeder::class]);
        
        $changes = $runner->getChanges();
        $this->assertCount(2, $changes);
    }
}
