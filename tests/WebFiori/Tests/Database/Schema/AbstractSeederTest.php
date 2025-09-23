<?php

namespace WebFiori\Tests\Database\Schema;

use PHPUnit\Framework\TestCase;
use WebFiori\Database\Database;
use WebFiori\Database\Schema\AbstractSeeder;

class AbstractSeederTest extends TestCase {
    
    public function testGetType() {
        $seeder = new class extends AbstractSeeder {
            public function run(Database $db): bool {  }
        };
        
        $this->assertEquals('seeder', $seeder->getType());
    }
    
    public function testGetEnvironments() {
        $seeder = new class extends AbstractSeeder {
            public function run(Database $db): bool {  }
        };
        
        $this->assertEquals([], $seeder->getEnvironments());
    }
    
    public function testCustomEnvironments() {
        $seeder = new class extends AbstractSeeder {
            public function run(Database $db): bool {  }
            public function getEnvironments(): array { return ['dev', 'test']; }
        };
        
        $this->assertEquals(['dev', 'test'], $seeder->getEnvironments());
    }
}
