<?php

namespace WebFiori\Tests\Database\Schema;

use WebFiori\Database\Database;
use WebFiori\Database\Schema\AbstractSeeder;

class TestSeeder extends AbstractSeeder {
    
    public function getDependencies(): array {
        return ['WebFiori\Tests\Database\Schema\TestMigration'];
    }
    
    public function getEnvironments(): array {
        return ['dev', 'test'];
    }
    
    public function run(Database $db): void {
        // Insert test data
        $db->table('user_profiles')->insert([
            'name' => 'Test User 1'
        ])->execute();
        
        $db->table('user_profiles')->insert([
            'name' => 'Test User 2'
        ])->execute();
    }
    
    public function rollback(Database $db): void {
        // Delete test data
        $db->table('user_profiles')->delete()->where('name', 'Test User 1')->execute();
        $db->table('user_profiles')->delete()->where('name', 'Test User 2')->execute();
    }
}
