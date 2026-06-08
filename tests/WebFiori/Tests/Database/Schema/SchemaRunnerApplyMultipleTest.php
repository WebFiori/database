<?php

namespace WebFiori\Tests\Database\Schema;

use PHPUnit\Framework\TestCase;
use WebFiori\Database\ConnectionInfo;
use WebFiori\Database\ConnectionPool;
use WebFiori\Database\Database;
use WebFiori\Database\Schema\AbstractMigration;
use WebFiori\Database\Schema\AbstractSeeder;
use WebFiori\Database\Schema\SchemaRunner;

// --- Test fixtures ---

class SimpleMigrationA extends AbstractMigration {
    public function up(Database $db): void {
    }

    public function down(Database $db): void {
    }
}

class SimpleMigrationB extends AbstractMigration {
    public function up(Database $db): void {
    }

    public function down(Database $db): void {
    }
}

class SimpleMigrationC extends AbstractMigration {
    public function up(Database $db): void {
    }

    public function down(Database $db): void {
    }
}

class SimpleSeederA extends AbstractSeeder {
    public function run(Database $db): void {
    }
}

/**
 * Migration that builds a query with an int binding then throws,
 * leaving stale bindings on the query generator.
 */
class FailingMigrationWithDirtyBindings extends AbstractMigration {
    public function up(Database $db): void {
        // This adds an int binding (100) to the query generator
        $db->table('schema_changes')->select()->where('batch', 100);
        // Throw before execute() — bindings remain dirty
        throw new \RuntimeException('Simulated failure');
    }

    public function down(Database $db): void {
    }

    public function useTransaction(Database $db): bool {
        return false;
    }
}

/**
 * Tests that SchemaRunner::apply() can be called multiple times without failing.
 *
 * Specifically targets the SchemaChangeRepository::count(array $cond) method
 * which is called by SchemaRunner::isApplied() on each apply() invocation.
 */
class SchemaRunnerApplyMultipleTest extends TestCase {
    protected function tearDown(): void {
        ConnectionPool::reset();
        parent::tearDown();
    }

    private function createSqliteRunner(): SchemaRunner {
        $conn = new ConnectionInfo('sqlite', '', '', ':memory:');
        $runner = new SchemaRunner($conn);
        $runner->createSchemaTable();

        return $runner;
    }

    /**
     * Test calling apply() twice - second call should skip already-applied changes.
     */
    public function testApplyTwiceNoFailure() {
        $runner = $this->createSqliteRunner();
        $runner->register(SimpleMigrationA::class);

        $result1 = $runner->apply();
        $this->assertCount(1, $result1->getApplied());
        $this->assertCount(0, $result1->getFailed());

        $result2 = $runner->apply();
        $this->assertCount(0, $result2->getApplied());
        $this->assertCount(1, $result2->getSkipped());
        $this->assertCount(0, $result2->getFailed());
    }

    /**
     * Test calling apply() three times in succession.
     */
    public function testApplyThreeTimesNoFailure() {
        $runner = $this->createSqliteRunner();
        $runner->register(SimpleMigrationA::class);
        $runner->register(SimpleMigrationB::class);

        $result1 = $runner->apply();
        $this->assertCount(2, $result1->getApplied());

        $result2 = $runner->apply();
        $this->assertCount(0, $result2->getApplied());
        $this->assertCount(2, $result2->getSkipped());

        $result3 = $runner->apply();
        $this->assertCount(0, $result3->getApplied());
        $this->assertCount(2, $result3->getSkipped());
    }

    /**
     * Test calling apply() when new migrations are registered between calls.
     */
    public function testApplyWithNewMigrationsBetweenCalls() {
        $runner = $this->createSqliteRunner();
        $runner->register(SimpleMigrationA::class);

        $result1 = $runner->apply();
        $this->assertCount(1, $result1->getApplied());

        $runner->register(SimpleMigrationB::class);
        $runner->register(SimpleMigrationC::class);

        $result2 = $runner->apply();
        $this->assertCount(2, $result2->getApplied());
        $this->assertCount(1, $result2->getSkipped());
    }

    /**
     * Test isApplied() (which calls count with conditions) after multiple apply() calls.
     */
    public function testIsAppliedAfterMultipleApplyCalls() {
        $runner = $this->createSqliteRunner();
        $runner->register(SimpleMigrationA::class);
        $runner->register(SimpleMigrationB::class);

        $this->assertFalse($runner->isApplied(SimpleMigrationA::class));
        $this->assertFalse($runner->isApplied(SimpleMigrationB::class));

        $runner->apply();

        $this->assertTrue($runner->isApplied(SimpleMigrationA::class));
        $this->assertTrue($runner->isApplied(SimpleMigrationB::class));

        $runner->apply();

        $this->assertTrue($runner->isApplied(SimpleMigrationA::class));
        $this->assertTrue($runner->isApplied(SimpleMigrationB::class));

        $runner->apply();

        $this->assertTrue($runner->isApplied(SimpleMigrationA::class));
        $this->assertTrue($runner->isApplied(SimpleMigrationB::class));
    }

    /**
     * Test repository count() with conditions called many times in a loop.
     */
    public function testCountCalledRepeatedlyInLoop() {
        $runner = $this->createSqliteRunner();
        $runner->register(SimpleMigrationA::class);
        $runner->register(SimpleMigrationB::class);
        $runner->register(SimpleMigrationC::class);
        $runner->register(SimpleSeederA::class);

        $runner->apply();

        $repo = $runner->getRepository();

        for ($i = 0; $i < 20; $i++) {
            $countA = $repo->count(['change_name' => SimpleMigrationA::class]);
            $this->assertEquals(1, $countA, "count() failed on iteration $i for SimpleMigrationA");

            $countB = $repo->count(['change_name' => SimpleMigrationB::class]);
            $this->assertEquals(1, $countB, "count() failed on iteration $i for SimpleMigrationB");

            $countC = $repo->count(['change_name' => SimpleMigrationC::class]);
            $this->assertEquals(1, $countC, "count() failed on iteration $i for SimpleMigrationC");

            $countNonExistent = $repo->count(['change_name' => 'NonExistent\\Class']);
            $this->assertEquals(0, $countNonExistent, "count() failed on iteration $i for non-existent");
        }
    }

    /**
     * Test apply() called 5 times in rapid succession.
     */
    public function testApplyFiveTimesRapidly() {
        $runner = $this->createSqliteRunner();
        $runner->register(SimpleMigrationA::class);
        $runner->register(SimpleMigrationB::class);
        $runner->register(SimpleMigrationC::class);

        for ($i = 0; $i < 5; $i++) {
            $result = $runner->apply();
            $this->assertCount(0, $result->getFailed(), "apply() failed on call #" . ($i + 1));

            if ($i === 0) {
                $this->assertCount(3, $result->getApplied());
            } else {
                $this->assertCount(0, $result->getApplied());
                $this->assertCount(3, $result->getSkipped());
            }
        }
    }

    /**
     * Test apply() after rollback and re-apply.
     */
    public function testApplyRollbackReapply() {
        $runner = $this->createSqliteRunner();
        $runner->register(SimpleMigrationA::class);
        $runner->register(SimpleMigrationB::class);

        $result1 = $runner->apply();
        $this->assertCount(2, $result1->getApplied());

        $runner->rollbackUpTo(null);

        $this->assertFalse($runner->isApplied(SimpleMigrationA::class));
        $this->assertFalse($runner->isApplied(SimpleMigrationB::class));

        $result2 = $runner->apply();
        $this->assertCount(2, $result2->getApplied());
        $this->assertCount(0, $result2->getFailed());

        $result3 = $runner->apply();
        $this->assertCount(0, $result3->getApplied());
        $this->assertCount(2, $result3->getSkipped());
    }

    /**
     * Test count() with and without conditions interleaved.
     */
    public function testCountWithAndWithoutConditions() {
        $runner = $this->createSqliteRunner();
        $runner->register(SimpleMigrationA::class);
        $runner->register(SimpleMigrationB::class);

        $runner->apply();

        $repo = $runner->getRepository();

        for ($i = 0; $i < 10; $i++) {
            $this->assertEquals(2, $repo->count());
            $this->assertEquals(1, $repo->count(['change_name' => SimpleMigrationA::class]));
            $this->assertEquals(1, $repo->count(['change_name' => SimpleMigrationB::class]));
        }
    }

    /**
     * Test that count() is not affected by prior query state on the same table.
     */
    public function testCountNotAffectedByPriorQueryState() {
        $runner = $this->createSqliteRunner();
        $runner->register(SimpleMigrationA::class);
        $runner->apply();

        $repo = $runner->getRepository();

        // Unrelated query on same table
        $runner->table('schema_changes')->select()->where('type', 'migration')->execute();

        $count = $repo->count(['change_name' => SimpleMigrationA::class]);
        $this->assertEquals(1, $count);

        // Another unrelated query
        $runner->table('schema_changes')->select()->where('batch', 1)->execute();

        $count = $repo->count(['change_name' => SimpleMigrationA::class]);
        $this->assertEquals(1, $count);
    }

    /**
     * Test that isApplied uses == 1, meaning exactly one record must exist.
     * Verifies no duplicate records are created by repeated apply() calls.
     */
    public function testNoDuplicateRecordsAfterMultipleApply() {
        $runner = $this->createSqliteRunner();
        $runner->register(SimpleMigrationA::class);

        // Apply 5 times
        for ($i = 0; $i < 5; $i++) {
            $runner->apply();
        }

        // Verify exactly 1 record exists for this migration
        $repo = $runner->getRepository();
        $count = $repo->count(['change_name' => SimpleMigrationA::class]);
        $this->assertEquals(1, $count, 'Should have exactly 1 record, not duplicates');

        // Also check total records
        $this->assertEquals(1, $repo->count());
    }

    /**
     * Test apply() multiple times with MySQL (requires MySQL server).
     */
    public function testApplyMultipleTimesMySQL() {
        try {
            $conn = new ConnectionInfo('mysql', 'root', getenv('MYSQL_ROOT_PASSWORD') ?: '123456', 'testing_db', '127.0.0.1');
            $runner = new SchemaRunner($conn);
            $runner->createSchemaTable();

            $runner->register(SimpleMigrationA::class);
            $runner->register(SimpleMigrationB::class);

            $result1 = $runner->apply();
            $this->assertCount(2, $result1->getApplied());
            $this->assertCount(0, $result1->getFailed());

            $result2 = $runner->apply();
            $this->assertCount(0, $result2->getApplied());
            $this->assertCount(2, $result2->getSkipped());
            $this->assertCount(0, $result2->getFailed());

            $result3 = $runner->apply();
            $this->assertCount(0, $result3->getApplied());
            $this->assertCount(2, $result3->getSkipped());
            $this->assertCount(0, $result3->getFailed());

            $repo = $runner->getRepository();
            $this->assertEquals(1, $repo->count(['change_name' => SimpleMigrationA::class]));
            $this->assertEquals(1, $repo->count(['change_name' => SimpleMigrationB::class]));
            $this->assertEquals(0, $repo->count(['change_name' => 'NonExistent']));

            $runner->rollbackUpTo(null);
            $runner->dropSchemaTable();
        } catch (\PHPUnit\Framework\AssertionFailedError $ex) {
            throw $ex;
        } catch (\Exception $ex) {
            $this->markTestSkipped('MySQL connection failed: ' . $ex->getMessage());
        }
    }

    /**
     * Test apply() multiple times with MSSQL (requires MSSQL server).
     */
    public function testApplyMultipleTimesMSSQL() {
        try {
            $conn = new ConnectionInfo('mssql', 'sa', getenv('SA_SQL_SERVER_PASSWORD') ?: 'YourStr0ng!Pass', 'testing_db', 'localhost', 1433, [
                'TrustServerCertificate' => 'true'
            ]);
            $runner = new SchemaRunner($conn);
            $runner->createSchemaTable();

            $runner->register(SimpleMigrationA::class);
            $runner->register(SimpleMigrationB::class);

            $result1 = $runner->apply();
            $this->assertCount(2, $result1->getApplied(), 'First apply should apply 2 migrations');
            $this->assertCount(0, $result1->getFailed());

            $result2 = $runner->apply();
            $this->assertCount(0, $result2->getApplied(), 'Second apply should not apply anything');
            $this->assertCount(2, $result2->getSkipped());
            $this->assertCount(0, $result2->getFailed());

            $result3 = $runner->apply();
            $this->assertCount(0, $result3->getApplied(), 'Third apply should not apply anything');
            $this->assertCount(2, $result3->getSkipped());
            $this->assertCount(0, $result3->getFailed());

            // Verify count() with conditions works on MSSQL
            $repo = $runner->getRepository();
            $this->assertEquals(1, $repo->count(['change_name' => SimpleMigrationA::class]));
            $this->assertEquals(1, $repo->count(['change_name' => SimpleMigrationB::class]));
            $this->assertEquals(0, $repo->count(['change_name' => 'NonExistent']));
            $this->assertEquals(2, $repo->count());

            $runner->rollbackUpTo(null);
            $runner->dropSchemaTable();
        } catch (\PHPUnit\Framework\AssertionFailedError $ex) {
            throw $ex;
        } catch (\Exception $ex) {
            $this->markTestSkipped('MSSQL connection failed: ' . $ex->getMessage());
        }
    }

    /**
     * Test apply() 10 times rapidly on MSSQL to stress the count() path.
     */
    public function testApplyTenTimesMSSQL() {
        try {
            $conn = new ConnectionInfo('mssql', 'sa', getenv('SA_SQL_SERVER_PASSWORD') ?: 'YourStr0ng!Pass', 'testing_db', 'localhost', 1433, [
                'TrustServerCertificate' => 'true'
            ]);
            $runner = new SchemaRunner($conn);
            $runner->createSchemaTable();

            $runner->register(SimpleMigrationA::class);
            $runner->register(SimpleMigrationB::class);
            $runner->register(SimpleMigrationC::class);

            for ($i = 0; $i < 10; $i++) {
                $result = $runner->apply();
                $this->assertCount(0, $result->getFailed(), "apply() failed on call #" . ($i + 1));

                if ($i === 0) {
                    $this->assertCount(3, $result->getApplied());
                } else {
                    $this->assertCount(0, $result->getApplied());
                    $this->assertCount(3, $result->getSkipped());
                }
            }

            // Verify no duplicates
            $repo = $runner->getRepository();
            $this->assertEquals(3, $repo->count());

            $runner->rollbackUpTo(null);
            $runner->dropSchemaTable();
        } catch (\PHPUnit\Framework\AssertionFailedError $ex) {
            throw $ex;
        } catch (\Exception $ex) {
            $this->markTestSkipped('MSSQL connection failed: ' . $ex->getMessage());
        }
    }

    /**
     * Test count() with conditions in a tight loop on MSSQL.
     * This specifically targets the reported failure in count(array $cond).
     */
    public function testCountWithConditionsLoopMSSQL() {
        try {
            $conn = new ConnectionInfo('mssql', 'sa', getenv('SA_SQL_SERVER_PASSWORD') ?: 'YourStr0ng!Pass', 'testing_db', 'localhost', 1433, [
                'TrustServerCertificate' => 'true'
            ]);
            $runner = new SchemaRunner($conn);
            $runner->createSchemaTable();

            $runner->register(SimpleMigrationA::class);
            $runner->register(SimpleMigrationB::class);
            $runner->apply();

            $repo = $runner->getRepository();

            for ($i = 0; $i < 30; $i++) {
                $this->assertEquals(1, $repo->count(['change_name' => SimpleMigrationA::class]),
                    "count() returned wrong value on iteration $i for SimpleMigrationA");
                $this->assertEquals(1, $repo->count(['change_name' => SimpleMigrationB::class]),
                    "count() returned wrong value on iteration $i for SimpleMigrationB");
                $this->assertEquals(0, $repo->count(['change_name' => 'DoesNotExist']),
                    "count() returned wrong value on iteration $i for non-existent");
                $this->assertEquals(2, $repo->count(),
                    "count() with no conditions returned wrong value on iteration $i");
            }

            $runner->rollbackUpTo(null);
            $runner->dropSchemaTable();
        } catch (\PHPUnit\Framework\AssertionFailedError $ex) {
            throw $ex;
        } catch (\Exception $ex) {
            $this->markTestSkipped('MSSQL connection failed: ' . $ex->getMessage());
        }
    }

    /**
     * Test that a failed migration leaving dirty bindings does not corrupt
     * subsequent count() calls. This reproduces the exact error:
     * "Conversion failed when converting the varchar value '...' to data type int"
     *
     * Root cause: migration builds select()->where('int_col', 100) then throws
     * before execute(). The int binding (100) leaks into the next count() call,
     * causing MSSQL to receive [100, 'MigrationName'] for a single ? placeholder.
     */
    public function testApplyWithFailedMigrationLeavingDirtyBindings() {
        $runner = $this->createSqliteRunner();
        $runner->register(SimpleMigrationA::class);
        $runner->register(FailingMigrationWithDirtyBindings::class);
        $runner->register(SimpleMigrationB::class);

        $result = $runner->apply();

        // SimpleMigrationA and SimpleMigrationB should succeed
        $this->assertCount(2, $result->getApplied());
        // FailingMigration should fail
        $this->assertCount(1, $result->getFailed());

        // Second apply should not crash on count()
        $result2 = $runner->apply();
        $this->assertCount(0, $result2->getApplied());
        $this->assertCount(2, $result2->getSkipped());
        $this->assertCount(1, $result2->getFailed());
    }

    /**
     * Same test on MSSQL where the original error was observed.
     */
    public function testApplyWithFailedMigrationDirtyBindingsMSSQL() {
        try {
            $conn = new ConnectionInfo('mssql', 'sa', getenv('SA_SQL_SERVER_PASSWORD') ?: 'YourStr0ng!Pass', 'testing_db', 'localhost', 1433, [
                'TrustServerCertificate' => 'true'
            ]);
            $runner = new SchemaRunner($conn);
            $runner->createSchemaTable();

            $runner->register(SimpleMigrationA::class);
            $runner->register(FailingMigrationWithDirtyBindings::class);
            $runner->register(SimpleMigrationB::class);

            $result = $runner->apply();
            $this->assertCount(2, $result->getApplied(), 'A and B should apply');
            $this->assertCount(1, $result->getFailed(), 'Failing migration should fail');
            $this->assertCount(0, $result->getSkipped());

            // Second apply — this is where the original bug manifested
            $result2 = $runner->apply();
            $this->assertCount(0, $result2->getApplied());
            $this->assertCount(2, $result2->getSkipped());
            $this->assertCount(1, $result2->getFailed());

            $runner->rollbackUpTo(null);
            $runner->dropSchemaTable();
        } catch (\PHPUnit\Framework\AssertionFailedError $ex) {
            throw $ex;
        } catch (\Exception $ex) {
            $this->markTestSkipped('MSSQL connection failed: ' . $ex->getMessage());
        }
    }
}
