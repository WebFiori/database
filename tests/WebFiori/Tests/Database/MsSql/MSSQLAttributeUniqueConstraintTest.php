<?php
namespace WebFiori\Tests\Database\MsSql;

use PHPUnit\Framework\TestCase;
use WebFiori\Database\Attributes\AttributeTableBuilder;

/**
 * Test case for GitHub issue #153:
 * AttributeTableBuilder does not create UNIQUE constraints for MSSQL columns.
 *
 * When using #[Column] with unique: true, the generated CREATE TABLE SQL
 * must include a UNIQUE constraint for MSSQL.
 */
class MSSQLAttributeUniqueConstraintTest extends TestCase {
    /**
     * @test
     * Verifies that a column marked with unique: true in a #[Column] attribute
     * produces a UNIQUE constraint in the generated MSSQL CREATE TABLE SQL.
     */
    public function testUniqueConstraintInGeneratedSQL() {
        $table = AttributeTableBuilder::build(
            MSSQLAttributeTestUser::class,
            'mssql'
        );

        $sql = $table->toSQL();

        $this->assertStringContainsString('unique', strtolower($sql),
            'Generated SQL should contain a UNIQUE constraint for the email column');
        $this->assertStringContainsString('email', $sql,
            'Generated SQL UNIQUE constraint should reference the email column');
    }

    /**
     * @test
     * Verifies the exact constraint format: "constraint AK_<table> unique (email)"
     */
    public function testUniqueConstraintFormat() {
        $table = AttributeTableBuilder::build(
            MSSQLAttributeTestUser::class,
            'mssql'
        );

        $sql = $table->toSQL();

        $this->assertMatchesRegularExpression(
            '/constraint\s+AK_test_users\s+unique\s*\(.*email.*\)/i',
            $sql,
            'Generated SQL should have constraint AK_test_users unique (...email...)'
        );
    }

    /**
     * @test
     * Verifies that the column object itself is marked as unique when built
     * from attributes (precondition for the constraint to appear in SQL).
     */
    public function testColumnIsMarkedUnique() {
        $table = AttributeTableBuilder::build(
            MSSQLAttributeTestUser::class,
            'mssql'
        );

        $emailCol = $table->getColByKey('email');
        $this->assertNotNull($emailCol, 'Table should have an email column');
        $this->assertTrue($emailCol->isUnique(),
            'The email column should be marked as unique');
    }

    /**
     * @test
     * Verifies that getUniqueCols() returns the unique column so that
     * MSSQLTable::createUniqueString() can generate the constraint.
     */
    public function testGetUniqueColsReturnsUniqueColumn() {
        $table = AttributeTableBuilder::build(
            MSSQLAttributeTestUser::class,
            'mssql'
        );

        $uniqueCols = $table->getUniqueCols();
        $this->assertNotEmpty($uniqueCols,
            'getUniqueCols() should return at least one column for a table with unique attributes');

        $colNames = array_map(fn($col) => $col->getNormalName(), $uniqueCols);
        $this->assertContains('email', $colNames,
            'email column should be in the unique columns list');
    }

    /**
     * @test
     * Reproduces the exact scenario from issue #153:
     * NVARCHAR column with unique: true should produce a UNIQUE constraint.
     */
    public function testIssue153ExactScenario() {
        $table = AttributeTableBuilder::build(
            MSSQLAttributeTestUniqueNvarchar::class,
            'mssql'
        );

        $sql = $table->toSQL();

        // Verify the constraint is present for the NVARCHAR unique column
        $this->assertMatchesRegularExpression(
            '/constraint\s+AK_test_table\s+unique\s*\(.*name.*\)/i',
            $sql,
            'NVARCHAR column with unique: true must produce a UNIQUE constraint in SQL'
        );
    }

    /**
     * @test
     * Verifies that duplicate inserts would be prevented by the constraint
     * by checking the SQL structure contains the proper constraint syntax.
     */
    public function testUniqueConstraintSQLStructure() {
        $table = AttributeTableBuilder::build(
            MSSQLAttributeTestUniqueNvarchar::class,
            'mssql'
        );

        $sql = $table->toSQL();

        // The full SQL should have: identity column, primary key, AND unique constraint
        $this->assertStringContainsString('identity(1,1)', $sql);
        $this->assertStringContainsString('primary key clustered', $sql);
        $this->assertStringContainsString('unique (name)', $sql);
    }
}
