<?php

namespace WebFiori\Tests\Database\Schema;

use WebFiori\Database\Database;
use WebFiori\Database\Schema\AbstractSeeder;

class TestSeeder2 extends AbstractSeeder {
    
    public function getDependencies(): array {
        return ['TestMigration2', 'TestSeeder'];
    }
    
    public function getEnvironments(): array {
        return ['dev'];
    }
    
    public function execute(Database $db): void {
        // Update existing records with email
        $db->table('user_profiles')->update([
            'email' => 'user1@test.com'
        ])->where('name', 'Test User 1')->execute();
        
        $db->table('user_profiles')->update([
            'email' => 'user2@test.com'
        ])->where('name', 'Test User 2')->execute();
    }
    
    public function rollback(Database $db): void {
        // Clear email values
        $db->table('user_profiles')->update([
            'email' => null
        ])->where('name', 'Test User 1')->execute();
        
        $db->table('user_profiles')->update([
            'email' => null
        ])->where('name', 'Test User 2')->execute();
    }
}
