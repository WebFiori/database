<?php

use WebFiori\Database\Database;
use WebFiori\Database\Schema\AbstractSeeder;

/**
 * Seeder for populating the users table with initial user data.
 */
class UsersSeeder extends AbstractSeeder {
    public function run(Database $db): void {
        $users = [
            [
                'username' => 'admin',
                'email' => 'admin@example.com',
                'full-name' => 'Administrator',
                'role' => 'admin'
            ],
            [
                'username' => 'mohammed_ali',
                'email' => 'mohammed@example.com',
                'full-name' => 'Mohammed Ali Al-Rashid',
                'role' => 'user'
            ],
            [
                'username' => 'zahra_hassan',
                'email' => 'zahra@example.com',
                'full-name' => 'Zahra Hassan Al-Mahmoud',
                'role' => 'user'
            ],
            [
                'username' => 'omar_khalil',
                'email' => 'omar@example.com',
                'full-name' => 'Omar Khalil Al-Najjar',
                'role' => 'moderator'
            ]
        ];

        foreach ($users as $user) {
            $db->table('users')->insert($user)->execute();
        }
    }
}
