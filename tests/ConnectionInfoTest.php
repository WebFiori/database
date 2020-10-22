<?php

namespace webfiori\database\tests;
use PHPUnit\Framework\TestCase;
use webfiori\database\ConnectionInfo;
use webfiori\database\DatabaseException;
/**
 * Description of ConnectionInfoTest
 *
 * @author Ibrahim
 */
class ConnectionInfoTest extends TestCase {
    /**
     * @test
     */
    public function test00() {
        $conn = new ConnectionInfo('mysql', 'db-user', 'his-pass', 'some-db');
        $this->assertEquals('mysql', $conn->getDatabaseType());
        $this->assertEquals('db-user', $conn->getUsername());
        $this->assertEquals('his-pass', $conn->getPassword());
        $this->assertEquals('localhost', $conn->getHost());
        $this->assertEquals(3306, $conn->getPort());
        $this->assertEquals([], $conn->getExtars());
        $this->assertEquals('New_Connection', $conn->getName());
    }
    /**
     * @test
     */
    public function test01() {
        $this->expectException(DatabaseException::class);
        $this->expectExceptionMessage('Database not supported: "pg-sql".');
        $conn = new ConnectionInfo('pg-sql', 'db-user', 'his-pass', 'some-db');
    }
    /**
     * @test
     */
    public function test02() {
        $conn = new ConnectionInfo('mysql', 'db-user', 'his-pass', 'some-db', '192.233.190.4', 3307, [
            'connection-name' => 'My Connection'
        ]);
        $this->assertEquals('mysql', $conn->getDatabaseType());
        $this->assertEquals('db-user', $conn->getUsername());
        $this->assertEquals('his-pass', $conn->getPassword());
        $this->assertEquals('192.233.190.4', $conn->getHost());
        $this->assertEquals(3307, $conn->getPort());
        $this->assertEquals([
            'connection-name' => 'My Connection'
        ], $conn->getExtars());
        $this->assertEquals('My Connection', $conn->getName());
    }
}
