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
 * Generator for creating migration and seeder class files.
 * 
 * This class provides methods to generate boilerplate code for database
 * changes, including proper namespace, imports, and method stubs.
 */
class DatabaseChangeGenerator {
    private string $namespace = '';
    private string $path = '';
    private bool $useTimestamp = false;

    /**
     * Create a migration class file.
     * 
     * @param string $name The class name (e.g., 'CreateUsersTable').
     * @param array $options Optional settings:
     *                       - GeneratorOption::DEPENDENCIES: array of class names this migration depends on
     *                       - GeneratorOption::TABLE: table name hint for comments
     * @return string The full path to the created file.
     */
    public function createMigration(string $name, array $options = []): string {
        $dependencies = $options[GeneratorOption::DEPENDENCIES] ?? [];
        $table = $options[GeneratorOption::TABLE] ?? null;

        $content = $this->buildMigrationContent($name, $dependencies, $table);

        return $this->writeFile($name, $content);
    }

    /**
     * Create a seeder class file.
     * 
     * @param string $name The class name (e.g., 'UsersSeeder').
     * @param array $options Optional settings:
     *                       - GeneratorOption::ENVIRONMENTS: array of environments where seeder should run
     *                       - GeneratorOption::DEPENDENCIES: array of class names this seeder depends on
     * @return string The full path to the created file.
     */
    public function createSeeder(string $name, array $options = []): string {
        $environments = $options[GeneratorOption::ENVIRONMENTS] ?? [];
        $dependencies = $options[GeneratorOption::DEPENDENCIES] ?? [];

        $content = $this->buildSeederContent($name, $environments, $dependencies);

        return $this->writeFile($name, $content);
    }

    /**
     * Get the configured namespace.
     */
    public function getNamespace(): string {
        return $this->namespace;
    }

    /**
     * Get the configured path.
     */
    public function getPath(): string {
        return $this->path;
    }

    /**
     * Check if timestamp prefix is enabled.
     */
    public function isTimestampPrefixEnabled(): bool {
        return $this->useTimestamp;
    }

    /**
     * Set the namespace for generated classes.
     * 
     * @param string $namespace The PHP namespace.
     */
    public function setNamespace(string $namespace): self {
        $this->namespace = trim($namespace, '\\');

        return $this;
    }

    /**
     * Set the directory path where generated files will be saved.
     * 
     * @param string $path Absolute path to the directory.
     */
    public function setPath(string $path): self {
        $this->path = rtrim($path, DIRECTORY_SEPARATOR);

        return $this;
    }

    /**
     * Enable or disable timestamp prefix in filenames.
     * 
     * When enabled, files are named like: 2026_01_04_175000_CreateUsersTable.php
     * 
     * @param bool $use True to enable timestamp prefix.
     */
    public function useTimestampPrefix(bool $use): self {
        $this->useTimestamp = $use;

        return $this;
    }

    private function buildMigrationContent(string $name, array $dependencies, ?string $table): string {
        $lines = [];
        $lines[] = '<?php';
        $lines[] = '';

        if ($this->namespace) {
            $lines[] = 'namespace '.$this->namespace.';';
            $lines[] = '';
        }

        $lines[] = 'use WebFiori\Database\Schema\AbstractMigration;';
        $lines[] = 'use WebFiori\Database\Database;';
        $lines[] = '';
        $lines[] = "class {$name} extends AbstractMigration {";

        // Add getDependencies if specified
        if (!empty($dependencies)) {
            $lines[] = '';
            $lines[] = '    public function getDependencies(): array {';
            $lines[] = '        return [';

            foreach ($dependencies as $dep) {
                $lines[] = '            '.$this->formatDependency($dep).',';
            }
            $lines[] = '        ];';
            $lines[] = '    }';
        }

        $lines[] = '';
        $lines[] = '    public function up(Database $db): void {';

        if ($table) {
            $lines[] = "        // TODO: Create or modify table '{$table}'";
        } else {
            $lines[] = '        // TODO: Implement migration';
        }
        $lines[] = '    }';
        $lines[] = '';
        $lines[] = '    public function down(Database $db): void {';

        if ($table) {
            $lines[] = "        // TODO: Reverse changes to table '{$table}'";
        } else {
            $lines[] = '        // TODO: Reverse migration';
        }
        $lines[] = '    }';
        $lines[] = '}';
        $lines[] = '';

        return implode("\n", $lines);
    }

    private function buildSeederContent(string $name, array $environments, array $dependencies): string {
        $lines = [];
        $lines[] = '<?php';
        $lines[] = '';

        if ($this->namespace) {
            $lines[] = 'namespace '.$this->namespace.';';
            $lines[] = '';
        }

        $lines[] = 'use WebFiori\Database\Schema\AbstractSeeder;';
        $lines[] = 'use WebFiori\Database\Database;';
        $lines[] = '';
        $lines[] = "class {$name} extends AbstractSeeder {";

        // Add getEnvironments if specified
        if (!empty($environments)) {
            $lines[] = '';
            $lines[] = '    public function getEnvironments(): array {';
            $lines[] = '        return ['.$this->formatStringArray($environments).'];';
            $lines[] = '    }';
        }

        // Add getDependencies if specified
        if (!empty($dependencies)) {
            $lines[] = '';
            $lines[] = '    public function getDependencies(): array {';
            $lines[] = '        return [';

            foreach ($dependencies as $dep) {
                $lines[] = '            '.$this->formatDependency($dep).',';
            }
            $lines[] = '        ];';
            $lines[] = '    }';
        }

        $lines[] = '';
        $lines[] = '    public function run(Database $db): void {';
        $lines[] = '        // TODO: Implement seeder';
        $lines[] = '    }';
        $lines[] = '}';
        $lines[] = '';

        return implode("\n", $lines);
    }

    private function formatDependency(string $dep): string {
        // If it looks like a fully qualified class name, use ::class syntax
        if (str_contains($dep, '\\')) {
            return $dep.'::class';
        }

        // If it's a simple class name, also use ::class syntax
        if (preg_match('/^[A-Z][a-zA-Z0-9_]*$/', $dep)) {
            return $dep.'::class';
        }

        // Otherwise treat as string
        return "'".$dep."'";
    }

    private function formatStringArray(array $items): string {
        $formatted = array_map(fn($item) => "'{$item}'", $items);

        return implode(', ', $formatted);
    }

    private function writeFile(string $name, string $content): string {
        if (empty($this->path)) {
            throw new \RuntimeException('Path not set. Call setPath() first.');
        }

        if (!is_dir($this->path)) {
            mkdir($this->path, 0755, true);
        }

        $filename = $this->useTimestamp 
            ? date('Y_m_d_His').'_'.$name.'.php'
            : $name.'.php';

        $fullPath = $this->path.DIRECTORY_SEPARATOR.$filename;
        file_put_contents($fullPath, $content);

        return $fullPath;
    }
}
