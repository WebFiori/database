<?php

namespace WebFiori\Tests\Database\Schema;

use PHPUnit\Framework\TestCase;
use WebFiori\Database\Database;
use WebFiori\Database\Schema\AbstractMigration;

class AbstractMigrationTest extends TestCase {
    
    public function testGetType() {
        $migration = new class extends AbstractMigration {
            public function execute(Database $db): void {}
            public function up(Database $db): bool { return true; }
            public function down(Database $db): bool { return true; }            public function rollback(Database $db): void {}
        };
        
        $this->assertEquals('migration', $migration->getType());
    }
}
