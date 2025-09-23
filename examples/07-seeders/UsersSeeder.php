<?php

use WebFiori\Database\Schema\AbstractSeeder;
use WebFiori\Database\Database;

/**
 * Seeder for populating the users table with initial user data.
 * 
 * This seeder creates essential user accounts including an administrator
 * and sample users for development and testing purposes.
 */
class UsersSeeder extends AbstractSeeder {
    

    
    /**
     * Run the seeder to populate the database with data.
     * 
     * Inserts sample user accounts with different roles including
     * an administrator account and regular users with Arabic names.
     * 
     * @param Database $db The database instance to execute seeding on.
     * @return bool True if seeding was successful, false otherwise.
     */
    public function run(Database $db): void {
        // Insert sample users
        $users = [
            [
                'username' => 'admin',
                'email' => 'admin@example.com',
                'full_name' => 'Administrator',
                'role' => 'admin'
            ],
            [
                'username' => 'mohammed_ali',
                'email' => 'mohammed@example.com',
                'full_name' => 'Mohammed Ali Al-Rashid',
                'role' => 'user'
            ],
            [
                'username' => 'zahra_hassan',
                'email' => 'zahra@example.com',
                'full_name' => 'Zahra Hassan Al-Mahmoud',
                'role' => 'user'
            ],
            [
                'username' => 'omar_khalil',
                'email' => 'omar@example.com',
                'full_name' => 'Omar Khalil Al-Najjar',
                'role' => 'moderator'
            ]
        ];
        
        foreach ($users as $user) {
            $db->table('users')->insert($user)->execute();
        }
        
    }
}
