<?php

namespace webfiori\database\tests;

use PHPUnit\Framework\TestCase;
use webfiori\database\ConnectionInfo;
use webfiori\database\migration\AbstractMigration;
use webfiori\database\migration\MigrationsRunner;

class MigrationsTest extends TestCase {
    /**
     * @test
     */
    public function test00() {
        $connInfo = new ConnectionInfo('mysql','root', '123456', 'testing_db', '127.0.0.1');
        $m = new MigrationsRunner(__DIR__. DIRECTORY_SEPARATOR.'migrations', '\\webfiori\\database\\tests\\migrations', $connInfo);
        $this->assertEquals(2, count($m->getMigrations()));
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
    }
}
