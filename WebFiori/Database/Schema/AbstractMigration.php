<?php

namespace WebFiori\Database\Schema;
use WebFiori\Database\Database;

/**
 * Base class for database migrations that handle schema changes.
 *
 * @author Ibrahim
 */
abstract class AbstractMigration extends DatabaseChange {
    /**
     * Get the type of database change.
     * 
     * @return string Always returns 'migration'.
     */
    public function getType(): string {
        return 'migration';
    }
    
    /**
     * Get environments where this migration should run.
     * Migrations run in all environments by default.
     * 
     * @return array Empty array means all environments.
     */
    public function getEnvironments(): array {
        return [];
    }
}
