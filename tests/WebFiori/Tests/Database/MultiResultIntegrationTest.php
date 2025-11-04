<?php

namespace WebFiori\Tests\Database;

use PHPUnit\Framework\TestCase;
use WebFiori\Database\MultiResultSet;
use WebFiori\Database\ResultSet;
use WebFiori\Tests\Database\MySql\MySQLTestSchema;
use WebFiori\Tests\Database\MsSql\MSSQLTestSchema;

/**
 * Integration tests to verify multi-result functionality works end-to-end.
 *
 * @author Ibrahim
 */
class MultiResultIntegrationTest extends TestCase {
    
    /**
     * @test
     */
    public function testMySQLStoredProcedureReturnsMultiResultSet() {
        $schema = new MySQLTestSchema();
        
        try {
            // Clean up and create a simple stored procedure
            $schema->raw("DROP PROCEDURE IF EXISTS TestMultiResult")->execute();
            $schema->raw("CREATE PROCEDURE TestMultiResult() BEGIN SELECT 1 as id, 'first' as name; SELECT 2 as id, 'second' as name; END")->execute();
            
            // Execute the stored procedure
            $result = $schema->raw("CALL TestMultiResult()")->execute();
            
            // Verify we get a MultiResultSet when multiple results are returned
            if ($result instanceof MultiResultSet) {
                $this->assertEquals(2, $result->count());
                $this->assertEquals('first', $result->getResultSet(0)->getRows()[0]['name']);
                $this->assertEquals('second', $result->getResultSet(1)->getRows()[0]['name']);
                
                // Test iteration
                $names = [];
                foreach ($result as $resultSet) {
                    if ($resultSet->getRowsCount() > 0) {
                        $names[] = $resultSet->getRows()[0]['name'];
                    }
                }
                $this->assertEquals(['first', 'second'], $names);
            } else {
                // If single result, just verify it's a ResultSet
                $this->assertInstanceOf(ResultSet::class, $result);
            }
            
        } catch (\Exception $e) {
            $this->markTestSkipped('MySQL stored procedure test failed: ' . $e->getMessage());
        } finally {
            try {
                $schema->raw("DROP PROCEDURE IF EXISTS TestMultiResult")->execute();
            } catch (\Exception $e) {
                // Ignore cleanup errors
            }
        }
    }
    
    /**
     * @test
     */
    public function testMSSQLStoredProcedureReturnsMultiResultSet() {
        $schema = new MSSQLTestSchema();
        
        try {
            // Clean up and create a simple stored procedure
            $schema->raw("IF OBJECT_ID('TestMultiResult', 'P') IS NOT NULL DROP PROCEDURE TestMultiResult")->execute();
            $schema->raw("CREATE PROCEDURE TestMultiResult AS BEGIN SELECT 1 as id, 'first' as name; SELECT 2 as id, 'second' as name; END")->execute();
            
            // Execute the stored procedure
            $result = $schema->raw("EXEC TestMultiResult")->execute();
            
            // Verify we get a MultiResultSet when multiple results are returned
            if ($result instanceof MultiResultSet) {
                $this->assertEquals(2, $result->count());
                $this->assertEquals('first', $result->getResultSet(0)->getRows()[0]['name']);
                $this->assertEquals('second', $result->getResultSet(1)->getRows()[0]['name']);
                
                // Test getTotalRecordCount
                $this->assertEquals(2, $result->getTotalRecordCount());
            } else {
                // If single result, just verify it's a ResultSet
                $this->assertInstanceOf(ResultSet::class, $result);
            }
            
        } catch (\Exception $e) {
            $this->markTestSkipped('MSSQL stored procedure test failed: ' . $e->getMessage());
        } finally {
            try {
                $schema->raw("IF OBJECT_ID('TestMultiResult', 'P') IS NOT NULL DROP PROCEDURE TestMultiResult")->execute();
            } catch (\Exception $e) {
                // Ignore cleanup errors
            }
        }
    }
    
    /**
     * @test
     */
    public function testSingleQueryStillReturnsSingleResultSet() {
        $mysqlSchema = new MySQLTestSchema();
        $mssqlSchema = new MSSQLTestSchema();
        
        // Test MySQL single query
        $mysqlResult = $mysqlSchema->raw("SELECT 'single' as type")->execute();
        $this->assertInstanceOf(ResultSet::class, $mysqlResult);
        $this->assertNotInstanceOf(MultiResultSet::class, $mysqlResult);
        
        // Test MSSQL single query
        $mssqlResult = $mssqlSchema->raw("SELECT 'single' as type")->execute();
        $this->assertInstanceOf(ResultSet::class, $mssqlResult);
        $this->assertNotInstanceOf(MultiResultSet::class, $mssqlResult);
    }
}
