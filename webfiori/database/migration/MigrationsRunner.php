<?php

namespace webfiori\database\migration;

use Exception;
use webfiori\database\ColOption;
use webfiori\database\ConnectionInfo;
use webfiori\database\Database;
use webfiori\database\DatabaseException;
use webfiori\database\DataType;

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
                        $instance = new $clazz();
                        
                        if ($instance instanceof AbstractMigration) {
                            $this->migrations[] = $instance;
                        }
                    }
                } catch (Exception $ex) {
                    
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
