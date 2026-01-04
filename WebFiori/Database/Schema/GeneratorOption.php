<?php
/**
 * This file is licensed under MIT License.
 * 
 * Copyright (c) 2026-present WebFiori Framework
 * 
 * For more information on the license, please visit: 
 * https://github.com/WebFiori/.github/blob/main/LICENSE
 * 
 */
namespace WebFiori\Database\Schema;

/**
 * Constants for DatabaseChangeGenerator options.
 */
class GeneratorOption {
    /**
     * Array of class names that the generated change depends on.
     * Used in both migrations and seeders.
     */
    const DEPENDENCIES = 'dependencies';
    
    /**
     * Array of environment names where the seeder should run.
     * Used in seeders only.
     */
    const ENVIRONMENTS = 'environments';
    
    /**
     * Table name hint for generated migration.
     * Adds a TODO comment in up() and down() methods.
     */
    const TABLE = 'table';
}
