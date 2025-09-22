<?php

namespace WebFiori\Database\Schema;

use Error;
use Exception;
use WebFiori\Database\ColOption;
use WebFiori\Database\ConnectionInfo;
use WebFiori\Database\Database;
use WebFiori\Database\DatabaseException;
use WebFiori\Database\DataType;

/**
 * A runner for executing database changes including migrations and seeders.
 *
 * @author Ibrahim
 */
class SchemaRunner extends Database {
    private $dbChanges;
    private $path;
    private $ns;
    private $environment;
    private $onErrCallbacks;
    private $onRegErrCallbacks;
    /**
     * Creates new instance of the class.
     * 
     * @param string $path The absolute path to the folder that will have all changes.
     * @param string $ns The namespace at which the changes will belong to.
     * @param ConnectionInfo $connectionInfo The connection that will be used to execute changes against.
     * @param string $environment The current environment (dev, test, prod).
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
    
    public function getNamespace(): string {
        return $this->ns;
    }
    
    public function getPath(): string {
        return $this->path;
    }
    
    public function getEnvironment(): string {
        return $this->environment;
    }
    
    /**
     * Add a callback to be executed when a seeder/migration fails to execute or rollback.
     * 
     * @param callable $callback Callback with signature: function(\Throwable $err, ?DatabaseChange $change, ?Database $schema)
     */
    public function addOnErrorCallback(callable $callback): void {
        $this->onErrCallbacks[] = $callback;
    }
    
    /**
     * Add a callback to be executed when a seeder/migration fails to register.
     * 
     * @param callable $callback Callback with signature: function(\Throwable $err)
     */
    public function addOnRegisterErrorCallback(callable $callback): void {
        $this->onRegErrCallbacks[] = $callback;
        
        if (empty($this->dbChanges)) {
            $this->scanPathForChanges();
        }
    }
    
    /**
     * Clear all error callbacks.
     */
    public function clearErrorCallbacks(): void {
        $this->onErrCallbacks = [];
    }
    
    /**
     * Clear all register error callbacks.
     */
    public function clearRegisterErrorCallbacks(): void {
        $this->onRegErrCallbacks = [];
    }
    
    public function createSchemaTable() {
        $this->createTables();
        $this->execute();
    }
    
    public function dropSchemaTable() {
        $this->table('schema_changes')->drop();
        $this->execute();
    }
    
    private function scanPathForChanges() {
        $changesPath = $this->getPath();
        
        if (!is_dir($changesPath)) {
            throw new DatabaseException('Invalid schema path: "'.$changesPath.'"');
        }

        $dirContents = array_diff(scandir($changesPath), ['.', '..']);

        foreach ($dirContents as $file) {
            if (is_file($changesPath . DIRECTORY_SEPARATOR . $file)) {
                $clazz = $this->getNamespace().'\\'.explode('.', $file)[0];
                
                try {
                    
                    if (class_exists($clazz)) {
                        $instance = new $clazz();
                        
                        if ($instance instanceof DatabaseChange) {
                            $this->dbChanges[] = $instance;
                        }
                    }
                } catch (Exception|Error $ex) {
                    foreach ($this->onRegErrCallbacks as $callback) {
                        call_user_func_array($callback, [$ex]);
                    }
                }
            }
        }
        
        $this->sortChangesByDependencies();
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
    
    private function topologicalSort(DatabaseChange $change, array &$visited, array &$sorted, array &$visiting = []) {
        $className = $change->getName();
        
        if (isset($visiting[$className])) {
            $cycle = array_merge(array_keys($visiting), [$className]);
            throw new DatabaseException('Circular dependency detected: ' . implode(' -> ', $cycle));
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
    
    private function findChangeByName(string $name): ?DatabaseChange {
        foreach ($this->dbChanges as $change) {
            $changeName = $change->getName();
            
            // Exact match
            if ($changeName === $name) {
                return $change;
            }
            
            // Check if name is a short class name and change is full class name
            if (str_ends_with($changeName, '\\' . $name)) {
                return $change;
            }
            
            // Check if name is full class name and change is short class name
            if (str_ends_with($name, '\\' . $changeName)) {
                return $change;
            }
        }
        return null;
    }
    
    public function getChanges(): array {
        return $this->dbChanges;
    }
    
    public function isApplied(string $name): bool {
        try {
            return $this->table('schema_changes')
                    ->select(['change_name'])
                    ->where('change_name', $name)
                    ->execute()
                    ->getRowsCount() == 1;
        } catch (DatabaseException $ex) {
            // If schema_changes table doesn't exist, no changes have been applied
            if (strpos($ex->getMessage(), "doesn't exist") !== false) {
                return false;
            }
            throw $ex;
        }
    }
    
    public function hasChange(string $name): bool {
        return $this->findChangeByName($name) !== null;
    }
    
    /**
     * Apply one single change at a time.
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
                
                $change->execute($this);
                $this->table('schema_changes')
                        ->insert([
                            'change_name' => $change->getName(),
                            'type' => $change->getType(),
                            'applied-on' => date('Y-m-d H:i:s')
                        ])->execute();
                
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
     * Apply all detected changes.
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
                    $change->execute($this);
                    $this->table('schema_changes')
                            ->insert([
                                'change_name' => $change->getName(),
                                'type' => $change->getType(),
                                'applied-on' => date('Y-m-d H:i:s')
                            ])->execute();
                    
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
    /**
     * Rollback changes up to a specific change.
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
                if ($this->isApplied($change->getName())) {
                    if (!$this->attemptRoolback($change, $rolled)) {
                        return $rolled;
                    }
                }
            }
        }
        
        return $rolled;
    }
    
    private function shouldRunInEnvironment(DatabaseChange $change): bool {
        $environments = $change->getEnvironments();
        return empty($environments) || in_array($this->environment, $environments);
    }
    
    private function areDependenciesSatisfied(DatabaseChange $change): bool {
        foreach ($change->getDependencies() as $depName) {
            if (!$this->isApplied($depName)) {
                return false;
            }
        }
        return true;
    }
}
