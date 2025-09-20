<?php

namespace WebFiori\Tests\Database\Migrations;

use PHPUnit\Framework\TestCase;
use WebFiori\Database\ConnectionInfo;
use WebFiori\Database\DatabaseException;
use WebFiori\Database\Migration\AbstractMigration;
use WebFiori\Database\Migration\MigrationsRunner;

class UpdatedMigrationsTest extends TestCase {
    
    /**
     * @test
     */
    public function testMigrationExtendsSchemaAbstractMigration() {
        $connInfo = new ConnectionInfo('mysql', 'root', '123456', 'testing_db');
        
        try {
            $runner = new MigrationsRunner(__DIR__, 'WebFiori\\Tests\\Database\\Migrations', $connInfo);
            $migrations = $runner->getMigrations();
            
            foreach ($migrations as $migration) {
                $this->assertInstanceOf(AbstractMigration::class, $migration);
                $this->assertEquals('migration', $migration->getType());
            }
            
        } catch (DatabaseException $ex) {
            $this->markTestSkipped('Database connection failed: ' . $ex->getMessage());
        }
    }
    
    /**
     * @test
     */
    public function testBackwardCompatibility() {
        try {
            $connInfo = new ConnectionInfo('mysql', 'root', '123456', 'testing_db');
            $runner = new MigrationsRunner(__DIR__, 'WebFiori\\Tests\\Database\\Migrations', $connInfo);
            
            // Test that existing migration methods still work
            $runner->createMigrationsTable();
            
            $applied = $runner->apply();
            $this->assertIsArray($applied);
            
            if (!empty($applied)) {
                $rolled = $runner->rollbackUpTo(null);
                $this->assertIsArray($rolled);
                if (!empty($rolled)) {
                    $this->assertInstanceOf(AbstractMigration::class, $rolled[0]);
                }
            }
            
            $runner->dropMigrationsTable();
            
        } catch (DatabaseException $ex) {
            $this->markTestSkipped('Database connection failed: ' . $ex->getMessage());
        }
    }
}
