<?php

namespace WebFiori\Tests\Database\Schema;

use PHPUnit\Framework\TestCase;
use WebFiori\Database\Schema\DatabaseChangeGenerator;
use WebFiori\Database\Schema\GeneratorOption;

class DatabaseChangeGeneratorTest extends TestCase {
    private string $tempDir;

    protected function setUp(): void {
        $this->tempDir = sys_get_temp_dir() . '/db_generator_test_' . uniqid();
        mkdir($this->tempDir, 0755, true);
    }

    protected function tearDown(): void {
        $this->removeDirectory($this->tempDir);
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

    public function testSetPath() {
        $generator = new DatabaseChangeGenerator();
        $generator->setPath('/some/path');
        
        $this->assertEquals('/some/path', $generator->getPath());
    }

    public function testSetNamespace() {
        $generator = new DatabaseChangeGenerator();
        $generator->setNamespace('App\\Migrations');
        
        $this->assertEquals('App\\Migrations', $generator->getNamespace());
    }

    public function testSetNamespaceTrimsSlashes() {
        $generator = new DatabaseChangeGenerator();
        $generator->setNamespace('\\App\\Migrations\\');
        
        $this->assertEquals('App\\Migrations', $generator->getNamespace());
    }

    public function testUseTimestampPrefix() {
        $generator = new DatabaseChangeGenerator();
        
        $this->assertFalse($generator->isTimestampPrefixEnabled());
        
        $generator->useTimestampPrefix(true);
        $this->assertTrue($generator->isTimestampPrefixEnabled());
    }

    public function testCreateMigrationBasic() {
        $generator = new DatabaseChangeGenerator();
        $generator->setPath($this->tempDir);
        $generator->setNamespace('App\\Migrations');

        $path = $generator->createMigration('CreateUsersTable');

        $this->assertFileExists($path);
        $this->assertStringEndsWith('CreateUsersTable.php', $path);

        $content = file_get_contents($path);
        $this->assertStringContainsString('namespace App\\Migrations;', $content);
        $this->assertStringContainsString('use WebFiori\Database\Schema\AbstractMigration;', $content);
        $this->assertStringContainsString('class CreateUsersTable extends AbstractMigration', $content);
        $this->assertStringContainsString('public function up(Database $db): void', $content);
        $this->assertStringContainsString('public function down(Database $db): void', $content);
    }

    public function testCreateMigrationWithTimestamp() {
        $generator = new DatabaseChangeGenerator();
        $generator->setPath($this->tempDir);
        $generator->useTimestampPrefix(true);

        $path = $generator->createMigration('CreateUsersTable');

        $this->assertFileExists($path);
        $this->assertMatchesRegularExpression('/\d{4}_\d{2}_\d{2}_\d{6}_CreateUsersTable\.php$/', $path);
    }

    public function testCreateMigrationWithDependencies() {
        $generator = new DatabaseChangeGenerator();
        $generator->setPath($this->tempDir);

        $path = $generator->createMigration('AddPostsTable', [
            GeneratorOption::DEPENDENCIES => ['CreateUsersTable', 'App\\Migrations\\CreateCategoriesTable']
        ]);

        $content = file_get_contents($path);
        $this->assertStringContainsString('public function getDependencies(): array', $content);
        $this->assertStringContainsString('CreateUsersTable::class', $content);
        $this->assertStringContainsString('App\\Migrations\\CreateCategoriesTable::class', $content);
    }

    public function testCreateMigrationWithTableHint() {
        $generator = new DatabaseChangeGenerator();
        $generator->setPath($this->tempDir);

        $path = $generator->createMigration('CreateUsersTable', [
            GeneratorOption::TABLE => 'users'
        ]);

        $content = file_get_contents($path);
        $this->assertStringContainsString("table 'users'", $content);
    }

    public function testCreateSeederBasic() {
        $generator = new DatabaseChangeGenerator();
        $generator->setPath($this->tempDir);
        $generator->setNamespace('App\\Seeders');

        $path = $generator->createSeeder('UsersSeeder');

        $this->assertFileExists($path);
        $this->assertStringEndsWith('UsersSeeder.php', $path);

        $content = file_get_contents($path);
        $this->assertStringContainsString('namespace App\\Seeders;', $content);
        $this->assertStringContainsString('use WebFiori\Database\Schema\AbstractSeeder;', $content);
        $this->assertStringContainsString('class UsersSeeder extends AbstractSeeder', $content);
        $this->assertStringContainsString('public function run(Database $db): void', $content);
    }

    public function testCreateSeederWithEnvironments() {
        $generator = new DatabaseChangeGenerator();
        $generator->setPath($this->tempDir);

        $path = $generator->createSeeder('TestDataSeeder', [
            GeneratorOption::ENVIRONMENTS => ['dev', 'test']
        ]);

        $content = file_get_contents($path);
        $this->assertStringContainsString('public function getEnvironments(): array', $content);
        $this->assertStringContainsString("'dev'", $content);
        $this->assertStringContainsString("'test'", $content);
    }

    public function testCreateSeederWithDependencies() {
        $generator = new DatabaseChangeGenerator();
        $generator->setPath($this->tempDir);

        $path = $generator->createSeeder('PostsSeeder', [
            GeneratorOption::DEPENDENCIES => ['UsersSeeder']
        ]);

        $content = file_get_contents($path);
        $this->assertStringContainsString('public function getDependencies(): array', $content);
        $this->assertStringContainsString('UsersSeeder::class', $content);
    }

    public function testCreateWithoutNamespace() {
        $generator = new DatabaseChangeGenerator();
        $generator->setPath($this->tempDir);

        $path = $generator->createMigration('CreateUsersTable');

        $content = file_get_contents($path);
        $this->assertStringNotContainsString('namespace', $content);
    }

    public function testCreateWithoutPathThrows() {
        $generator = new DatabaseChangeGenerator();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Path not set');

        $generator->createMigration('CreateUsersTable');
    }

    public function testCreatesDirectoryIfNotExists() {
        $generator = new DatabaseChangeGenerator();
        $newDir = $this->tempDir . '/nested/path';
        $generator->setPath($newDir);

        $path = $generator->createMigration('CreateUsersTable');

        $this->assertFileExists($path);
        $this->assertDirectoryExists($newDir);
    }

    public function testFluentInterface() {
        $generator = new DatabaseChangeGenerator();
        
        $result = $generator
            ->setPath($this->tempDir)
            ->setNamespace('App\\Migrations')
            ->useTimestampPrefix(true);

        $this->assertSame($generator, $result);
    }
}
