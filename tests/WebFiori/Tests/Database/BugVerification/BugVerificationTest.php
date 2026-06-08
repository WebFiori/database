<?php

namespace WebFiori\Tests\Database\BugVerification;

use PHPUnit\Framework\TestCase;
use WebFiori\Database\Attributes\AttributeTableBuilder;
use WebFiori\Database\Attributes\Column;
use WebFiori\Database\Attributes\Table;
use WebFiori\Database\ColOption;
use WebFiori\Database\ConnectionInfo;
use WebFiori\Database\ConnectionPool;
use WebFiori\Database\Database;
use WebFiori\Database\DataType;
use WebFiori\Database\MsSql\MSSQLColumn;
use WebFiori\Database\MsSql\MSSQLTable;

// --- Fixtures for #159 ---

#[Table(name: 'issue159_test')]
class Issue159Entity {
    #[Column(name: 'created_at', type: DataType::DATETIME)]
    private ?string $createdAt = null;

    #[Column(name: 'user_name', type: DataType::VARCHAR, size: 100)]
    private string $userName = '';
}

// --- Fixtures for #153 ---

#[Table(name: 'issue153_test')]
#[Column(name: 'id', type: DataType::INT, primary: true, identity: true)]
#[Column(name: 'email', type: DataType::NVARCHAR, size: 64, unique: true)]
class Issue153Entity {
}

/**
 * Bug verification tests for GitHub issues #163, #159, #153.
 */
class BugVerificationTest extends TestCase {
    protected function tearDown(): void {
        ConnectionPool::reset();
    }

    /**
     * Issue #163: MSSQLColumn varbinary/varchar MAX size (-1) creates column
     * with size 1 instead of MAX.
     */
    public function testIssue163_VarbinaryMaxSize() {
        $col = new MSSQLColumn('data', 'varbinary', -1);
        // Bug: size becomes 1 because setSize(-1) returns false
        $this->assertEquals(-1, $col->getSize(),
            'Issue #163: varbinary column with size -1 should retain -1 for MAX');
    }

    /**
     * Issue #163: varchar(max) should also work.
     */
    public function testIssue163_VarcharMaxSize() {
        $col = new MSSQLColumn('content', 'varchar', -1);
        $this->assertEquals(-1, $col->getSize(),
            'Issue #163: varchar column with size -1 should retain -1 for MAX');
    }

    /**
     * Issue #163: nvarchar(max) should also work.
     */
    public function testIssue163_NvarcharMaxSize() {
        $col = new MSSQLColumn('content', 'nvarchar', -1);
        $this->assertEquals(-1, $col->getSize(),
            'Issue #163: nvarchar column with size -1 should retain -1 for MAX');
    }

    /**
     * Issue #163: The SQL output should say "max" not "1" or "-1".
     */
    public function testIssue163_SqlOutputContainsMax() {
        $col = new MSSQLColumn('data', 'varbinary', -1);
        $sql = $col->asString();
        $this->assertStringContainsStringIgnoringCase('max', $sql,
            'Issue #163: SQL output should contain "max" for size -1, got: ' . $sql);
        $this->assertStringNotContainsString('(-1)', $sql,
            'Issue #163: SQL output should not contain literal "(-1)"');
    }

    /**
     * Issue #159: Column attribute 'name' parameter is ignored during
     * property-level table creation. The actual DB column name should use
     * the explicit name from the attribute, not the propertyToKey() result.
     */
    public function testIssue159_ColumnNameFromAttribute() {
        $table = AttributeTableBuilder::build(Issue159Entity::class, 'mysql');

        // The attribute says name: 'created_at'
        $col = $table->getColByName('created_at');
        $this->assertNotNull($col,
            'Issue #159: Column with name "created_at" should exist (from attribute name parameter)');

        $col2 = $table->getColByName('user_name');
        $this->assertNotNull($col2,
            'Issue #159: Column with name "user_name" should exist (from attribute name parameter)');
    }

    /**
     * Issue #159: Verify on MSSQL as well.
     */
    public function testIssue159_ColumnNameFromAttributeMSSQL() {
        $table = AttributeTableBuilder::build(Issue159Entity::class, 'mssql');

        $col = $table->getColByName('created_at');
        $this->assertNotNull($col,
            'Issue #159 (MSSQL): Column with name "created_at" should exist');
    }

    /**
     * Issue #153: AttributeTableBuilder does not create UNIQUE constraints
     * for MSSQL columns when using class-level Column attributes.
     */
    public function testIssue153_UniqueConstraintMSSQL() {
        $table = AttributeTableBuilder::build(Issue153Entity::class, 'mssql');

        $uniqueCols = $table->getUniqueCols();
        $this->assertNotEmpty($uniqueCols,
            'Issue #153: Table should have at least one unique column');

        $emailCol = $table->getColByName('email');
        $this->assertNotNull($emailCol, 'email column should exist');
        $this->assertTrue($emailCol->isUnique(),
            'Issue #153: email column should be marked as unique');
    }

    /**
     * Issue #153: The generated CREATE TABLE SQL should contain a UNIQUE constraint.
     */
    public function testIssue153_CreateTableSqlContainsUnique() {
        $table = AttributeTableBuilder::build(Issue153Entity::class, 'mssql');
        $sql = $table->toSQL();

        $this->assertStringContainsStringIgnoringCase('unique', $sql,
            'Issue #153: CREATE TABLE SQL should contain UNIQUE constraint. Got: ' . $sql);
    }

    /**
     * Issue #153: Integration test — inserting duplicate values should fail.
     */
    public function testIssue153_UniqueConstraintEnforcedOnMSSQL() {
        try {
            $conn = new ConnectionInfo('mssql', 'sa', getenv('SA_SQL_SERVER_PASSWORD') ?: 'YourStr0ng!Pass', 'testing_db', 'localhost', 1433, [
                'TrustServerCertificate' => 'true'
            ]);
            $db = new Database($conn);
            $table = AttributeTableBuilder::build(Issue153Entity::class, 'mssql');
            $db->addTable($table);
            $db->table('issue153_test')->createTable()->execute();

            // First insert should succeed
            $db->table('issue153_test')->insert(['email' => 'test@example.com'])->execute();

            // Second insert with same email should fail
            $this->expectException(\WebFiori\Database\DatabaseException::class);
            $db->table('issue153_test')->insert(['email' => 'test@example.com'])->execute();
        } catch (\WebFiori\Database\DatabaseException $ex) {
            if (str_contains($ex->getMessage(), 'Unable to connect')) {
                $this->markTestSkipped('MSSQL connection failed: ' . $ex->getMessage());
            }
            // Re-throw if it's the expected unique violation
            throw $ex;
        } finally {
            try {
                if (isset($db)) {
                    $db->table('issue153_test')->drop()->execute();
                }
            } catch (\Exception $e) {
            }
        }
    }
}
