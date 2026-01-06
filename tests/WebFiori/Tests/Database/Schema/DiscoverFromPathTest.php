<?php

namespace WebFiori\Tests\Database\Schema;

use PHPUnit\Framework\TestCase;
use WebFiori\Database\ConnectionInfo;
use WebFiori\Database\Schema\SchemaRunner;

class DiscoverFromPathTest extends TestCase {
    private string $tempDir;
    
    protected function setUp(): void {
        $this->tempDir = sys_get_temp_dir() . '/schema_discover_test_' . uniqid();
        mkdir($this->tempDir, 0777, true);
    }
    
    protected function tearDown(): void {
        $this->removeDirectory($this->tempDir);
        gc_collect_cycles();
    }
    
    private function removeDirectory(string $dir): void {
        if (!is_dir($dir)) {
            return;
        }
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($files as $file) {
            $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }
        rmdir($dir);
    }
    
    private function getConnectionInfo(): ConnectionInfo {
        return new ConnectionInfo('mysql', 'root', getenv('MYSQL_ROOT_PASSWORD') ?: '123456', 'testing_db', '127.0.0.1');
    }
    
    public function testDiscoverFromEmptyDirectory() {
        $runner = new SchemaRunner($this->getConnectionInfo());
        
        $count = $runner->discoverFromPath($this->tempDir, 'TestMigrations');
        
        $this->assertEquals(0, $count);
        $this->assertEmpty($runner->getChanges());
    }
    
    public function testDiscoverFromNonExistentDirectory() {
        $runner = new SchemaRunner($this->getConnectionInfo());
        
        $count = $runner->discoverFromPath('/non/existent/path', 'TestMigrations');
        
        $this->assertEquals(0, $count);
    }
    
    public function testDiscoverMigrationClass() {
        $this->createMigrationFile($this->tempDir, 'TestMigrationA', 'TestMigrations');
        
        $runner = new SchemaRunner($this->getConnectionInfo());
        $count = $runner->discoverFromPath($this->tempDir, 'TestMigrations');
        
        $this->assertEquals(1, $count);
        $this->assertCount(1, $runner->getChanges());
        $this->assertTrue($runner->hasChange('TestMigrations\\TestMigrationA'));
    }
    
    public function testDiscoverMultipleClasses() {
        $this->createMigrationFile($this->tempDir, 'MigrationOne', 'TestMigrations');
        $this->createMigrationFile($this->tempDir, 'MigrationTwo', 'TestMigrations');
        $this->createSeederFile($this->tempDir, 'SeederOne', 'TestMigrations');
        
        $runner = new SchemaRunner($this->getConnectionInfo());
        $count = $runner->discoverFromPath($this->tempDir, 'TestMigrations');
        
        $this->assertEquals(3, $count);
        $this->assertCount(3, $runner->getChanges());
    }
    
    public function testDiscoverIgnoresNonPhpFiles() {
        $this->createMigrationFile($this->tempDir, 'ValidMigration', 'TestMigrations');
        file_put_contents($this->tempDir . '/readme.txt', 'This is not a PHP file');
        file_put_contents($this->tempDir . '/config.json', '{}');
        
        $runner = new SchemaRunner($this->getConnectionInfo());
        $count = $runner->discoverFromPath($this->tempDir, 'TestMigrations');
        
        $this->assertEquals(1, $count);
    }
    
    public function testDiscoverIgnoresNonDatabaseChangeClasses() {
        $this->createMigrationFile($this->tempDir, 'ValidMigration', 'TestMigrations');
        $this->createNonChangeClass($this->tempDir, 'SomeHelper', 'TestMigrations');
        
        $runner = new SchemaRunner($this->getConnectionInfo());
        $count = $runner->discoverFromPath($this->tempDir, 'TestMigrations');
        
        $this->assertEquals(1, $count);
    }
    
    public function testDiscoverIgnoresAbstractClasses() {
        $this->createMigrationFile($this->tempDir, 'ConcreteMigration', 'TestMigrations');
        $this->createAbstractMigrationFile($this->tempDir, 'BaseMigration', 'TestMigrations');
        
        $runner = new SchemaRunner($this->getConnectionInfo());
        $count = $runner->discoverFromPath($this->tempDir, 'TestMigrations');
        
        $this->assertEquals(1, $count);
    }
    
    public function testDiscoverNonRecursiveIgnoresSubdirectories() {
        $this->createMigrationFile($this->tempDir, 'RootMigration', 'TestMigrations');
        
        $subDir = $this->tempDir . '/SubDir';
        mkdir($subDir);
        $this->createMigrationFile($subDir, 'SubMigration', 'TestMigrations\\SubDir');
        
        $runner = new SchemaRunner($this->getConnectionInfo());
        $count = $runner->discoverFromPath($this->tempDir, 'TestMigrations', recursive: false);
        
        $this->assertEquals(1, $count);
        $this->assertTrue($runner->hasChange('TestMigrations\\RootMigration'));
        $this->assertFalse($runner->hasChange('TestMigrations\\SubDir\\SubMigration'));
    }
    
    public function testDiscoverRecursiveIncludesSubdirectories() {
        $this->createMigrationFile($this->tempDir, 'RootMigration', 'TestMigrations');
        
        $subDir = $this->tempDir . '/SubDir';
        mkdir($subDir);
        $this->createMigrationFile($subDir, 'SubMigration', 'TestMigrations\\SubDir');
        
        $deepDir = $subDir . '/Deep';
        mkdir($deepDir);
        $this->createMigrationFile($deepDir, 'DeepMigration', 'TestMigrations\\SubDir\\Deep');
        
        $runner = new SchemaRunner($this->getConnectionInfo());
        $count = $runner->discoverFromPath($this->tempDir, 'TestMigrations', recursive: true);
        
        $this->assertEquals(3, $count);
        $this->assertTrue($runner->hasChange('TestMigrations\\RootMigration'));
        $this->assertTrue($runner->hasChange('TestMigrations\\SubDir\\SubMigration'));
        $this->assertTrue($runner->hasChange('TestMigrations\\SubDir\\Deep\\DeepMigration'));
    }
    
    public function testDiscoverWithTrailingSlashInNamespace() {
        $this->createMigrationFile($this->tempDir, 'TestMigration', 'TestMigrations');
        
        $runner = new SchemaRunner($this->getConnectionInfo());
        $count = $runner->discoverFromPath($this->tempDir, 'TestMigrations\\');
        
        $this->assertEquals(1, $count);
        $this->assertTrue($runner->hasChange('TestMigrations\\TestMigration'));
    }
    
    private function createMigrationFile(string $dir, string $className, string $namespace): void {
        $content = <<<PHP
<?php
namespace {$namespace};

use WebFiori\Database\Schema\AbstractMigration;
use WebFiori\Database\Database;

class {$className} extends AbstractMigration {
    public function up(Database \$db): void {}
    public function down(Database \$db): void {}
}
PHP;
        file_put_contents($dir . '/' . $className . '.php', $content);
    }
    
    private function createSeederFile(string $dir, string $className, string $namespace): void {
        $content = <<<PHP
<?php
namespace {$namespace};

use WebFiori\Database\Schema\AbstractSeeder;
use WebFiori\Database\Database;

class {$className} extends AbstractSeeder {
    public function run(Database \$db): void {}
}
PHP;
        file_put_contents($dir . '/' . $className . '.php', $content);
    }
    
    private function createNonChangeClass(string $dir, string $className, string $namespace): void {
        $content = <<<PHP
<?php
namespace {$namespace};

class {$className} {
    public function doSomething(): void {}
}
PHP;
        file_put_contents($dir . '/' . $className . '.php', $content);
    }
    
    private function createAbstractMigrationFile(string $dir, string $className, string $namespace): void {
        $content = <<<PHP
<?php
namespace {$namespace};

use WebFiori\Database\Schema\AbstractMigration;
use WebFiori\Database\Database;

abstract class {$className} extends AbstractMigration {
    public function up(Database \$db): void {}
    public function down(Database \$db): void {}
}
PHP;
        file_put_contents($dir . '/' . $className . '.php', $content);
    }
}
