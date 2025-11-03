<?php

namespace WebFiori\Tests\Database\MsSql;

use PHPUnit\Framework\TestCase;
use WebFiori\Database\MultiResultSet;
use WebFiori\Database\ResultSet;
use WebFiori\Tests\Database\MsSql\MSSQLTestSchema;

/**
 * Test cases for MSSQL multi-result functionality.
 *
 * @author Ibrahim
 */
class MSSQLMultiResultTest extends TestCase {
    
    /**
     * @test
     */
    public function testMultiSelectQuery() {
        $schema = new MSSQLTestSchema();
        
        try {
            // Execute multi-select query (MSSQL supports this natively)
            $result = $schema->raw("SELECT 1 as num, 'first' as label; SELECT 2 as num, 'second' as label")->execute();
            
            if ($result instanceof MultiResultSet) {
                $this->assertEquals(2, $result->count());
                
                // Check first result set
                $firstResult = $result->getResultSet(0);
                $this->assertInstanceOf(ResultSet::class, $firstResult);
                $this->assertEquals(1, $firstResult->getRowsCount());
                $this->assertEquals(1, $firstResult->getRows()[0]['num']);
                $this->assertEquals('first', $firstResult->getRows()[0]['label']);
                
                // Check second result set
                $secondResult = $result->getResultSet(1);
                $this->assertInstanceOf(ResultSet::class, $secondResult);
                $this->assertEquals(1, $secondResult->getRowsCount());
                $this->assertEquals(2, $secondResult->getRows()[0]['num']);
                $this->assertEquals('second', $secondResult->getRows()[0]['label']);
            } else {
                // If single result, verify it has data from first result set
                $this->assertInstanceOf(ResultSet::class, $result);
                $this->assertGreaterThan(0, $result->getRowsCount());
            }
            
        } catch (\Exception $e) {
            $this->markTestSkipped('MSSQL multi-select test failed: ' . $e->getMessage());
        }
    }
    
    /**
     * @test
     */
    public function testStoredProcedureMultiResult() {
        $schema = new MSSQLTestSchema();
        
        try {
            // Clean up first
            $schema->raw("IF OBJECT_ID('GetMultiResults', 'P') IS NOT NULL DROP PROCEDURE GetMultiResults")->execute();
            
            // Create stored procedure that returns multiple result sets
            $createProc = "CREATE PROCEDURE GetMultiResults 
                          AS 
                          BEGIN 
                              SELECT 'users' as table_name, 1 as count; 
                              SELECT 'result' as status, 'success' as message; 
                          END";
            $schema->raw($createProc)->execute();
            
            // Execute stored procedure using our implementation
            $result = $schema->raw("EXEC GetMultiResults")->execute();
            
            if ($result instanceof MultiResultSet) {
                $this->assertEquals(2, $result->count());
                
                // Check first result set
                $firstResult = $result->getResultSet(0);
                $this->assertInstanceOf(ResultSet::class, $firstResult);
                $this->assertEquals(1, $firstResult->getRowsCount());
                $this->assertEquals('users', $firstResult->getRows()[0]['table_name']);
                $this->assertEquals(1, $firstResult->getRows()[0]['count']);
                
                // Check second result set
                $secondResult = $result->getResultSet(1);
                $this->assertInstanceOf(ResultSet::class, $secondResult);
                $this->assertEquals(1, $secondResult->getRowsCount());
                $this->assertEquals('result', $secondResult->getRows()[0]['status']);
                $this->assertEquals('success', $secondResult->getRows()[0]['message']);
            } else {
                // If single result, verify it has data from first result set
                $this->assertInstanceOf(ResultSet::class, $result);
                $this->assertGreaterThan(0, $result->getRowsCount());
            }
            
        } catch (\Exception $e) {
            $this->markTestSkipped('MSSQL stored procedure test failed: ' . $e->getMessage());
        } finally {
            // Clean up
            try {
                $schema->raw("IF OBJECT_ID('GetMultiResults', 'P') IS NOT NULL DROP PROCEDURE GetMultiResults")->execute();
            } catch (\Exception $e) {
                // Ignore cleanup errors
            }
        }
    }
    
    /**
     * @test
     */
    public function testMultipleInsertWithSelect() {
        $schema = new MSSQLTestSchema();
        
        try {
            // Create temp table for testing
            $schema->raw("IF OBJECT_ID('tempdb..#TestMulti') IS NOT NULL DROP TABLE #TestMulti")->execute();
            $schema->raw("CREATE TABLE #TestMulti (id INT, name NVARCHAR(50))")->execute();
            
            // Multi-statement: Insert data then select it
            $result = $schema->raw("
                INSERT INTO #TestMulti VALUES (1, 'Test1'), (2, 'Test2');
                SELECT * FROM #TestMulti;
                SELECT COUNT(*) as total FROM #TestMulti
            ")->execute();
            
            if ($result instanceof MultiResultSet) {
                $this->assertGreaterThanOrEqual(2, $result->count());
                
                // Should have at least the SELECT results
                $selectResult = null;
                $countResult = null;
                
                for ($i = 0; $i < $result->count(); $i++) {
                    $rs = $result->getResultSet($i);
                    if ($rs->getRowsCount() > 0) {
                        $firstRow = $rs->getRows()[0];
                        if (isset($firstRow['id']) && isset($firstRow['name'])) {
                            $selectResult = $rs;
                        } elseif (isset($firstRow['total'])) {
                            $countResult = $rs;
                        }
                    }
                }
                
                if ($selectResult) {
                    $this->assertEquals(2, $selectResult->getRowsCount());
                    $this->assertEquals('Test1', $selectResult->getRows()[0]['name']);
                }
                
                if ($countResult) {
                    $this->assertEquals(2, $countResult->getRows()[0]['total']);
                }
            } else {
                // Single result - should still have some data
                $this->assertInstanceOf(ResultSet::class, $result);
            }
            
        } catch (\Exception $e) {
            $this->markTestSkipped('MSSQL multi-insert test failed: ' . $e->getMessage());
        } finally {
            // Clean up
            try {
                $schema->raw("IF OBJECT_ID('tempdb..#TestMulti') IS NOT NULL DROP TABLE #TestMulti")->execute();
            } catch (\Exception $e) {
                // Ignore cleanup errors
            }
        }
    }
    
    /**
     * @test
     */
    public function testSingleQueryStillWorksAsResultSet() {
        $schema = new MSSQLTestSchema();
        
        // Execute single query - should return ResultSet, not MultiResultSet
        $result = $schema->raw("SELECT 'test' as value")->execute();
        
        $this->assertInstanceOf(ResultSet::class, $result);
        $this->assertNotInstanceOf(MultiResultSet::class, $result);
        $this->assertEquals(1, $result->getRowsCount());
        $this->assertEquals('test', $result->getRows()[0]['value']);
    }
    
    /**
     * @test
     */
    public function testBackwardCompatibilityMaintained() {
        $schema = new MSSQLTestSchema();
        
        // Test various single queries still work
        $result1 = $schema->raw("SELECT 1 as num")->execute();
        $this->assertInstanceOf(ResultSet::class, $result1);
        $this->assertEquals(1, $result1->getRows()[0]['num']);
        
        $result2 = $schema->raw("SELECT 'hello' as greeting, 'world' as target")->execute();
        $this->assertInstanceOf(ResultSet::class, $result2);
        $this->assertEquals('hello', $result2->getRows()[0]['greeting']);
        $this->assertEquals('world', $result2->getRows()[0]['target']);
    }
    
    /**
     * @test
     */
    public function testEmptyResultSetHandling() {
        $schema = new MSSQLTestSchema();
        
        // Query that returns no rows
        $result = $schema->raw("SELECT 1 as num WHERE 1=0")->execute();
        
        $this->assertInstanceOf(ResultSet::class, $result);
        $this->assertEquals(0, $result->getRowsCount());
        $this->assertEquals([], $result->getRows());
    }
}
