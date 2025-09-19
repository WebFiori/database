<?php

namespace WebFiori\Tests\Database\Schema;

use WebFiori\Database\Database;
use WebFiori\Database\Schema\AbstractSeeder;

class TestSeeder extends AbstractSeeder {
    
    public function getDependencies(): array {
        return [TestMigration::class];
    }
    
    public function getEnvironments(): array {
        return ['dev', 'test'];
    }
    
    public function execute(Database $db): void {
        // Insert test data
        $db->table('test_table')->insert([
            'name' => 'Test User 1'
        ])->execute();
        
        $db->table('test_table')->insert([
            'name' => 'Test User 2'
        ])->execute();
    }
    
    public function rollback(Database $db): void {
        // Delete test data
        $db->table('test_table')->delete()->where('name', 'Test User 1')->execute();
        $db->table('test_table')->delete()->where('name', 'Test User 2')->execute();
    }
}
