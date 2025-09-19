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
}
