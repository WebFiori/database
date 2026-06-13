<?php

namespace WebFiori\Tests\Database\MySql;

use PHPUnit\Framework\TestCase;
use WebFiori\Database\ColOption;
use WebFiori\Database\ConnectionInfo;
use WebFiori\Database\Database;
use WebFiori\Database\DataType;
use WebFiori\Database\MySql\MySQLConnection;

class MySQLLastInsertIdTest extends TestCase {
    /**
     * @test
     */
    public function testGetLastInsertIdAfterInsert() {
        $connInfo = new ConnectionInfo('mysql', 'root', getenv('MYSQL_ROOT_PASSWORD'), 'testing_db', '127.0.0.1');
        $db = new Database($connInfo);

        $db->createBlueprint('last_id_test')->addColumns([
            'id' => [ColOption::TYPE => DataType::INT, ColOption::PRIMARY => true, ColOption::AUTO_INCREMENT => true],
            'name' => [ColOption::TYPE => DataType::VARCHAR, ColOption::SIZE => 50],
        ]);
        $db->table('last_id_test')->drop(true)->execute();
        $db->table('last_id_test')->createTable()->execute();

        $db->table('last_id_test')->insert(['name' => 'first'])->execute();
        $id1 = $db->getLastInsertId();
        $this->assertGreaterThan(0, $id1);

        $db->table('last_id_test')->insert(['name' => 'second'])->execute();
        $id2 = $db->getLastInsertId();
        $this->assertGreaterThan($id1, $id2);

        $db->table('last_id_test')->drop()->execute();
        $db->close();
        \WebFiori\Database\ConnectionPool::reset();
    }

    /**
     * @test
     */
    public function testGetLastInsertIdClosedConnection() {
        $connInfo = new ConnectionInfo('mysql', 'root', getenv('MYSQL_ROOT_PASSWORD'), 'testing_db', '127.0.0.1');
        $conn = new MySQLConnection($connInfo);
        $conn->close();
        $this->assertEquals(0, $conn->getLastInsertId());
    }
}
