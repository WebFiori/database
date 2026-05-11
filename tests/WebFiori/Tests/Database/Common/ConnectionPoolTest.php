<?php

namespace WebFiori\Tests\Database;

use PHPUnit\Framework\TestCase;
use WebFiori\Database\ConnectionInfo;
use WebFiori\Database\ConnectionPool;

/**
 * Test cases for ConnectionPool.
 * 
 * @author Ibrahim
 */
class ConnectionPoolTest extends TestCase {

    protected function setUp(): void {
        parent::setUp();
        ConnectionPool::reset();
    }

    protected function tearDown(): void {
        ConnectionPool::reset();
        parent::tearDown();
    }

    /**
     * @test
     */
    public function testGetInstance() {
        $pool = ConnectionPool::getInstance();
        $this->assertInstanceOf(ConnectionPool::class, $pool);
        $this->assertSame($pool, ConnectionPool::getInstance());
    }

    /**
     * @test
     */
    public function testAcquireCreatesConnection() {
        $pool = ConnectionPool::getInstance();
        $info = $this->createMySQLConnectionInfo();

        $conn = $pool->acquire($info);

        $this->assertNotNull($conn);
        $this->assertTrue($conn->isAlive());
        $this->assertEquals(1, $pool->getActiveCount());
        $this->assertEquals(0, $pool->getIdleCount());
    }

    /**
     * @test
     */
    public function testReleaseMovesToIdle() {
        $pool = ConnectionPool::getInstance();
        $info = $this->createMySQLConnectionInfo();

        $conn = $pool->acquire($info);
        $pool->release($conn);

        $this->assertEquals(0, $pool->getActiveCount());
        $this->assertEquals(1, $pool->getIdleCount());
    }

    /**
     * @test
     */
    public function testAcquireReusesIdleConnection() {
        $pool = ConnectionPool::getInstance();
        $info = $this->createMySQLConnectionInfo();

        $conn1 = $pool->acquire($info);
        $pool->release($conn1);

        $conn2 = $pool->acquire($info);

        $this->assertSame($conn1, $conn2);
        $this->assertEquals(1, $pool->getActiveCount());
        $this->assertEquals(0, $pool->getIdleCount());
    }

    /**
     * @test
     */
    public function testMultipleAcquiresCreateSeparateConnections() {
        $pool = ConnectionPool::getInstance();
        $info = $this->createMySQLConnectionInfo();

        $conn1 = $pool->acquire($info);
        $conn2 = $pool->acquire($info);

        $this->assertNotSame($conn1, $conn2);
        $this->assertEquals(2, $pool->getActiveCount());
    }

    /**
     * @test
     */
    public function testCloseAllDrainsPool() {
        $pool = ConnectionPool::getInstance();
        $info = $this->createMySQLConnectionInfo();

        $conn1 = $pool->acquire($info);
        $conn2 = $pool->acquire($info);
        $pool->release($conn1);

        $pool->closeAll();

        $this->assertEquals(0, $pool->getActiveCount());
        $this->assertEquals(0, $pool->getIdleCount());
    }

    /**
     * @test
     */
    public function testMaxTotalIsAdvisoryNotHardLimit() {
        $pool = ConnectionPool::getInstance();
        $pool->setMaxTotal(2);
        $info = $this->createMySQLConnectionInfo();

        $conn1 = $pool->acquire($info);
        $conn2 = $pool->acquire($info);
        $conn3 = $pool->acquire($info);

        // All connections created — no exception thrown
        $this->assertNotNull($conn3);
        $this->assertTrue($conn3->isAlive());
        $this->assertEquals(3, $pool->getActiveCount());
    }

    /**
     * @test
     */
    public function testMaxPerKeyLimitsIdleConnections() {
        $pool = ConnectionPool::getInstance();
        $pool->setMaxPerKey(1);
        $info = $this->createMySQLConnectionInfo();

        $conn1 = $pool->acquire($info);
        $conn2 = $pool->acquire($info);

        $pool->release($conn1);
        $pool->release($conn2);

        // Only 1 should be kept idle (maxPerKey = 1), the other closed
        $this->assertEquals(1, $pool->getIdleCount());
    }

    /**
     * @test
     */
    public function testReset() {
        $pool = ConnectionPool::getInstance();
        $info = $this->createMySQLConnectionInfo();

        $pool->acquire($info);
        ConnectionPool::reset();

        $newPool = ConnectionPool::getInstance();
        $this->assertEquals(0, $newPool->getActiveCount());
        $this->assertEquals(0, $newPool->getIdleCount());
    }

    /**
     * @test
     */
    public function testAcquireAfterReleaseReusesSameConnection() {
        $pool = ConnectionPool::getInstance();
        $info = $this->createMySQLConnectionInfo();

        $conn1 = $pool->acquire($info);
        $pool->release($conn1);

        // Same params after release should reuse
        $conn2 = $pool->acquire($info);
        $this->assertSame($conn1, $conn2);
        $this->assertEquals(1, $pool->getActiveCount());
    }

    /**
     * @test
     */
    public function testDefaultMaxPerKey() {
        $pool = ConnectionPool::getInstance();
        $this->assertEquals(10, $pool->getMaxPerKey());
        $pool->setMaxPerKey(3);
        $this->assertEquals(3, $pool->getMaxPerKey());
    }

    /**
     * @test
     */
    public function testDefaultMaxTotal() {
        $pool = ConnectionPool::getInstance();
        $this->assertEquals(100, $pool->getMaxTotal());
        $pool->setMaxTotal(10);
        $this->assertEquals(10, $pool->getMaxTotal());
    }

    /**
     * @test
     */
    public function testClosedConnectionNotReused() {
        $pool = ConnectionPool::getInstance();
        $info = $this->createMySQLConnectionInfo();

        $conn = $pool->acquire($info);
        $conn->close(); // Manually close the underlying link
        $pool->release($conn);

        // Pool should detect dead connection and not reuse it
        $conn2 = $pool->acquire($info);
        $this->assertNotSame($conn, $conn2);
        $this->assertTrue($conn2->isAlive());
    }

    private function createMySQLConnectionInfo(): ConnectionInfo {
        return new ConnectionInfo(
            'mysql',
            'root',
            getenv('MYSQL_ROOT_PASSWORD'),
            'testing_db',
            '127.0.0.1',
            3306
        );
    }
}
