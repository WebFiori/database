<?php

namespace WebFiori\Tests\Database\Schema;

use PHPUnit\Framework\TestCase;
use WebFiori\Database\ConnectionInfo;
use WebFiori\Database\Database;
use WebFiori\Database\Schema\AbstractMigration;
use WebFiori\Database\Schema\SchemaRunner;

class SkipMigrationA extends AbstractMigration {
    public function up(Database $db): void {
        $db->table('schema_changes')->select(['id'])->limit(1)->execute();
    }

    public function down(Database $db): void {
    }
}

class SkipMigrationB extends AbstractMigration {
    public function up(Database $db): void {
        $db->table('schema_changes')->select(['id'])->limit(1)->execute();
    }

    public function down(Database $db): void {
    }
}

class SkipMigrationC extends AbstractMigration {
    public function up(Database $db): void {
        $db->table('schema_changes')->select(['id'])->limit(1)->execute();
    }

    public function down(Database $db): void {
    }
}

class SkipBaselineTest extends TestCase {
    private function getConnectionInfo(): ConnectionInfo {
        return new ConnectionInfo('mysql', 'root', getenv('MYSQL_ROOT_PASSWORD') ?: '123456', 'testing_db', '127.0.0.1');
    }

    private function createRunner(): SchemaRunner {
        $runner = new SchemaRunner($this->getConnectionInfo());
        $runner->register(SkipMigrationA::class);
        $runner->register(SkipMigrationB::class);
        $runner->register(SkipMigrationC::class);
        return $runner;
    }

    public function testSkipSingle() {
        try {
            $runner = $this->createRunner();
            $runner->createSchemaTable();

            $result = $runner->skip(SkipMigrationA::class);
            $this->assertTrue($result);
            $this->assertTrue($runner->isApplied(SkipMigrationA::class));

            // apply() should not re-run it
            $applyResult = $runner->apply();
            $appliedNames = array_map(fn($c) => $c->getName(), $applyResult->getApplied());
            $this->assertNotContains(SkipMigrationA::class, $appliedNames);

            $runner->rollbackUpTo(null);
            $runner->dropSchemaTable();
        } catch (\PHPUnit\Framework\AssertionFailedError $ex) {
            throw $ex;
        } catch (\Exception $ex) {
            $this->markTestSkipped('Database connection failed: ' . $ex->getMessage());
        }
    }

    public function testSkipAlreadyApplied() {
        try {
            $runner = $this->createRunner();
            $runner->createSchemaTable();

            // Apply first
            $runner->apply();

            // Try to skip — should return false
            $result = $runner->skip(SkipMigrationA::class);
            $this->assertFalse($result);

            $runner->rollbackUpTo(null);
            $runner->dropSchemaTable();
        } catch (\PHPUnit\Framework\AssertionFailedError $ex) {
            throw $ex;
        } catch (\Exception $ex) {
            $this->markTestSkipped('Database connection failed: ' . $ex->getMessage());
        }
    }

    public function testSkipAll() {
        try {
            $runner = $this->createRunner();
            $runner->createSchemaTable();

            $skipped = $runner->skipAll();
            $this->assertCount(3, $skipped);

            // All should be marked as applied now
            $this->assertTrue($runner->isApplied(SkipMigrationA::class));
            $this->assertTrue($runner->isApplied(SkipMigrationB::class));
            $this->assertTrue($runner->isApplied(SkipMigrationC::class));

            // Nothing pending
            $pending = $runner->getPendingChanges();
            $this->assertEmpty($pending);

            $runner->rollbackUpTo(null);
            $runner->dropSchemaTable();
        } catch (\PHPUnit\Framework\AssertionFailedError $ex) {
            throw $ex;
        } catch (\Exception $ex) {
            $this->markTestSkipped('Database connection failed: ' . $ex->getMessage());
        }
    }

    public function testSkipUpTo() {
        try {
            $runner = $this->createRunner();
            $runner->createSchemaTable();

            $skipped = $runner->skipUpTo(SkipMigrationB::class);
            $this->assertCount(2, $skipped);

            $this->assertTrue($runner->isApplied(SkipMigrationA::class));
            $this->assertTrue($runner->isApplied(SkipMigrationB::class));
            $this->assertFalse($runner->isApplied(SkipMigrationC::class));

            $runner->rollbackUpTo(null);
            $runner->dropSchemaTable();
        } catch (\PHPUnit\Framework\AssertionFailedError $ex) {
            throw $ex;
        } catch (\Exception $ex) {
            $this->markTestSkipped('Database connection failed: ' . $ex->getMessage());
        }
    }

    public function testSkipNext() {
        try {
            $runner = $this->createRunner();
            $runner->createSchemaTable();

            $skipped = $runner->skipNext(2);
            $this->assertCount(2, $skipped);

            $this->assertTrue($runner->isApplied(SkipMigrationA::class));
            $this->assertTrue($runner->isApplied(SkipMigrationB::class));
            $this->assertFalse($runner->isApplied(SkipMigrationC::class));

            $runner->rollbackUpTo(null);
            $runner->dropSchemaTable();
        } catch (\PHPUnit\Framework\AssertionFailedError $ex) {
            throw $ex;
        } catch (\Exception $ex) {
            $this->markTestSkipped('Database connection failed: ' . $ex->getMessage());
        }
    }

    public function testSkipRecordsStatusColumn() {
        try {
            $runner = $this->createRunner();
            $runner->createSchemaTable();

            $runner->skip(SkipMigrationA::class);

            $records = $runner->getRepository()->getAllApplied();
            $record = null;

            foreach ($records as $r) {
                if ($r['change_name'] === SkipMigrationA::class) {
                    $record = $r;
                    break;
                }
            }

            $this->assertNotNull($record);
            $this->assertEquals('skipped', $record['status']);

            $runner->rollbackUpTo(null);
            $runner->dropSchemaTable();
        } catch (\PHPUnit\Framework\AssertionFailedError $ex) {
            throw $ex;
        } catch (\Exception $ex) {
            $this->markTestSkipped('Database connection failed: ' . $ex->getMessage());
        }
    }

    public function testPendingExcludesSkipped() {
        try {
            $runner = $this->createRunner();
            $runner->createSchemaTable();

            $runner->skip(SkipMigrationA::class);

            $pending = $runner->getPendingChanges();
            $pendingNames = array_map(fn($p) => $p['change']->getName(), $pending);

            $this->assertNotContains(SkipMigrationA::class, $pendingNames);
            $this->assertContains(SkipMigrationB::class, $pendingNames);
            $this->assertContains(SkipMigrationC::class, $pendingNames);

            $runner->rollbackUpTo(null);
            $runner->dropSchemaTable();
        } catch (\PHPUnit\Framework\AssertionFailedError $ex) {
            throw $ex;
        } catch (\Exception $ex) {
            $this->markTestSkipped('Database connection failed: ' . $ex->getMessage());
        }
    }

    public function testSkipNotFound() {
        try {
            $runner = $this->createRunner();
            $runner->createSchemaTable();

            $result = $runner->skip('NonExistent\\Migration');
            $this->assertFalse($result);

            $runner->dropSchemaTable();
        } catch (\PHPUnit\Framework\AssertionFailedError $ex) {
            throw $ex;
        } catch (\Exception $ex) {
            $this->markTestSkipped('Database connection failed: ' . $ex->getMessage());
        }
    }
}
