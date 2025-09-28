<?php
namespace WebFiori\Database\Schema;

use Error;
use Exception;
use ReflectionClass;
use WebFiori\Database\ColOption;
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
    private $ns;
    private $onErrCallbacks;
    private $onRegErrCallbacks;
    private $path;
    /**
     * Initialize a new schema runner with configuration.
     * 
     * @param string $path Filesystem path where migration/seeder classes are located.
     * @param string $ns PHP namespace for migration/seeder classes (e.g., 'App\\Migrations').
     * @param ConnectionInfo|null $connectionInfo Database connection information.
     * @param string $environment Target environment (dev, test, prod) - affects which changes run.
     */
    public function __construct(string $path, string $ns, ?ConnectionInfo $connectionInfo, string $environment = 'dev') {
        parent::__construct($connectionInfo);
        $this->path = $path;
        $this->ns = $ns;
        $this->environment = $environment;
        $dbType = $connectionInfo !== null ? $connectionInfo->getDatabaseType() : 'mysql';
        $this->onErrCallbacks = [];
        $this->onRegErrCallbacks = [];
        $this->createBlueprint('schema_changes')->addColumns([
            'id' => [
                ColOption::TYPE => DataType::INT,
                ColOption::PRIMARY => true,
                ColOption::AUTO_INCREMENT => true,
                ColOption::IDENTITY => true,
                ColOption::COMMENT => 'The unique identifier of the change.'
            ],
            'change_name' => [
                ColOption::TYPE => DataType::VARCHAR,
                ColOption::SIZE => 125,
                ColOption::COMMENT => 'The name of the change.'
            ],
            'type' => [
                ColOption::TYPE => DataType::VARCHAR,
                ColOption::SIZE => 20,
                ColOption::COMMENT => 'The type of the change (migration, seeder, etc.).'
            ],
            'applied-on' => [
                ColOption::TYPE => $dbType == ConnectionInfo::SUPPORTED_DATABASES[1] ? DataType::DATETIME2 : DataType::DATETIME,
                ColOption::COMMENT => 'The date and time at which the change was applied.'
            ]
        ]);

        $this->dbChanges = [];
        $this->scanPathForChanges();
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
            $this->scanPathForChanges();
        }
    }

    /**
     * Apply all pending database changes.
     * 
     * @return array Array of applied DatabaseChange instances.
     */
    public function apply(): array {
        $applied = [];

        // Keep applying changes until no more can be applied
        $appliedInPass = true;

        while ($appliedInPass) {
            $appliedInPass = false;

            foreach ($this->dbChanges as $change) {
                if ($this->isApplied($change->getName())) {
                    continue;
                }

                if (!$this->shouldRunInEnvironment($change)) {
                    continue;
                }

                if (!$this->areDependenciesSatisfied($change)) {
                    continue;
                }

                try {
                    $this->transaction(function($db) use ($change) {
                        $change->execute($db);
                        $db->table('schema_changes')
                                ->insert([
                                    'change_name' => $change->getName(),
                                    'type' => $change->getType(),
                                    'applied-on' => date('Y-m-d H:i:s')
                                ])->execute();
                    });

                    $applied[] = $change;
                    $appliedInPass = true;
                } catch (\Throwable $ex) {
                    foreach ($this->onErrCallbacks as $callback) {
                        call_user_func_array($callback, [$ex, $change, $this]);
                    }
                    // Continue with next change instead of breaking
                }
            }
        }

        return $applied;
    }

    /**
     * Apply the next pending database change.
     * 
     * @return DatabaseChange|null The applied change, or null if no pending changes.
     */
    public function applyOne(): ?DatabaseChange {
        $change = null;
        try {
            foreach ($this->dbChanges as $change) {
                if ($this->isApplied($change->getName())) {
                    continue;
                }

                if (!$this->shouldRunInEnvironment($change)) {
                    continue;
                }

                if (!$this->areDependenciesSatisfied($change)) {
                    continue;
                }

                $this->transaction(function($db) use ($change) {
                    $change->execute($db);
                    $db->table('schema_changes')
                            ->insert([
                                'change_name' => $change->getName(),
                                'type' => $change->getType(),
                                'applied-on' => date('Y-m-d H:i:s')
                            ])->execute();
                });

                return $change;
            }
        } catch (\Throwable $ex) {
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
     */
    public function createSchemaTable() {
        $this->createTables();
        $this->execute();
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
     * Get the PHP namespace used for migration and seeder classes.
     * 
     * @return string The namespace prefix for all change classes.
     */
    public function getNamespace(): string {
        return $this->ns;
    }

    /**
     * Get the filesystem path where migration and seeder classes are located.
     * 
     * @return string The directory path containing change class files.
     */
    public function getPath(): string {
        return $this->path;
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
        return $this->table('schema_changes')
                ->select(['change_name'])
                ->where('change_name', $name)
                ->execute()
                ->getRowsCount() == 1;
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
            $this->table('schema_changes')->delete()->where('change_name', $change->getName())->execute();
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

    private function scanPathForChanges() {
        $changesPath = $this->getPath();

        if (!is_dir($changesPath)) {
            throw new DatabaseException('Invalid schema path: "'.$changesPath.'"');
        }

        $dirContents = scandir($changesPath);
        if ($dirContents === false) {
            throw new DatabaseException('Cannot read directory: "'.$changesPath.'"');
        }
        $dirContents = array_diff($dirContents, ['.', '..']);

        foreach ($dirContents as $file) {
            if (is_file($changesPath.DIRECTORY_SEPARATOR.$file) && pathinfo($file, PATHINFO_EXTENSION) === 'php') {
                $filePath = $changesPath.DIRECTORY_SEPARATOR.$file;
                $clazz = $this->getNamespace().'\\'.explode('.', $file)[0];

                try {
                    // Try to load the class if it doesn't exist
                    if (!class_exists($clazz, false)) {
                        // Set up error handler to catch fatal errors
                        $prevHandler = set_error_handler(function($severity, $message, $file, $line) {
                            throw new ErrorException($message, 0, $severity, $file, $line);
                        });
                        
                        try {
                            require_once $filePath;
                        } finally {
                            restore_error_handler();
                        }
                    }
                    
                    if (class_exists($clazz)) {
                        $reflection = new ReflectionClass($clazz);
                        if ($reflection->isAbstract()) {
                            $ex = new Exception("Cannot instantiate abstract class: {$clazz}");
                            foreach ($this->onRegErrCallbacks as $callback) {
                                call_user_func_array($callback, [$ex]);
                            }
                            continue;
                        }
                        if ($reflection->getConstructor()?->getNumberOfRequiredParameters() > 0) {
                            $ex = new Exception("Cannot instantiate class with required parameters: {$clazz}");
                            foreach ($this->onRegErrCallbacks as $callback) {
                                call_user_func_array($callback, [$ex]);
                            }
                            continue;
                        }
                        
                        $instance = new $clazz();

                        if ($instance instanceof DatabaseChange) {
                            $this->dbChanges[] = $instance;
                        }
                    }
                } catch (ParseError $ex) {
                    foreach ($this->onRegErrCallbacks as $callback) {
                        call_user_func_array($callback, [$ex]);
                    }
                } catch (Throwable $ex) {
                    foreach ($this->onRegErrCallbacks as $callback) {
                        call_user_func_array($callback, [$ex]);
                    }
                }
            }
        }

        $this->sortChangesByDependencies();
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
}
