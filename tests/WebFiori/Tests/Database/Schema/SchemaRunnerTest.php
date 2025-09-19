<?php

namespace WebFiori\Tests\Database\Schema;

use PHPUnit\Framework\TestCase;
use WebFiori\Database\ConnectionInfo;
use WebFiori\Database\Database;
use WebFiori\Database\Schema\AbstractMigration;
use WebFiori\Database\Schema\AbstractSeeder;
use WebFiori\Database\Schema\SchemaRunner;

class SchemaRunnerTest extends TestCase {
    
    private function getConnectionInfo(): ConnectionInfo {
        return new ConnectionInfo('mysql', 'root', '123456', 'testing_db');
    }
    
    public function testConstruct() {
        $runner = new SchemaRunner(__DIR__, 'TestNamespace', $this->getConnectionInfo());
        
        $this->assertEquals(__DIR__, $runner->getPath());
        $this->assertEquals('TestNamespace', $runner->getNamespace());
        $this->assertEquals('dev', $runner->getEnvironment());
    }
    
    public function testConstructWithEnvironment() {
        $runner = new SchemaRunner(__DIR__, 'TestNamespace', $this->getConnectionInfo(), 'test');
        
        $this->assertEquals('test', $runner->getEnvironment());
    }
    
    public function testGetChanges() {
        $runner = new SchemaRunner(__DIR__, 'TestNamespace', $this->getConnectionInfo());
        
        $this->assertIsArray($runner->getChanges());
    }
}
