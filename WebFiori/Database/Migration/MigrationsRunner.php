<?php

namespace WebFiori\Database\Migration;

use Error;
use Exception;
use WebFiori\Database\ColOption;
use WebFiori\Database\ConnectionInfo;
use WebFiori\Database\Database;
use WebFiori\Database\DatabaseException;
use WebFiori\Database\DataType;

/**
 * @author Ibrahim
 */
class MigrationsRunner extends Database {
    private $migrations;
    private $path;
    private $ns;
    /**
     * Creates new instance of the class.
     * 
     * @param string $path The absolute path to the folder that will have all migrations.
     * 
     * @param string $ns The namespace at which the migrations will belongs to.
     * 
     * @param ConnectionInfo $connectionInfo The connection that will be used
     * to execute migrations against.
     */
    public function __construct(string $path, string $ns, ?ConnectionInfo $connectionInfo) {
        parent::__construct($connectionInfo);
        $this->path = $path;
        $this->ns = $ns;
        $dbType = $connectionInfo !== null ? $connectionInfo->getDatabaseType() : 'mysql';
        $this->createBlueprint('migrations')->addColumns([
            'id' => [
                ColOption::TYPE => DataType::INT,
                ColOption::PRIMARY => true,
                ColOption::AUTO_INCREMENT => true,
                ColOption::IDENTITY => true
            ],
            'name' => [
                ColOption::TYPE => DataType::VARCHAR,
                ColOption::SIZE => 125
            ],
            'applied-on' => [
                //SQL, use datetime2. Others, use datetime
                ColOption::TYPE => $dbType == ConnectionInfo::SUPPORTED_DATABASES[1] ? DataType::DATETIME2 : DataType::DATETIME,
            ]
        ]);
        $this->migrations = [];
        $this->scanPathForMigrations();
    }
    public function getMigrationsNamespace() : string {
        return $this->ns;
    }
    public function getPath() : string {
        return $this->path;
    }
    public function createMigrationsTable() {
        $this->table('migrations')->createTable();
        $this->execute();
    }
    public function dropMigrationsTable() {
        $this->table('migrations')->drop();
        $this->execute();
    }
    private function scanPathForMigrations() {
        $path = $this->getPath();
        
        if (!is_dir($path)) {
            throw new DatabaseException('Invalid migrations path: "'.$path.'"');
        }

        $dirContents = array_diff(scandir($path), ['.', '..']);

        foreach ($dirContents as $file) {
            if (is_file($path . DIRECTORY_SEPARATOR . $file)) {
                $clazz = $this->getMigrationsNamespace().'\\'.explode('.', $file)[0];
                
                try {
                    if (class_exists($clazz)) {
                        $xClazz = static::class;
                        //Prevent recursion
                        if (trim($clazz, '\\') == $xClazz) {
                            continue;
                        }
                        $instance = new $clazz();
                        
                        if ($instance instanceof AbstractMigration) {
                            $this->migrations[] = $instance;
                        }
                    }
                } catch (Exception|Error $ex) {
                    
                }
            }
        }
        $sortFunc = function (mixed $first, mixed $second) {
            return $first->getOrder() - $second->getOrder();
        };
        usort($this->migrations, $sortFunc);
    }
    public function getMigrations() : array {
        return $this->migrations;
    }
    public function isApplied(string $name) : bool {
        return $this->table('migrations')
                ->select(['name'])
                ->where('name', $name)
                ->execute()
                ->getRowsCount() == 1;
    }
    public function hasMigration(string $name) : bool {
        foreach ($this->getMigrations() as $migration) {
            if ($migration->getName() == $name) {
                return true;
            }
        }
        return false;
    }
    /**
     * Apply one single migration at a time.
     * 
     * @return AbstractMigration|null If a migration was applied, the method will
     * return its information in an object of type 'AbstractMigration'. Other than that, null
     * is returned.
     */
    public function applyOne() : ?AbstractMigration {
        $applied = null;
        foreach ($this->migrations as $m) {
            if ($this->isApplied($m->getName())) {
                continue;
            }
            $m->up($this);
            $this->table('migrations')
                    ->insert([
                        'name' => $m->getName(),
                        'applied-on' => date('Y-m-d H:i:s')
                    ])->execute();
            $applied = $m;
            break;
        }
        return $applied;
    }
    /**
     * Apply all detected migrations.
     * 
     * @return array The method will return an array that holds all applied migrations
     * as objects of type 'AbstractMigration'.
     */
    public function apply() : array {
        $applied = [];
        foreach ($this->migrations as $m) {
            if ($this->isApplied($m->getName())) {
                continue;
            }
            $m->up($this);
            $this->table('migrations')
                    ->insert([
                        'name' => $m->getName(),
                        'applied-on' => date('Y-m-d H:i:s')
                    ])->execute();
            $applied[] = $m;
        }
        return $applied;
    }
    /**
     * Rollback a set of applied migrations.
     * 
     * @param string|null $migrationName If a name is provided, the rollback will
     * be till reaching the specified migration.
     * 
     * @return array The method will return an array that holds all rolled back migrations
     * as objects of type 'AbstractMigration'.
     */
    public function rollbackUpTo(?string $migrationName) : array {
        $migrations = $this->getMigrations();
        $count = count($migrations);
        $rolled = [];
        if ($count == 0) {
            return $rolled;
        }
        
        if ($migrationName !== null && $this->hasMigration($migrationName)) {
            for ($x = $count - 1 ; $x > -1 ; $x--) {
                $m = $migrations[$x];
                if ($m->getName() == $migrationName && $this->isApplied($m->getName())) {
                    $m->down($this);
                    $this->table('migrations')->delete()->where('name', $m->getName())->execute();
                    $rolled[] = $m;
                    return $rolled;
                }
            }
        } else if ($migrationName === null) {
            for ($x = $count - 1 ; $x > -1 ; $x--) {
                $m = $migrations[$x];
                if ($this->isApplied($m->getName())) {
                    $m->down($this);
                    $this->table('migrations')->delete()->where('name', $m->getName())->execute();
                    $rolled[] = $m;
                }
            }
        }
        return $rolled;
    }
    /**
     * Rollback one single migration.
     * 
     * @return AbstractMigration|null If a migration was rolled back, the method
     * will return the migration as an object of type 'AbstractMigration'. Other than that,
     * null is returned.
     */
    public function rollback() : ?AbstractMigration {
        $migrations = $this->getMigrations();
        $count = count($migrations);
        if ($count == 0) {
            return null;
        }
        
        for ($x = $count - 1 ; $x > -1 ; $x--) {
            $m = $migrations[$x];
            if ($this->isApplied($m->getName())) {
                $m->down($this);
                $this->table('migrations')->delete()->where('name', $m->getName())->execute();
                return $m;
            }
        }
        return null;
    }
}
