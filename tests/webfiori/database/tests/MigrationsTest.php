<?php

namespace webfiori\database\tests;

use PHPUnit\Framework\TestCase;
use webfiori\database\ConnectionInfo;
use webfiori\database\DatabaseException;
use webfiori\database\migration\AbstractMigration;
use webfiori\database\migration\MigrationsRunner;

class MigrationsTest extends TestCase {
    /**
     * @test
     */
    public function test00() {
        $this->expectException(DatabaseException::class);
        $this->expectExceptionMessage("Connection information not set.");
        $m = new MigrationsRunner(__DIR__. DIRECTORY_SEPARATOR.'migrations', '\\webfiori\\database\\tests\\migrations', null);
        $this->assertEquals(2, count($m->getMigrations()));
        $m->createMigrationsTable();
    }
    /**
     * @test
     */
    public function test01() {
        $connInfo = new ConnectionInfo('mssql','sa', '1234567890@Eu', 'testing_db', SQL_SERVER_HOST, 1433, [
            'TrustServerCertificate' => 'true'
        ]);
        $m = new MigrationsRunner(__DIR__. DIRECTORY_SEPARATOR.'migrations', '\\webfiori\\database\\tests\\migrations', null);
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
