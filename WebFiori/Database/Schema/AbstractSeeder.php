<?php

namespace WebFiori\Database\Schema;

/**
 * Base class for database seeders that handle data population.
 *
 * @author Ibrahim
 */
abstract class AbstractSeeder extends DatabaseChange {
    /**
     * Get the type of database change.
     * 
     * @return string Always returns 'seeder'.
     */
    public function getType(): string {
        return 'seeder';
    }
    
    /**
     * Get the environments where this seeder should run.
     * 
     * @return array Array of environment names. Empty array means all environments.
     */
    public function getEnvironments(): array {
        return [];
    }
}
