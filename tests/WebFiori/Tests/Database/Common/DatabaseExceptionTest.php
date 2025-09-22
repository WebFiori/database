<?php

namespace WebFiori\Tests\Database\Common;

use PHPUnit\Framework\TestCase;
use WebFiori\Database\DatabaseException;

/**
 * Test cases for DatabaseException class.
 */
class DatabaseExceptionTest extends TestCase {
    
    /**
     * @test
     */
    public function testBasicException() {
        $exception = new DatabaseException('Test error message');
        
        $this->assertEquals('Test error message', $exception->getMessage());
        $this->assertEquals(0, $exception->getCode());
        $this->assertEquals('', $exception->getSQLQuery());
    }
    
    /**
     * @test
     */
    public function testExceptionWithCode() {
        $exception = new DatabaseException('Database error', 1045);
        
        $this->assertEquals('Database error', $exception->getMessage());
        $this->assertEquals(1045, $exception->getCode());
        $this->assertEquals('', $exception->getSQLQuery());
    }
    
    /**
     * @test
     */
    public function testExceptionWithQuery() {
        $query = 'SELECT * FROM users WHERE id = ?';
        $exception = new DatabaseException('Query failed', 1064, $query);
        
        $this->assertEquals('Query failed', $exception->getMessage());
        $this->assertEquals(1064, $exception->getCode());
        $this->assertEquals($query, $exception->getSQLQuery());
    }
    
    /**
     * @test
     */
    public function testExceptionWithPrevious() {
        $previous = new \Exception('Previous exception');
        $exception = new DatabaseException('Database error', 0, '', $previous);
        
        $this->assertEquals('Database error', $exception->getMessage());
        $this->assertEquals(0, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
        $this->assertEquals('', $exception->getSQLQuery());
    }
    
    /**
     * @test
     */
    public function testExceptionWithAllParameters() {
        $query = 'INSERT INTO users (name) VALUES (?)';
        $previous = new \Exception('Connection lost');
        $exception = new DatabaseException('Insert failed', 2006, $query, $previous);
        
        $this->assertEquals('Insert failed', $exception->getMessage());
        $this->assertEquals(2006, $exception->getCode());
        $this->assertEquals($query, $exception->getSQLQuery());
        $this->assertSame($previous, $exception->getPrevious());
    }
}
