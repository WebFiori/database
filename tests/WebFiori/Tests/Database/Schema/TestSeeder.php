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
    
    public function run(Database $db): bool {
        // Insert test data
        $db->table('user_profiles')->insert([
            'name' => 'Test User 1'
        ])->execute();
        
        $db->table('user_profiles')->insert([
            'name' => 'Test User 2'
        ])->execute();
        
        return true;
    }
    
    public function rollback(Database $db): void {
        // Delete test data
        $db->table('user_profiles')->delete()->where('name', 'Test User 1')->execute();
        $db->table('user_profiles')->delete()->where('name', 'Test User 2')->execute();
    }
}
