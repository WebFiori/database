<?php
namespace WebFiori\Tests\Database\Sqlite;

use PHPUnit\Framework\TestCase;
use WebFiori\Database\ColOption;
use WebFiori\Database\ConnectionInfo;
use WebFiori\Database\Database;
use WebFiori\Database\DatabaseException;
use WebFiori\Database\DataType;
use WebFiori\Database\Sqlite\SQLiteConnection;

class SQLiteConnectionTest extends TestCase {
    private Database $db;

    protected function setUp(): void {
        $conn = new ConnectionInfo('sqlite', '', '', ':memory:');
        $this->db = new Database($conn);
        $this->db->createBlueprint('users')->addColumns([
            'id' => [ColOption::TYPE => DataType::INT, ColOption::PRIMARY => true, ColOption::AUTO_INCREMENT => true],
            'name' => [ColOption::TYPE => DataType::VARCHAR, ColOption::SIZE => 100],
            'email' => [ColOption::TYPE => DataType::VARCHAR, ColOption::SIZE => 200, ColOption::UNIQUE => true],
            'age' => [ColOption::TYPE => DataType::INT],
            'score' => [ColOption::TYPE => DataType::DECIMAL, ColOption::NULL => true],
            'active' => [ColOption::TYPE => DataType::BOOL, ColOption::NULL => true],
        ]);
        $this->db->table('users')->createTable()->execute();
    }
    /**
     * @test
     */
    public function testInsertAndSelect() {
        $this->db->table('users')->insert(['name' => 'Ibrahim', 'email' => 'i@test.com', 'age' => 30, 'active' => true])->execute();
        $result = $this->db->table('users')->select()->execute();
        $this->assertEquals(1, $result->getCount());
        $row = $result->fetch();
        $this->assertEquals('Ibrahim', $row['name']);
        $this->assertEquals(30, $row['age']);
    }
    /**
     * @test
     */
    public function testInsertNullValue() {
        $this->db->table('users')->insert(['name' => 'Ali', 'email' => 'a@t.com', 'age' => 25, 'score' => null])->execute();
        $result = $this->db->table('users')->select()->where('name', 'Ali')->execute();
        $this->assertNull($result->fetch()['score']);
    }
    /**
     * @test
     */
    public function testInsertBoolean() {
        $this->db->table('users')->insert(['name' => 'X', 'email' => 'x@t.com', 'age' => 20, 'active' => false])->execute();
        $result = $this->db->table('users')->select()->where('active', 0)->execute();
        $this->assertEquals(1, $result->getCount());
    }
    /**
     * @test
     */
    public function testInsertFloat() {
        $this->db->table('users')->insert(['name' => 'Y', 'email' => 'y@t.com', 'age' => 22, 'score' => 9.5])->execute();
        $result = $this->db->table('users')->select()->where('name', 'Y')->execute();
        $this->assertEquals(9.5, $result->fetch()['score']);
    }
    /**
     * @test
     */
    public function testSelectWithWhere() {
        $this->db->table('users')->insert(['name' => 'A', 'email' => 'a@t.com', 'age' => 20])->execute();
        $this->db->table('users')->insert(['name' => 'B', 'email' => 'b@t.com', 'age' => 30])->execute();
        $result = $this->db->table('users')->select()->where('age', 30)->execute();
        $this->assertEquals(1, $result->getCount());
        $this->assertEquals('B', $result->fetch()['name']);
    }
    /**
     * @test
     */
    public function testSelectWhereGreaterThan() {
        $this->db->table('users')->insert(['name' => 'A', 'email' => 'a@t.com', 'age' => 20])->execute();
        $this->db->table('users')->insert(['name' => 'B', 'email' => 'b@t.com', 'age' => 30])->execute();
        $result = $this->db->table('users')->select()->where('age', 25, '>')->execute();
        $this->assertEquals(1, $result->getCount());
    }
    /**
     * @test
     */
    public function testSelectWhereIn() {
        $this->db->table('users')->insert(['name' => 'A', 'email' => 'a@t.com', 'age' => 20])->execute();
        $this->db->table('users')->insert(['name' => 'B', 'email' => 'b@t.com', 'age' => 30])->execute();
        $this->db->table('users')->insert(['name' => 'C', 'email' => 'c@t.com', 'age' => 40])->execute();
        $result = $this->db->table('users')->select()->whereIn('age', [20, 40])->execute();
        $this->assertEquals(2, $result->getCount());
    }
    /**
     * @test
     */
    public function testSelectWhereBetween() {
        $this->db->table('users')->insert(['name' => 'A', 'email' => 'a@t.com', 'age' => 20])->execute();
        $this->db->table('users')->insert(['name' => 'B', 'email' => 'b@t.com', 'age' => 30])->execute();
        $this->db->table('users')->insert(['name' => 'C', 'email' => 'c@t.com', 'age' => 40])->execute();
        $result = $this->db->table('users')->select()->whereBetween('age', 25, 35)->execute();
        $this->assertEquals(1, $result->getCount());
    }
    /**
     * @test
     */
    public function testSelectWhereLike() {
        $this->db->table('users')->insert(['name' => 'Ibrahim', 'email' => 'i@t.com', 'age' => 30])->execute();
        $this->db->table('users')->insert(['name' => 'Ali', 'email' => 'a@t.com', 'age' => 25])->execute();
        $result = $this->db->table('users')->select()->whereLike('name', 'Ibr%')->execute();
        $this->assertEquals(1, $result->getCount());
    }
    /**
     * @test
     */
    public function testSelectWhereNull() {
        $this->db->table('users')->insert(['name' => 'A', 'email' => 'a@t.com', 'age' => 20, 'score' => null])->execute();
        $this->db->table('users')->insert(['name' => 'B', 'email' => 'b@t.com', 'age' => 30, 'score' => 5.0])->execute();
        $result = $this->db->table('users')->select()->whereNull('score')->execute();
        $this->assertEquals(1, $result->getCount());
    }
    /**
     * @test
     */
    public function testSelectCount() {
        $this->db->table('users')->insert(['name' => 'A', 'email' => 'a@t.com', 'age' => 20])->execute();
        $this->db->table('users')->insert(['name' => 'B', 'email' => 'b@t.com', 'age' => 30])->execute();
        $result = $this->db->table('users')->selectCount()->execute();
        $this->assertEquals(2, (int) $result->fetch()['count']);
    }
    /**
     * @test
     */
    public function testSelectMax() {
        $this->db->table('users')->insert(['name' => 'A', 'email' => 'a@t.com', 'age' => 20])->execute();
        $this->db->table('users')->insert(['name' => 'B', 'email' => 'b@t.com', 'age' => 30])->execute();
        $result = $this->db->table('users')->selectMax('age')->execute();
        $this->assertEquals(30, (int) $result->fetch()['max']);
    }
    /**
     * @test
     */
    public function testSelectWithLimitOffset() {
        $this->db->table('users')->insert(['name' => 'A', 'email' => 'a@t.com', 'age' => 20])->execute();
        $this->db->table('users')->insert(['name' => 'B', 'email' => 'b@t.com', 'age' => 30])->execute();
        $this->db->table('users')->insert(['name' => 'C', 'email' => 'c@t.com', 'age' => 40])->execute();
        $result = $this->db->table('users')->select()->limit(2)->offset(1)->execute();
        $this->assertEquals(2, $result->getCount());
    }
    /**
     * @test
     */
    public function testUpdate() {
        $this->db->table('users')->insert(['name' => 'Old', 'email' => 'o@t.com', 'age' => 20])->execute();
        $this->db->table('users')->update(['name' => 'New'])->where('email', 'o@t.com')->execute();
        $result = $this->db->table('users')->select()->where('email', 'o@t.com')->execute();
        $this->assertEquals('New', $result->fetch()['name']);
    }
    /**
     * @test
     */
    public function testUpdateNull() {
        $this->db->table('users')->insert(['name' => 'X', 'email' => 'x@t.com', 'age' => 20, 'score' => 5.0])->execute();
        $this->db->table('users')->update(['score' => null])->where('email', 'x@t.com')->execute();
        $result = $this->db->table('users')->select()->where('email', 'x@t.com')->execute();
        $this->assertNull($result->fetch()['score']);
    }
    /**
     * @test
     */
    public function testDelete() {
        $this->db->table('users')->insert(['name' => 'A', 'email' => 'a@t.com', 'age' => 20])->execute();
        $this->db->table('users')->insert(['name' => 'B', 'email' => 'b@t.com', 'age' => 30])->execute();
        $this->db->table('users')->delete()->where('name', 'A')->execute();
        $result = $this->db->table('users')->select()->execute();
        $this->assertEquals(1, $result->getCount());
    }
    /**
     * @test
     */
    public function testTransaction() {
        $this->db->transaction(function (Database $db) {
            $db->table('users')->insert(['name' => 'Tx', 'email' => 'tx@t.com', 'age' => 50])->execute();
        });
        $result = $this->db->table('users')->select()->where('name', 'Tx')->execute();
        $this->assertEquals(1, $result->getCount());
    }
    /**
     * @test
     */
    public function testUniqueConstraintViolation() {
        $this->expectException(DatabaseException::class);
        $this->db->table('users')->insert(['name' => 'A', 'email' => 'dup@t.com', 'age' => 20])->execute();
        $this->db->table('users')->insert(['name' => 'B', 'email' => 'dup@t.com', 'age' => 30])->execute();
    }
    /**
     * @test
     */
    public function testConnectionIsAlive() {
        $conn = new ConnectionInfo('sqlite', '', '', ':memory:');
        $connection = new SQLiteConnection($conn);
        $this->assertTrue($connection->isAlive());
        $this->assertNotNull($connection->getLink());
    }
    /**
     * @test
     */
    public function testConnectionClose() {
        $conn = new ConnectionInfo('sqlite', '', '', ':memory:');
        $connection = new SQLiteConnection($conn);
        $this->assertTrue($connection->isAlive());
        $connection->close();
        $this->assertFalse($connection->isAlive());
        $this->assertNull($connection->getLink());
        // Double close should not error
        $connection->close();
    }
    /**
     * @test
     */
    public function testGetLastInsertId() {
        $this->db->table('users')->insert(['name' => 'Z', 'email' => 'z@t.com', 'age' => 99])->execute();
        $conn = $this->db->getConnection();
        $this->assertGreaterThan(0, $conn->getLastInsertId());
    }
    /**
     * @test
     */
    public function testGetLastInsertIdNoConnection() {
        $conn = new ConnectionInfo('sqlite', '', '', ':memory:');
        $connection = new SQLiteConnection($conn);
        $connection->close();
        $this->assertEquals(0, $connection->getLastInsertId());
    }
    /**
     * @test
     */
    public function testRunQueryWithoutQuery() {
        $conn = new ConnectionInfo('sqlite', '', '', ':memory:');
        $connection = new SQLiteConnection($conn);
        $this->assertFalse($connection->runQuery(null));
    }
    /**
     * @test
     */
    public function testDirectQueryExec() {
        $this->db->raw('CREATE TABLE IF NOT EXISTS temp_t (id INTEGER)')->execute();
        $this->db->raw('DROP TABLE temp_t')->execute();
        $this->assertTrue(true);
    }
    /**
     * @test
     */
    public function testRollback() {
        $conn = $this->db->getConnection();
        $conn->beginTransaction();
        $this->db->table('users')->insert(['name' => 'RB', 'email' => 'rb@t.com', 'age' => 10])->execute();
        $conn->rollBack();
        $result = $this->db->table('users')->select()->execute();
        $this->assertEquals(0, $result->getCount());
    }
    /**
     * @test
     */
    public function testConnectionFailure() {
        $this->expectException(\Exception::class);
        new SQLiteConnection(new ConnectionInfo('sqlite', '', '', '/nonexistent/path/db.sqlite'));
    }
}
