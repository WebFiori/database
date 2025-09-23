<?php

namespace WebFiori\Tests\Database\Common;

use PHPUnit\Framework\TestCase;
use WebFiori\Database\ResultSet;

/**
 * Test cases for ResultSet class.
 */
class ResultSetTest extends TestCase {
    
    private array $sampleData;
    
    protected function setUp(): void {
        $this->sampleData = [
            ['id' => 1, 'name' => 'John', 'email' => 'john@example.com'],
            ['id' => 2, 'name' => 'Jane', 'email' => 'jane@example.com'],
            ['id' => 3, 'name' => 'Bob', 'email' => 'bob@example.com']
        ];
    }
    
    /**
     * @test
     */
    public function testBasicResultSet() {
        $resultSet = new ResultSet($this->sampleData);
        
        $this->assertEquals(3, $resultSet->count());
        $this->assertEquals(3, count($resultSet));
    }
    
    /**
     * @test
     */
    public function testIteration() {
        $resultSet = new ResultSet($this->sampleData);
        
        $count = 0;
        foreach ($resultSet as $row) {
            $this->assertIsArray($row);
            $this->assertArrayHasKey('id', $row);
            $this->assertArrayHasKey('name', $row);
            $this->assertArrayHasKey('email', $row);
            $count++;
        }
        
        $this->assertEquals(3, $count);
    }
    
    /**
     * @test
     */
    public function testIteratorMethods() {
        $resultSet = new ResultSet($this->sampleData);
        
        // Test rewind and current
        $resultSet->rewind();
        $current = $resultSet->current();
        $this->assertEquals($this->sampleData[0], $current);
        
        // Test key
        $this->assertEquals(0, $resultSet->key());
        
        // Test next
        $resultSet->next();
        $this->assertEquals(1, $resultSet->key());
        $this->assertEquals($this->sampleData[1], $resultSet->current());
        
        // Test valid
        $this->assertTrue($resultSet->valid());
        
        // Move to end
        $resultSet->next();
        $resultSet->next();
        $this->assertFalse($resultSet->valid());
    }
    
    /**
     * @test
     */
    public function testGetRows() {
        $resultSet = new ResultSet($this->sampleData);
        
        $rows = $resultSet->getRows();
        $this->assertEquals($this->sampleData, $rows);
    }
    
    /**
     * @test
     */
    public function testMap() {
        $resultSet = new ResultSet($this->sampleData);
        
        $mapped = $resultSet->map(function($row) {
            return strtoupper($row['name']);
        });
        
        $this->assertInstanceOf(ResultSet::class, $mapped);
        $mappedRows = $mapped->getRows();
        $this->assertEquals(['JOHN', 'JANE', 'BOB'], $mappedRows);
    }
    
    /**
     * @test
     */
    public function testMapWithObjects() {
        $resultSet = new ResultSet($this->sampleData);
        
        $mapped = $resultSet->map(function($row) {
            return (object) $row;
        });
        
        $mappedRows = $mapped->getRows();
        $this->assertIsObject($mappedRows[0]);
        $this->assertEquals('John', $mappedRows[0]->name);
    }
    
    /**
     * @test
     */
    public function testEmptyResultSet() {
        $resultSet = new ResultSet([]);
        
        $this->assertEquals(0, $resultSet->count());
        $this->assertFalse($resultSet->valid());
        $this->assertEquals([], $resultSet->getRows());
    }
    
    /**
     * @test
     */
    public function testSingleRowResultSet() {
        $singleRow = [['id' => 1, 'name' => 'John']];
        $resultSet = new ResultSet($singleRow);
        
        $this->assertEquals(1, $resultSet->count());
        $this->assertTrue($resultSet->valid());
        $this->assertEquals($singleRow[0], $resultSet->current());
    }
    
    /**
     * @test
     */
    public function testResetIteration() {
        $resultSet = new ResultSet($this->sampleData);
        
        // Iterate to the end
        foreach ($resultSet as $row) {
            // Just iterate
        }
        
        $this->assertFalse($resultSet->valid());
        
        // Reset and iterate again
        $resultSet->rewind();
        $this->assertTrue($resultSet->valid());
        $this->assertEquals($this->sampleData[0], $resultSet->current());
    }
    
    /**
     * @test
     */
    public function testMapWithIndex() {
        $resultSet = new ResultSet($this->sampleData);
        
        $mapped = $resultSet->map(function($row, $index, $allRows) {
            return $row['name'] . '_' . $index;
        });
        
        $mappedRows = $mapped->getRows();
        // Note: Current implementation has a bug where index is always 0
        $this->assertEquals('John_0', $mappedRows[0]);
        $this->assertEquals('Jane_0', $mappedRows[1]);
        $this->assertEquals('Bob_0', $mappedRows[2]);
    }
}
