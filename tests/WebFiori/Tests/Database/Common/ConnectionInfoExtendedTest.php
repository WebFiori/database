<?php

namespace WebFiori\Tests\Database\Common;

use PHPUnit\Framework\TestCase;
use WebFiori\Database\ConnectionInfo;
use WebFiori\Database\DatabaseException;

/**
 * Extended test cases for ConnectionInfo class to improve coverage.
 */
class ConnectionInfoExtendedTest extends TestCase {
    
    /**
     * @test
     */
    public function testMSSQLConnection() {
        $conn = new ConnectionInfo('mssql', 'sa', 'password', 'testdb', 'localhost', 1433);
        $this->assertEquals('mssql', $conn->getDatabaseType());
        $this->assertEquals('sa', $conn->getUsername());
        $this->assertEquals('password', $conn->getPassword());
        $this->assertEquals('localhost', $conn->getHost());
        $this->assertEquals(1433, $conn->getPort());
    }
    
    /**
     * @test
     */
    public function testSettersAndGetters() {
        $conn = new ConnectionInfo('mysql', 'user', 'pass', 'db');
        
        // Test setters
        $conn->setHost('newhost');
        $this->assertEquals('newhost', $conn->getHost());
        
        $conn->setPort(3308);
        $this->assertEquals(3308, $conn->getPort());
        
        $conn->setUsername('newuser');
        $this->assertEquals('newuser', $conn->getUsername());
        
        $conn->setPassword('newpass');
        $this->assertEquals('newpass', $conn->getPassword());
        
        $conn->setDBName('newdb');
        $this->assertEquals('newdb', $conn->getDBName());
    }
    
    /**
     * @test
     */
    public function testExtrasHandling() {
        // Test connection name from extras during construction
        $conn = new ConnectionInfo('mysql', 'user', 'pass', 'db', 'localhost', 3306, [
            'connection-name' => 'Test Connection',
            'charset' => 'utf8mb4',
            'timeout' => 30
        ]);
        
        $extras = $conn->getExtars();
        $this->assertEquals('utf8mb4', $extras['charset']);
        $this->assertEquals(30, $extras['timeout']);
        $this->assertEquals('Test Connection', $conn->getName());
        
        // Test setting extras after construction
        $conn->setExtras(['new-option' => 'value']);
        $newExtras = $conn->getExtars();
        $this->assertEquals('value', $newExtras['new-option']);
    }
    
    /**
     * @test
     */
    public function testDefaultValues() {
        $conn = new ConnectionInfo('mysql', 'user', 'pass', 'db');
        
        // Test default values
        $this->assertEquals('localhost', $conn->getHost());
        $this->assertEquals(3306, $conn->getPort());
        $this->assertEquals('New_Connection', $conn->getName());
        $this->assertEquals([], $conn->getExtars());
    }
    
    /**
     * @test
     */
    public function testSupportedDatabases() {
        $supported = ConnectionInfo::SUPPORTED_DATABASES;
        $this->assertContains('mysql', $supported);
        $this->assertContains('mssql', $supported);
        $this->assertCount(2, $supported);
    }
}
