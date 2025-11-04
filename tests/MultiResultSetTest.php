<?php

use PHPUnit\Framework\TestCase;
use WebFiori\Database\MultiResultSet;
use WebFiori\Database\ResultSet;

/**
 * Test cases for MultiResultSet class.
 *
 * @author Ibrahim
 */
class MultiResultSetTest extends TestCase {
    
    /**
     * @test
     */
    public function testConstructorEmpty() {
        $multiResult = new MultiResultSet();
        $this->assertEquals(0, $multiResult->count());
        $this->assertEquals(0, $multiResult->getTotalRecordCount());
    }
    
    /**
     * @test
     */
    public function testConstructorWithData() {
        $data = [
            [['id' => 1, 'name' => 'John'], ['id' => 2, 'name' => 'Jane']],
            [['count' => 5]],
            [['status' => 'success']]
        ];
        
        $multiResult = new MultiResultSet($data);
        $this->assertEquals(3, $multiResult->count());
        $this->assertEquals(4, $multiResult->getTotalRecordCount()); // 2 + 1 + 1
    }
    
    /**
     * @test
     */
    public function testAddResultSet() {
        $multiResult = new MultiResultSet();
        $multiResult->addResultSet([['id' => 1, 'name' => 'Test']]);
        
        $this->assertEquals(1, $multiResult->count());
        $this->assertEquals(1, $multiResult->getTotalRecordCount());
    }
    
    /**
     * @test
     */
    public function testGetResultSet() {
        $data = [
            [['id' => 1, 'name' => 'John']],
            [['count' => 5]]
        ];
        
        $multiResult = new MultiResultSet($data);
        
        $firstResult = $multiResult->getResultSet(0);
        $this->assertInstanceOf(ResultSet::class, $firstResult);
        $this->assertEquals(1, $firstResult->getRowsCount());
        
        $secondResult = $multiResult->getResultSet(1);
        $this->assertInstanceOf(ResultSet::class, $secondResult);
        $this->assertEquals(1, $secondResult->getRowsCount());
        
        $invalidResult = $multiResult->getResultSet(5);
        $this->assertNull($invalidResult);
    }
    
    /**
     * @test
     */
    public function testGetResultSets() {
        $data = [
            [['id' => 1]],
            [['id' => 2]]
        ];
        
        $multiResult = new MultiResultSet($data);
        $resultSets = $multiResult->getResultSets();
        
        $this->assertIsArray($resultSets);
        $this->assertEquals(2, count($resultSets));
        $this->assertInstanceOf(ResultSet::class, $resultSets[0]);
        $this->assertInstanceOf(ResultSet::class, $resultSets[1]);
    }
    
    /**
     * @test
     */
    public function testIterator() {
        $data = [
            [['id' => 1, 'name' => 'First']],
            [['id' => 2, 'name' => 'Second']],
            [['id' => 3, 'name' => 'Third']]
        ];
        
        $multiResult = new MultiResultSet($data);
        
        $count = 0;
        foreach ($multiResult as $index => $resultSet) {
            $this->assertEquals($count, $index);
            $this->assertInstanceOf(ResultSet::class, $resultSet);
            $this->assertEquals(1, $resultSet->getRowsCount());
            $count++;
        }
        
        $this->assertEquals(3, $count);
    }
    
    /**
     * @test
     */
    public function testIteratorMethods() {
        $data = [
            [['id' => 1]],
            [['id' => 2]]
        ];
        
        $multiResult = new MultiResultSet($data);
        
        // Test rewind
        $multiResult->rewind();
        $this->assertEquals(0, $multiResult->key());
        $this->assertTrue($multiResult->valid());
        
        // Test current
        $current = $multiResult->current();
        $this->assertInstanceOf(ResultSet::class, $current);
        
        // Test next
        $multiResult->next();
        $this->assertEquals(1, $multiResult->key());
        $this->assertTrue($multiResult->valid());
        
        // Test beyond bounds
        $multiResult->next();
        $this->assertEquals(2, $multiResult->key());
        $this->assertFalse($multiResult->valid());
        $this->assertNull($multiResult->current());
    }
    
    /**
     * @test
     */
    public function testGetTotalRecordCount() {
        $data = [
            [['id' => 1], ['id' => 2], ['id' => 3]], // 3 records
            [['count' => 10]], // 1 record
            [], // 0 records
            [['status' => 'ok'], ['status' => 'error']] // 2 records
        ];
        
        $multiResult = new MultiResultSet($data);
        $this->assertEquals(6, $multiResult->getTotalRecordCount()); // 3 + 1 + 0 + 2
    }
    
    /**
     * @test
     */
    public function testConstructorWithMixedData() {
        $resultSet1 = new ResultSet([['id' => 1, 'name' => 'John']]);
        $resultSet2 = new ResultSet([['count' => 5]]);
        
        $data = [
            $resultSet1, // ResultSet object
            [['id' => 2, 'name' => 'Jane']], // Array data
            $resultSet2, // Another ResultSet object
            [['status' => 'success']] // More array data
        ];
        
        $multiResult = new MultiResultSet($data);
        $this->assertEquals(4, $multiResult->count());
        $this->assertEquals(4, $multiResult->getTotalRecordCount()); // 1 + 1 + 1 + 1
        
        // Verify first result set (was already ResultSet)
        $first = $multiResult->getResultSet(0);
        $this->assertInstanceOf(ResultSet::class, $first);
        $this->assertEquals(1, $first->getRowsCount());
        $this->assertEquals('John', $first->getRows()[0]['name']);
        
        // Verify second result set (was array, converted to ResultSet)
        $second = $multiResult->getResultSet(1);
        $this->assertInstanceOf(ResultSet::class, $second);
        $this->assertEquals(1, $second->getRowsCount());
        $this->assertEquals('Jane', $second->getRows()[0]['name']);
    }
    
    /**
     * @test
     */
    public function testAddResultSetWithMixedTypes() {
        $multiResult = new MultiResultSet();
        
        // Add array data
        $multiResult->addResultSet([['id' => 1, 'name' => 'Test']]);
        $this->assertEquals(1, $multiResult->count());
        
        // Add ResultSet object
        $resultSet = new ResultSet([['count' => 5], ['count' => 10]]);
        $multiResult->addResultSet($resultSet);
        $this->assertEquals(2, $multiResult->count());
        $this->assertEquals(3, $multiResult->getTotalRecordCount()); // 1 + 2
        
        // Verify the added ResultSet object
        $addedResultSet = $multiResult->getResultSet(1);
        $this->assertInstanceOf(ResultSet::class, $addedResultSet);
        $this->assertEquals(2, $addedResultSet->getRowsCount());
        $this->assertEquals(5, $addedResultSet->getRows()[0]['count']);
    }
    
    /**
     * @test
     */
    public function testCountable() {
        $multiResult = new MultiResultSet();
        $this->assertEquals(0, count($multiResult));
        
        $multiResult->addResultSet([['id' => 1]]);
        $this->assertEquals(1, count($multiResult));
        
        $multiResult->addResultSet([['id' => 2]]);
        $this->assertEquals(2, count($multiResult));
    }
}
