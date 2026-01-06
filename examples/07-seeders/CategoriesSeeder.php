<?php

use WebFiori\Database\Database;
use WebFiori\Database\Schema\AbstractSeeder;

/**
 * Seeder for populating the categories table with sample category data.
 */
class CategoriesSeeder extends AbstractSeeder {
    public function getEnvironments(): array {
        return ['dev', 'test'];
    }

    public function run(Database $db): void {
        $categories = [
            ['name' => 'Technology', 'description' => 'Articles about technology', 'slug' => 'technology'],
            ['name' => 'Science', 'description' => 'Scientific articles', 'slug' => 'science'],
            ['name' => 'Culture', 'description' => 'Cultural topics', 'slug' => 'culture'],
            ['name' => 'Sports', 'description' => 'Sports news', 'slug' => 'sports']
        ];

        foreach ($categories as $category) {
            $db->table('categories')->insert($category)->execute();
        }
    }
}
