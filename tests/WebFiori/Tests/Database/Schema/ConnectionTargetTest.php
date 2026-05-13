<?php
namespace WebFiori\Tests\Database\Schema;

use PHPUnit\Framework\TestCase;
use WebFiori\Database\ConnectionInfo;
use WebFiori\Database\Database;
use WebFiori\Database\Schema\AbstractMigration;
use WebFiori\Database\Schema\AbstractSeeder;
use WebFiori\Database\Schema\SchemaRunner;

// --- Fixtures ---

class MigrationForAllConnections extends AbstractMigration {
    public function down(Database $db): void {
    }
    public function up(Database $db): void {
        $db->table('schema_changes')->select(['id'])->limit(1)->execute();
    }
}

class MigrationForReportingDb extends AbstractMigration {
    public function down(Database $db): void {
    }
    public function getTargetConnections(): array {
        return ['reporting-db'];
    }

    public function up(Database $db): void {
        $db->table('schema_changes')->select(['id'])->limit(1)->execute();
    }
}

class MigrationForMasterDb extends AbstractMigration {
    public function down(Database $db): void {
    }
    public function getTargetConnections(): array {
        return ['master-db'];
    }

    public function up(Database $db): void {
        $db->table('schema_changes')->select(['id'])->limit(1)->execute();
    }
}

class MigrationForMultipleConnections extends AbstractMigration {
    public function down(Database $db): void {
    }
    public function getTargetConnections(): array {
        return ['reporting-db', 'master-db'];
    }

    public function up(Database $db): void {
        $db->table('schema_changes')->select(['id'])->limit(1)->execute();
    }
}

class SeederForReportingDb extends AbstractSeeder {
    public function getTargetConnections(): array {
        return ['reporting-db'];
    }

    public function run(Database $db): void {
        $db->table('schema_changes')->select(['id'])->limit(1)->execute();
    }
}

// --- Tests ---

class ConnectionTargetTest extends TestCase {
    // Test: applyOne respects connection filtering
    public function testApplyOneSkipsMismatch() {
        try {
            $runner = $this->createRunner('master-db');
            $runner->register(MigrationForReportingDb::class);
            $runner->register(MigrationForAllConnections::class);
            $runner->createSchemaTable();

            $applied = $runner->applyOne();
            // Should skip reporting-db migration and apply the all-connections one
            $this->assertNotNull($applied);
            $this->assertEquals(MigrationForAllConnections::class, $applied->getName());

            $runner->rollbackUpTo(null);
            $runner->dropSchemaTable();
        } catch (\PHPUnit\Framework\AssertionFailedError $ex) {
            throw $ex;
        } catch (\Exception $ex) {
            $this->markTestSkipped('Database connection failed: '.$ex->getMessage());
        }
    }

    // Test: getTargetConnections() returns declared connections
    public function testDeclaredTargetConnections() {
        $migration = new MigrationForReportingDb();
        $this->assertEquals(['reporting-db'], $migration->getTargetConnections());
    }

    // Test: getTargetConnections() defaults to empty array
    public function testDefaultTargetConnections() {
        $migration = new MigrationForAllConnections();
        $this->assertEquals([], $migration->getTargetConnections());
    }

    // Test: getPendingChanges respects connection filtering
    public function testGetPendingChangesFiltered() {
        try {
            $runner = $this->createRunner('master-db');
            $runner->register(MigrationForAllConnections::class);
            $runner->register(MigrationForReportingDb::class);
            $runner->createSchemaTable();

            $pending = $runner->getPendingChanges();
            // Only the all-connections migration should be pending
            $this->assertCount(1, $pending);
            $this->assertEquals(MigrationForAllConnections::class, $pending[0]['change']->getName());

            $runner->dropSchemaTable();
        } catch (\PHPUnit\Framework\AssertionFailedError $ex) {
            throw $ex;
        } catch (\Exception $ex) {
            $this->markTestSkipped('Database connection failed: '.$ex->getMessage());
        }
    }

    // Test: Migration targeting 'reporting-db' runs when connection is 'reporting-db'
    public function testMatchingConnectionRuns() {
        try {
            $runner = $this->createRunner('reporting-db');
            $runner->register(MigrationForReportingDb::class);
            $runner->createSchemaTable();

            $result = $runner->apply();
            $this->assertCount(1, $result->getApplied());

            $runner->rollbackUpTo(null);
            $runner->dropSchemaTable();
        } catch (\PHPUnit\Framework\AssertionFailedError $ex) {
            throw $ex;
        } catch (\Exception $ex) {
            $this->markTestSkipped('Database connection failed: '.$ex->getMessage());
        }
    }

    // Test: Migration targeting 'reporting-db' is skipped when connection is 'master-db'
    public function testMismatchedConnectionSkipped() {
        try {
            $runner = $this->createRunner('master-db');
            $runner->register(MigrationForReportingDb::class);
            $runner->createSchemaTable();

            $result = $runner->apply();
            $this->assertCount(0, $result->getApplied());
            $this->assertCount(1, $result->getSkipped());

            $runner->dropSchemaTable();
        } catch (\PHPUnit\Framework\AssertionFailedError $ex) {
            throw $ex;
        } catch (\Exception $ex) {
            $this->markTestSkipped('Database connection failed: '.$ex->getMessage());
        }
    }

    // Test: Mixed - some match, some don't
    public function testMixedConnectionTargeting() {
        try {
            $runner = $this->createRunner('reporting-db');
            $runner->register(MigrationForAllConnections::class);
            $runner->register(MigrationForReportingDb::class);
            $runner->register(MigrationForMasterDb::class);
            $runner->createSchemaTable();

            $result = $runner->apply();
            // All-connections + reporting-db should run, master-db should be skipped
            $this->assertCount(2, $result->getApplied());
            $this->assertCount(1, $result->getSkipped());

            $runner->rollbackUpTo(null);
            $runner->dropSchemaTable();
        } catch (\PHPUnit\Framework\AssertionFailedError $ex) {
            throw $ex;
        } catch (\Exception $ex) {
            $this->markTestSkipped('Database connection failed: '.$ex->getMessage());
        }
    }

    // Test: Migration targeting multiple connections runs on any of them
    public function testMultipleTargetConnections() {
        try {
            $runner = $this->createRunner('master-db');
            $runner->register(MigrationForMultipleConnections::class);
            $runner->createSchemaTable();

            $result = $runner->apply();
            $this->assertCount(1, $result->getApplied());

            $runner->rollbackUpTo(null);
            $runner->dropSchemaTable();
        } catch (\PHPUnit\Framework\AssertionFailedError $ex) {
            throw $ex;
        } catch (\Exception $ex) {
            $this->markTestSkipped('Database connection failed: '.$ex->getMessage());
        }
    }

    // Test: Migration with no target runs on any connection
    public function testNoTargetRunsOnAnyConnection() {
        try {
            $runner = $this->createRunner('some-random-db');
            $runner->register(MigrationForAllConnections::class);
            $runner->createSchemaTable();

            $result = $runner->apply();
            $this->assertCount(1, $result->getApplied());

            $runner->rollbackUpTo(null);
            $runner->dropSchemaTable();
        } catch (\PHPUnit\Framework\AssertionFailedError $ex) {
            throw $ex;
        } catch (\Exception $ex) {
            $this->markTestSkipped('Database connection failed: '.$ex->getMessage());
        }
    }

    // Test: Seeder respects getTargetConnections
    public function testSeederConnectionFiltering() {
        try {
            $runner = $this->createRunner('master-db');
            $runner->register(SeederForReportingDb::class);
            $runner->createSchemaTable();

            $result = $runner->apply();
            $this->assertCount(0, $result->getApplied());
            $this->assertCount(1, $result->getSkipped());

            $runner->dropSchemaTable();
        } catch (\PHPUnit\Framework\AssertionFailedError $ex) {
            throw $ex;
        } catch (\Exception $ex) {
            $this->markTestSkipped('Database connection failed: '.$ex->getMessage());
        }
    }

    // Test: Skipped reason is 'Connection mismatch'
    public function testSkippedReason() {
        try {
            $runner = $this->createRunner('master-db');
            $runner->register(MigrationForReportingDb::class);
            $runner->createSchemaTable();

            $result = $runner->apply();
            $skipped = $result->getSkipped();
            $this->assertCount(1, $skipped);
            $this->assertEquals('Connection mismatch', $skipped[0]['reason']);

            $runner->dropSchemaTable();
        } catch (\PHPUnit\Framework\AssertionFailedError $ex) {
            throw $ex;
        } catch (\Exception $ex) {
            $this->markTestSkipped('Database connection failed: '.$ex->getMessage());
        }
    }

    private function createRunner(string $connectionName = 'reporting-db'): SchemaRunner {
        return new SchemaRunner($this->getConnectionInfo($connectionName));
    }
    private function getConnectionInfo(string $name = 'reporting-db'): ConnectionInfo {
        $info = new ConnectionInfo('mysql', 'root', getenv('MYSQL_ROOT_PASSWORD') ?: '123456', 'testing_db', '127.0.0.1');
        $info->setName($name);

        return $info;
    }
}
