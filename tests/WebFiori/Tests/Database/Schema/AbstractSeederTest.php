<?php

namespace WebFiori\Tests\Database\Schema;

use PHPUnit\Framework\TestCase;
use WebFiori\Database\Database;
use WebFiori\Database\Schema\AbstractSeeder;

class AbstractSeederTest extends TestCase {
    
    public function testGetType() {
        $seeder = new class extends AbstractSeeder {
            public function execute(Database $db): void {}
            public function rollback(Database $db): void {}
        };
        
        $this->assertEquals('seeder', $seeder->getType());
    }
    
    public function testGetEnvironments() {
        $seeder = new class extends AbstractSeeder {
            public function execute(Database $db): void {}
            public function rollback(Database $db): void {}
        };
        
        $this->assertEquals([], $seeder->getEnvironments());
    }
    
}
