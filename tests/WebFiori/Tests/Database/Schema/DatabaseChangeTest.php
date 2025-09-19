<?php

namespace WebFiori\Tests\Database\Schema;

use PHPUnit\Framework\TestCase;
use WebFiori\Database\Database;
use WebFiori\Database\Schema\DatabaseChange;

class DatabaseChangeTest extends TestCase {
    
    public function testGetDependencies() {
        $change = new class extends DatabaseChange {
            public function execute(Database $db): void {}
            public function rollback(Database $db): void {}
            public function getType(): string { return 'test'; }
        };
        
        $this->assertEquals([], $change->getDependencies());
    }
    
    public function testGetName() {
        $change = new class extends DatabaseChange {
            public function execute(Database $db): void {}
            public function rollback(Database $db): void {}
            public function getType(): string { return 'test'; }
        };
        
        $this->assertStringContainsString('DatabaseChange@anonymous', $change->getName());
    }
}
