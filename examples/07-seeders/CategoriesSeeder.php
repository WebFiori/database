<?php

use WebFiori\Database\Schema\AbstractSeeder;
use WebFiori\Database\Database;

/**
 * Seeder for populating the categories table with sample category data.
 * 
 * This seeder is environment-specific and only runs in development
 * and test environments to provide sample categories for testing.
 */
class CategoriesSeeder extends AbstractSeeder {
    

    
    /**
     * Get the environments where this seeder should be executed.
     * 
     * This seeder only runs in development and test environments
     * to avoid populating production with sample data.
     * 
     * @return array Array of environment names where this seeder should run.
     */
    public function getEnvironments(): array {
        // Only run in development and test environments
        return ['dev', 'test'];
    }
    
    /**
     * Run the seeder to populate the database with data.
     * 
     * Inserts sample categories for content organization including
     * technology, science, culture, and sports categories.
     * 
     * @param Database $db The database instance to execute seeding on.
     * @return bool True if seeding was successful, false otherwise.
     */
    public function run(Database $db): bool {
        // Insert sample categories
        $categories = [
            [
                'name' => 'Technology',
                'description' => 'Articles about technology and programming',
                'slug' => 'technology'
            ],
            [
                'name' => 'Science',
                'description' => 'Scientific articles and research',
                'slug' => 'science'
            ],
            [
                'name' => 'Culture',
                'description' => 'Cultural topics and discussions',
                'slug' => 'culture'
            ],
            [
                'name' => 'Sports',
                'description' => 'Sports news and updates',
                'slug' => 'sports'
            ]
        ];
        
        foreach ($categories as $category) {
            $db->table('categories')->insert($category)->execute();
        }
        
        return true;
    }
}
