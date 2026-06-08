<?php

/**
 * This file is licensed under MIT License.
 * 
 * Copyright (c) 2025-present WebFiori Framework
 * 
 * For more information on the license, please visit: 
 * https://github.com/WebFiori/.github/blob/main/LICENSE
 * 
 */
namespace WebFiori\Database\Schema;

use Error;
use ReflectionClass;
use WebFiori\Database\Attributes\AttributeTableBuilder;
use WebFiori\Database\ConnectionInfo;
use WebFiori\Database\Database;
use WebFiori\Database\DatabaseException;
use WebFiori\Database\DataType;

/**
 * Database schema management system for executing migrations and seeders.
 * 
 * The SchemaRunner provides a comprehensive system for managing database
 * schema changes over time. It handles:
 * 
 * **Migration Management:**
 * - Automatic discovery of migration classes
 * - Dependency resolution and execution ordering
 * - Tracking of applied migrations
 * - Rollback capabilities for reversing changes
 * 
 * **Seeder Management:**
 * - Data population after schema changes
 * - Environment-specific seeding
 * - Bulk data insertion capabilities
 * 
 * **Features:**
 * - Environment-aware execution (dev, test, prod)
 * - Error handling with custom callbacks
 * - Registration validation for change classes
 * - Automatic class loading from specified paths
 * - Transaction support for atomic operations
 * 
 * **Usage Example:**
 * ```php
 * $runner = new SchemaRunner($connectionInfo);
 * $runner->setPath(/path/to/migrations);
 * $runner->setNamespace(App\Migrations);
 * $runner->setEnvironment(dev);
 * $runner->runAll(); // Execute all pending changes
 * ```
 *
 * @author Ibrahim
 */
class SchemaRunner extends Database {
    private $dbChanges;
    private $environment;
    private $onErrCallbacks;
    private $onRegErrCallbacks;
    private SchemaChangeRepository $repository;
    /**
     * Initialize a new schema runner with configuration.
     * 
     * @param ConnectionInfo|null $connectionInfo Database connection information.
     * @param string $environment Target environment (dev, test, prod) - affects which changes run.
     */
    public function __construct(?ConnectionInfo $connectionInfo, string $environment = 'dev') {
        parent::__construct($connectionInfo);
        $this->environment = $environment;
        $this->onErrCallbacks = [];
        $this->onRegErrCallbacks = [];

        $table = AttributeTableBuilder::build(
            SchemaMigrationsTable::class,
            $this->getConnectionInfo()->getDatabaseType()
        );

        // Handle MSSQL datetime2 type
        if ($this->getConnectionInfo()->getDatabaseType() === ConnectionInfo::SUPPORTED_DATABASES[1]) {
            $table->getColByKey('applied_on')->setDatatype(DataType::DATETIME2);
        }

        $this->addTable($table);

        $this->repository = new SchemaChangeRepository($this);
        $this->dbChanges = [];
    }
    /**
     * Register a callback to handle execution errors.
     * 
     * The callback will be invoked when a migration or seeder fails during execution.
     * Multiple callbacks can be registered and will be called in registration order.
     * 
     * @param callable $callback Function to call on execution errors. Receives error details.
     */
    public function addOnErrorCallback(callable $callback): void {
        $this->onErrCallbacks[] = $callback;
    }
    /**
     * Register a callback to handle class registration errors.
     * 
     * The callback will be invoked when a migration or seeder class cannot be
     * properly loaded or instantiated during the discovery process.
     * 
     * @param callable $callback Function to call on registration errors. Receives error details.
     */
    public function addOnRegisterErrorCallback(callable $callback): void {
        $this->onRegErrCallbacks[] = $callback;

        if (empty($this->dbChanges)) {
            // No changes registered
        }
    }

    /**
     * Apply all pending database changes.
     * 
     * All changes applied in a single call to apply() are assigned the same
     * batch number, allowing them to be rolled back together.
     * 
     * @return DatabaseChangeResult Result containing applied, skipped, and failed changes.
     */
    public function apply(): DatabaseChangeResult {
        $result = new DatabaseChangeResult();
        $result->setConnectionInfo($this->getConnectionInfo());
        $batch = $this->getRepository()->getNextBatchNumber();
        $startTime = microtime(true);

        // Track which changes we've already processed
        $processed = [];

        // Keep applying changes until no more can be applied
        $appliedInPass = true;

        while ($appliedInPass) {
            $appliedInPass = false;

            foreach ($this->dbChanges as $change) {
                $name = $change->getName();

                if (isset($processed[$name])) {
                    continue;
                }

                if ($this->isApplied($name)) {
                    $processed[$name] = true;
                    $result->addSkipped($change, 'Already applied');
                    continue;
                }

                if (!$this->shouldRunInEnvironment($change)) {
                    $processed[$name] = true;
                    $result->addSkipped($change, 'Environment mismatch');
                    continue;
                }

                if (!$this->shouldRunForConnection($change)) {
                    $processed[$name] = true;
                    $result->addSkipped($change, 'Connection mismatch');
                    continue;
                }

                if (!$this->areDependenciesSatisfied($change)) {
                    continue; // Don't mark as processed - may be satisfied later
                }

                try {
                    $this->executeChange($change);
                    $change->setBatch($batch);
                    $this->getRepository()->recordChange($change);
                    $result->addApplied($change);
                    $processed[$name] = true;
                    $appliedInPass = true;
                } catch (\Throwable $ex) {
                    $this->resetBinding();
                    $result->addFailed($change, $ex);
                    $processed[$name] = true;

                    foreach ($this->onErrCallbacks as $callback) {
                        call_user_func_array($callback, [$ex, $change, $this]);
                    }
                }
            }
        }

        // Mark unprocessed changes as skipped (unsatisfied dependencies)
        foreach ($this->dbChanges as $change) {
            if (!isset($processed[$change->getName()])) {
                $result->addSkipped($change, 'Unsatisfied dependencies');
            }
        }

        $result->setTotalTime((microtime(true) - $startTime) * 1000);

        return $result;
    }

    /**
     * Apply the next pending database change.
     * 
     * Each call to applyOne() creates a new batch with a single change.
     * 
     * @return DatabaseChange|null The applied change, or null if no pending changes.
     */
    public function applyOne(): ?DatabaseChange {
        $change = null;
        $batch = $this->getRepository()->getNextBatchNumber();

        try {
            foreach ($this->dbChanges as $change) {
                if ($this->isApplied($change->getName())) {
                    continue;
                }

                if (!$this->shouldRunInEnvironment($change)) {
                    continue;
                }

                if (!$this->shouldRunForConnection($change)) {
                    continue;
                }

                if (!$this->areDependenciesSatisfied($change)) {
                    continue;
                }

                try {
                    $this->executeChange($change);
                    $change->setBatch($batch);
                    $this->getRepository()->recordChange($change);
                } catch (\Throwable $ex) {
                    $this->resetBinding();

                    foreach ($this->onErrCallbacks as $callback) {
                        call_user_func_array($callback, [$ex, $change, $this]);
                    }
                }

                return $change;
            }
        } catch (\Throwable $ex) {
            $this->resetBinding();

            foreach ($this->onErrCallbacks as $callback) {
                call_user_func_array($callback, [$ex, $change, $this]);
            }
        }

        return null;
    }

    /**
     * Remove all registered execution error callbacks.
     */
    public function clearErrorCallbacks(): void {
        $this->onErrCallbacks = [];
    }

    /**
     * Remove all registered class registration error callbacks.
     */
    public function clearRegisterErrorCallbacks(): void {
        $this->onRegErrCallbacks = [];
    }

    /**
     * Create the schema tracking table if it does not exist.
     * 
     * This table stores information about which migrations and seeders
     * have been applied, including timestamps and execution status.
     * Required for tracking database change history.
     * 
     * If the table already exists but is missing the 'status' column
     * (upgrade from older version), the column will be added automatically.
     */
    public function createSchemaTable() {
        $this->createTables();
        // Add status column if missing (upgrade path for existing installations)
        try {
            $this->table('schema_changes')->addCol('status')->execute();
        } catch (\Throwable $e) {
            //Probably already exist.
        }
    }

    /**
     * Discover and register database changes from a directory.
     * 
     * Scans the specified directory for PHP files containing classes that extend
     * DatabaseChange (migrations and seeders). Each discovered class is automatically
     * registered with the schema runner.
     * 
     * @param string $path Absolute path to the directory containing migration/seeder files.
     * @param string $namespace The PHP namespace for classes in the directory.
     * @param bool $recursive Whether to scan subdirectories recursively. Default is false.
     * @return int Number of changes discovered and registered.
     */
    public function discoverFromPath(string $path, string $namespace = '', bool $recursive = false): int {
        $count = 0;

        if (!is_dir($path)) {
            return $count;
        }

        $namespace = rtrim($namespace, '\\');
        $iterator = $recursive 
            ? new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS))
            : new \DirectoryIterator($path);

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $className = $this->resolveClassName($file, $path, $namespace, $recursive);

                if ($className !== null && $this->register($className)) {
                    $count++;
                }
            }
        }

        return $count;
    }

    /**
     * Drop the schema tracking table from the database.
     * 
     * Removes the table that stores migration and seeder execution history.
     * Use with caution as this will lose all tracking information.
     */
    public function dropSchemaTable() {
        $this->table('schema_changes')->drop();
        $this->execute();
    }

    /**
     * Get all discovered database changes (migrations and seeders).
     * 
     * @return array Array of DatabaseChange instances found in the configured path.
     */
    public function getChanges(): array {
        return $this->dbChanges;
    }

    /**
     * Get the current execution environment.
     * 
     * The environment determines which migrations and seeders will be executed.
     * Changes can specify which environments they should run in.
     * 
     * @return string The current environment (e.g., 'dev', 'test', 'prod').
     */
    public function getEnvironment(): string {
        return $this->environment;
    }
    /**
     * Get pending database changes that would be applied.
     * 
     * This method returns changes that have not been applied yet and would
     * run in the current environment. Optionally captures the SQL queries
     * that would be executed using dry-run mode.
     * 
     * @param bool $withQueries If true, executes each change in dry-run mode
     *                          to capture the SQL queries. Default is false.
     * @return array Array of associative arrays with keys:
     *               - 'change': The DatabaseChange instance
     *               - 'queries': Array of SQL strings (only if $withQueries is true)
     */
    public function getPendingChanges(bool $withQueries = false): array {
        $pending = [];

        foreach ($this->dbChanges as $change) {
            if ($this->isApplied($change->getName())) {
                continue;
            }

            if (!$this->shouldRunInEnvironment($change)) {
                continue;
            }

            if (!$this->shouldRunForConnection($change)) {
                continue;
            }

            $info = ['change' => $change, 'queries' => []];

            if ($withQueries) {
                $this->setDryRun(true);
                try {
                    $change->execute($this);
                    $info['queries'] = $this->getCapturedQueries();
                } catch (\Throwable $ex) {
                    // Capture failed, queries may be partial
                    $info['queries'] = array_merge($this->getCapturedQueries(), ['Error: '.$ex->getMessage()]);
                }
                $this->setDryRun(false);
            }

            $pending[] = $info;
        }

        return $pending;
    }

    /**
     * Get the schema change repository.
     * 
     * @return SchemaChangeRepository The repository instance
     */
    public function getRepository(): SchemaChangeRepository {
        return $this->repository;
    }

    /**
     * Check if a database change exists in the discovered changes.
     * 
     * @param string $name The class name of the change to check.
     * @return bool True if the change exists, false otherwise.
     */
    public function hasChange(string $name): bool {
        return $this->findChangeByName($name) !== null;
    }

    /**
     * Check if a specific database change has been applied.
     * 
     * @param string $name The class name of the change to check.
     * @return bool True if the change has been applied, false otherwise.
     */
    public function isApplied(string $name): bool {
        return $this->getRepository()->count([
            'change_name' => $name
        ]) == 1;
    }

    /**
     * Register a database change.
     * 
     * If a change with the same name is already registered, this method
     * returns false without registering a duplicate.
     * 
     * @param DatabaseChange|string $change The change instance or class name.
     * @return bool True if registered successfully, false if already registered or on error.
     */
    public function register(DatabaseChange|string $change): bool {
        try {
            $name = is_string($change) ? $change : $change->getName();

            if ($this->hasChange($name)) {
                return false;
            }

            if (is_string($change)) {
                if (!class_exists($change)) {
                    throw new SchemaException("Class does not exist: {$change}");
                }

                if (!is_subclass_of($change, DatabaseChange::class)) {
                    throw new SchemaException("Class is not a subclass of DatabaseChange: {$change}");
                }

                $change = new $change();
            }

            $this->dbChanges[] = $change;

            return true;
        } catch (\Throwable $ex) {
            foreach ($this->onRegErrCallbacks as $callback) {
                call_user_func_array($callback, [$ex]);
            }

            return false;
        }
    }

    /**
     * Register multiple database changes.
     * 
     * @param array $changes Array of DatabaseChange instances or class names.
     */
    public function registerAll(array $changes): void {
        foreach ($changes as $change) {
            $this->register($change);
        }
    }

    /**
     * Rollback all changes from a specific batch.
     * 
     * @param int $batch The batch number to rollback.
     * @return array Array of rolled back DatabaseChange instances.
     */
    public function rollbackBatch(int $batch): array {
        $changeNames = array_column($this->getRepository()->getByBatch($batch), 'change_name');
        $rolled = [];

        // Rollback in reverse order
        $changes = array_reverse($this->getChanges());

        foreach ($changes as $change) {
            if (in_array($change->getName(), $changeNames)) {
                $this->attemptRoolback($change, $rolled);
            }
        }

        return $rolled;
    }

    /**
     * Rollback all changes from the last batch.
     * 
     * @return array Array of rolled back DatabaseChange instances.
     */
    public function rollbackLastBatch(): array {
        $lastBatch = $this->getRepository()->getLastBatchNumber();

        if ($lastBatch === 0) {
            return [];
        }

        return $this->rollbackBatch($lastBatch);
    }

    /**
     * Rollback database changes up to a specific change.
     * 
     * @param string|null $changeName The change to rollback to, or null to rollback all.
     * @return array Array of rolled back DatabaseChange instances.
     */
    public function rollbackUpTo(?string $changeName): array {
        $changes = array_reverse($this->getChanges());
        $rolled = [];

        if (empty($changes)) {
            return $rolled;
        }

        if ($changeName !== null && $this->hasChange($changeName)) {
            foreach ($changes as $change) {
                if ($change->getName() == $changeName && $this->isApplied($change->getName())) {
                    $this->attemptRoolback($change, $rolled);

                    return $rolled;
                }
            }
        } else if ($changeName === null) {
            foreach ($changes as $change) {
                if ($this->isApplied($change->getName()) && !$this->attemptRoolback($change, $rolled)) {
                    return $rolled;
                }
            }
        }

        return $rolled;
    }

    /**
     * Skip a single change without executing it (baseline).
     * 
     * Records the change as 'skipped' in the schema_changes table so it
     * won't be executed by future apply() calls.
     * 
     * @param DatabaseChange|string $change The change instance or class name.
     * @return bool True if skipped successfully, false if already applied/skipped or not found.
     */
    public function skip(DatabaseChange|string $change): bool {
        $instance = is_string($change) ? $this->findChangeByName($change) : $change;

        if ($instance === null) {
            return false;
        }

        if ($this->isApplied($instance->getName())) {
            return false;
        }

        $batch = $this->getRepository()->getNextBatchNumber();
        $this->getRepository()->recordSkipped($instance, $batch);

        return true;
    }

    /**
     * Skip all pending changes without executing them (baseline all).
     * 
     * @return array Array of skipped DatabaseChange instances.
     */
    public function skipAll(): array {
        $skipped = [];
        $batch = $this->getRepository()->getNextBatchNumber();

        foreach ($this->dbChanges as $change) {
            if ($this->isApplied($change->getName())) {
                continue;
            }

            if (!$this->shouldRunInEnvironment($change)) {
                continue;
            }

            if (!$this->shouldRunForConnection($change)) {
                continue;
            }

            $this->getRepository()->recordSkipped($change, $batch);
            $skipped[] = $change;
        }

        return $skipped;
    }

    /**
     * Skip the next N pending changes without executing them.
     * 
     * Changes are skipped in dependency order (same order apply() would use).
     * 
     * @param int $count Number of changes to skip.
     * @return array Array of skipped DatabaseChange instances.
     */
    public function skipNext(int $count = 1): array {
        $skipped = [];
        $batch = $this->getRepository()->getNextBatchNumber();

        foreach ($this->dbChanges as $change) {
            if (count($skipped) >= $count) {
                break;
            }

            if ($this->isApplied($change->getName())) {
                continue;
            }

            if (!$this->shouldRunInEnvironment($change)) {
                continue;
            }

            if (!$this->shouldRunForConnection($change)) {
                continue;
            }

            $this->getRepository()->recordSkipped($change, $batch);
            $skipped[] = $change;
        }

        return $skipped;
    }

    /**
     * Skip all pending changes up to and including the named one.
     * 
     * @param string $changeName The class name of the change to skip up to.
     * @return array Array of skipped DatabaseChange instances.
     */
    public function skipUpTo(string $changeName): array {
        $skipped = [];
        $batch = $this->getRepository()->getNextBatchNumber();

        foreach ($this->dbChanges as $change) {
            if ($this->isApplied($change->getName())) {
                if ($change->getName() === $changeName) {
                    break;
                }

                continue;
            }

            if (!$this->shouldRunInEnvironment($change)) {
                if ($change->getName() === $changeName) {
                    break;
                }

                continue;
            }

            if (!$this->shouldRunForConnection($change)) {
                if ($change->getName() === $changeName) {
                    break;
                }

                continue;
            }

            $this->getRepository()->recordSkipped($change, $batch);
            $skipped[] = $change;

            if ($change->getName() === $changeName) {
                break;
            }
        }

        return $skipped;
    }

    private function areDependenciesSatisfied(DatabaseChange $change): bool {
        foreach ($change->getDependencies() as $depName) {
            if (!$this->isApplied($depName)) {
                return false;
            }
        }

        return true;
    }
    private function attemptRoolback(DatabaseChange $change, &$rolled) : bool {
        try {
            $change->rollback($this);
            $this->repository->removeChange($change->getName());
            $rolled[] = $change;

            return true;
        } catch (\Throwable $ex) {
            foreach ($this->onErrCallbacks as $callback) {
                call_user_func_array($callback, [$ex, $change, $this]);
            }

            return false;
        }
    }

    private function findChangeByName(string $name): ?DatabaseChange {
        foreach ($this->dbChanges as $change) {
            $changeName = $change->getName();

            // Exact match
            if ($changeName === $name) {
                return $change;
            }

            // Check if name is a short class name and change is full class name
            if (str_ends_with($changeName, '\\'.$name)) {
                return $change;
            }

            // Check if name is full class name and change is short class name
            if (str_ends_with($name, '\\'.$changeName)) {
                return $change;
            }
        }

        return null;
    }

    /**
     * Resolve the fully qualified class name from a file.
     * 
     * @param \SplFileInfo $file The file to resolve.
     * @param string $basePath The base directory path.
     * @param string $namespace The base namespace.
     * @param bool $recursive Whether recursive scanning is enabled.
     * @return string|null The fully qualified class name, or null if not a valid change class.
     */
    private function resolveClassName(\SplFileInfo $file, string $basePath, string $namespace, bool $recursive): ?string {
        $filename = $file->getBasename('.php');

        if ($recursive) {
            $relativePath = substr($file->getPath(), strlen($basePath));
            $relativePath = trim(str_replace(DIRECTORY_SEPARATOR, '\\', $relativePath), '\\');
            $className = $relativePath ? $namespace.'\\'.$relativePath.'\\'.$filename : $namespace.'\\'.$filename;
        } else {
            $className = $namespace.'\\'.$filename;
        }

        if (!class_exists($className)) {
            require_once $file->getPathname();
        }

        if (class_exists($className) && is_subclass_of($className, DatabaseChange::class)) {
            $reflection = new ReflectionClass($className);

            if (!$reflection->isAbstract()) {
                return $className;
            }
        }

        return null;
    }

    private function shouldRunForConnection(DatabaseChange $change): bool {
        $targets = $change->getTargetConnections();

        if (empty($targets)) {
            return true;
        }

        $connInfo = $this->getConnectionInfo();

        return $connInfo !== null && in_array($connInfo->getName(), $targets);
    }

    private function shouldRunInEnvironment(DatabaseChange $change): bool {
        $environments = $change->getEnvironments();

        return empty($environments) || in_array($this->environment, $environments);
    }

    private function sortChangesByDependencies() {
        $sorted = [];
        $visited = [];

        foreach ($this->dbChanges as $change) {
            $visiting = [];
            $this->topologicalSort($change, $visited, $sorted, $visiting);
        }

        $this->dbChanges = $sorted;
    }

    private function topologicalSort(DatabaseChange $change, array &$visited, array &$sorted, array &$visiting) {
        $className = $change->getName();

        if (isset($visiting[$className])) {
            $cycle = array_merge(array_keys($visiting), [$className]);
            throw new DatabaseException('Circular dependency detected: '.implode(' -> ', $cycle));
        }

        if (isset($visited[$className])) {
            return;
        }

        $visiting[$className] = true;

        foreach ($change->getDependencies() as $depName) {
            $dep = $this->findChangeByName($depName);

            if ($dep) {
                $this->topologicalSort($dep, $visited, $sorted, $visiting);
            }
        }

        unset($visiting[$className]);
        $visited[$className] = true;
        $sorted[] = $change;
    }

    /**
     * Execute a database change, optionally wrapped in a transaction.
     * 
     * This method checks the change's useTransaction() method to determine
     * whether to wrap the execution in a database transaction.
     * 
     * @param DatabaseChange $change The change to execute.
     */
    protected function executeChange(DatabaseChange $change): void {
        if ($change->useTransaction($this)) {
            $this->transaction(function (Database $db) use ($change)
            {
                $change->execute($db);
            });
        } else {
            $change->execute($this);
        }
    }
}
