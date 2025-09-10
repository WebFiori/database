<?php

namespace WebFiori\Tests\Database\Migrations;

use PHPUnit\Framework\TestCase;
use WebFiori\Database\ConnectionInfo;
use WebFiori\Database\DatabaseException;
use WebFiori\Database\Migration\AbstractMigration;
use WebFiori\Database\Migration\MigrationsRunner;

class MigrationsTest extends TestCase {
    /**
     * @test
     */
    public function test00() {
        $this->expectException(DatabaseException::class);
        $this->expectExceptionMessage("Connection information not set.");
        $m = new MigrationsRunner(__DIR__. DIRECTORY_SEPARATOR, '\\WebFiori\\Tests\\Database\\Migrations', null);
        $this->assertEquals(2, count($m->getMigrations()));
        $m->createMigrationsTable();
    }
    /**
     * @test
     */
    public function test01() {
        $connInfo = new ConnectionInfo('mssql','sa', '1234567890@Eu', 'testing_db', 'localhost', 1433, [
            'TrustServerCertificate' => 'true'
        ]);
        $m = new MigrationsRunner(__DIR__. DIRECTORY_SEPARATOR, '\\WebFiori\\Tests\\Database\\Migrations', null);
        $this->assertEquals(2, count($m->getMigrations()));
        try {
            $m->createMigrationsTable();
        } catch (DatabaseException $ex) {
            $m->setConnectionInfo($connInfo);
        }
        $m->createMigrationsTable();
        $this->assertNull($m->rollback());
        $applied = $m->apply();
        $this->assertEquals(2, count($applied));
        $rolled = $m->rollback();
        $this->assertTrue($rolled instanceof AbstractMigration);
        $this->assertEquals('Mig01', $rolled->getName());
        $this->assertEquals(1, $rolled->getOrder());
        $multiRoolback = $m->rollbackUpTo('Mig00');
        $mig00 = $multiRoolback[0];
        $this->assertTrue($mig00 instanceof AbstractMigration);
        $this->assertEquals('Mig00', $mig00->getName());
        $this->assertEquals(0, $mig00->getOrder());
        $m->dropMigrationsTable();
    }
}
