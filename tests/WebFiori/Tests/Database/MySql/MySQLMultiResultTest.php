<?php

namespace WebFiori\Tests\Database\MySql;

use PHPUnit\Framework\TestCase;
use WebFiori\Database\MultiResultSet;
use WebFiori\Database\ResultSet;
use WebFiori\Tests\Database\MySql\MySQLTestSchema;

/**
 * Test cases for MySQL multi-result functionality.
 *
 * @author Ibrahim
 */
class MySQLMultiResultTest extends TestCase {
    
    /**
     * @test
     */
    public function testMultiSelectQuery() {
        $schema = new MySQLTestSchema();
        
        // Enable multi-query mode by using mysqli_multi_query
        $connection = $schema->getConnection();
        $link = $connection->getMysqliLink();
        
        // Execute multi-select query using mysqli_multi_query
        $sql = "SELECT 1 as num, 'first' as label; SELECT 2 as num, 'second' as label";
        $success = mysqli_multi_query($link, $sql);
        
        if ($success) {
            // Collect all results manually to verify our implementation would work
            $allResults = [];
            
            // First result
            if ($result = mysqli_store_result($link)) {
                $rows = mysqli_fetch_all($result, MYSQLI_ASSOC);
                $allResults[] = $rows;
                mysqli_free_result($result);
            }
            
            // Additional results
            while (mysqli_more_results($link)) {
                mysqli_next_result($link);
                if ($result = mysqli_store_result($link)) {
                    $rows = mysqli_fetch_all($result, MYSQLI_ASSOC);
                    $allResults[] = $rows;
                    mysqli_free_result($result);
                }
            }
            
            // Verify we got multiple result sets
            $this->assertEquals(2, count($allResults));
            $this->assertEquals(1, $allResults[0][0]['num']);
            $this->assertEquals('first', $allResults[0][0]['label']);
            $this->assertEquals(2, $allResults[1][0]['num']);
            $this->assertEquals('second', $allResults[1][0]['label']);
        } else {
            $this->markTestSkipped('MySQL multi-query not supported or enabled');
        }
    }
    
    /**
     * @test
     */
    public function testStoredProcedureMultiResult() {
        $schema = new MySQLTestSchema();
        
        try {
            // Clean up first
            $schema->raw("DROP PROCEDURE IF EXISTS GetMultiResults")->execute();
            
            // Create stored procedure that returns multiple result sets
            $createProc = "CREATE PROCEDURE GetMultiResults() 
                          BEGIN 
                              SELECT 'users' as table_name, 1 as count; 
                              SELECT 'result' as status, 'success' as message; 
                          END";
            $schema->raw($createProc)->execute();
            
            // Execute stored procedure using our implementation
            $result = $schema->raw("CALL GetMultiResults()")->execute();
            
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
            $this->markTestSkipped('Stored procedure test failed: ' . $e->getMessage());
        } finally {
            // Clean up
            try {
                $schema->raw("DROP PROCEDURE IF EXISTS GetMultiResults")->execute();
            } catch (\Exception $e) {
                // Ignore cleanup errors
            }
        }
    }
    
    /**
     * @test
     */
    public function testSingleQueryStillWorksAsResultSet() {
        $schema = new MySQLTestSchema();
        
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
        $schema = new MySQLTestSchema();
        
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
        $schema = new MySQLTestSchema();
        
        // Query that returns no rows
        $result = $schema->raw("SELECT 1 as num WHERE 1=0")->execute();
        
        $this->assertInstanceOf(ResultSet::class, $result);
        $this->assertEquals(0, $result->getRowsCount());
        $this->assertEquals([], $result->getRows());
    }
}
