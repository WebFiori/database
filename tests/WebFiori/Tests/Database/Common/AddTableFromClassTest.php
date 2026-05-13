<?php
namespace WebFiori\Tests\Database\Common;

use PHPUnit\Framework\TestCase;
use WebFiori\Database\Attributes\Column;
use WebFiori\Database\Attributes\InvalidAttributeException;
use WebFiori\Database\Attributes\Table;
use WebFiori\Database\ColOption;
use WebFiori\Database\ConnectionInfo;
use WebFiori\Database\Database;
use WebFiori\Database\DatabaseException;
use WebFiori\Database\DataType;
use WebFiori\Database\MsSql\MSSQLTable;
use WebFiori\Database\MySql\MySQLTable;

// --- Test Fixtures ---

#[Table(name: 'attr_users')]
class AttrUserFixture {
    #[Column(type: DataType::VARCHAR, size: 150)]
    public string $email;
    #[Column(type: DataType::INT, primary: true, autoIncrement: true)]
    public int $id;

    #[Column(type: DataType::VARCHAR, size: 100)]
    public string $name;
}

#[Table(name: 'attr_posts')]
class AttrPostFixture {
    #[Column(type: DataType::INT, primary: true, autoIncrement: true)]
    public int $id;

    #[Column(type: DataType::VARCHAR, size: 200)]
    public string $title;
}

#[Table(name: 'attr_empty')]
class AttrEmptyFixture {
}

class PlainClassFixture {
    public string $name;
}

class MySQLSubclassFixture extends MySQLTable {
    public function __construct() {
        parent::__construct('mysql_sub_table');
        $this->addColumns([
            'id' => [
                ColOption::TYPE => DataType::INT,
                ColOption::PRIMARY => true,
                ColOption::AUTO_INCREMENT => true
            ],
            'title' => [
                ColOption::TYPE => DataType::VARCHAR,
                ColOption::SIZE => 100
            ]
        ]);
    }
}

class MSSQLSubclassFixture extends MSSQLTable {
    public function __construct() {
        parent::__construct('mssql_sub_table');
        $this->addColumns([
            'id' => [
                ColOption::TYPE => DataType::INT,
                ColOption::PRIMARY => true,
                ColOption::IDENTITY => true
            ],
            'title' => [
                ColOption::TYPE => DataType::VARCHAR,
                ColOption::SIZE => 100
            ]
        ]);
    }
}

class RequiredArgTableFixture extends MySQLTable {
    public function __construct(string $requiredArg) {
        parent::__construct($requiredArg);
    }
}

#[Table(name: 'attr_with_parent')]
class AttrAndSubclassFixture extends MySQLTable {
    #[Column(type: DataType::INT, primary: true)]
    public int $id;

    public function __construct() {
        parent::__construct('subclass_wins');
        $this->addColumns([
            'id' => [
                ColOption::TYPE => DataType::INT,
                ColOption::PRIMARY => true,
            ],
            'name' => [
                ColOption::TYPE => DataType::VARCHAR,
                ColOption::SIZE => 50
            ]
        ]);
    }
}

// --- Tests ---

class AddTableFromClassTest extends TestCase {
    // Test 12: Abstract class throws
    public function testAbstractClassThrows() {
        $this->expectException(\Throwable::class);
        $db = $this->createMySQLDatabase();
        $db->addTableFromClass(\WebFiori\Database\Table::class);
    }

    // Test 1: Attribute-based class registers correctly
    public function testAddAttributeBasedClass() {
        $db = $this->createMySQLDatabase();
        $table = $db->addTableFromClass(AttrUserFixture::class);

        $this->assertNotNull($table);
        $this->assertEquals('attr_users', $table->getNormalName());
        $this->assertTrue($db->hasTable('attr_users'));
    }

    // Test 3: Bulk registration
    public function testAddTablesFromClasses() {
        $db = $this->createMySQLDatabase();
        $tables = $db->addTablesFromClasses([
            AttrUserFixture::class,
            AttrPostFixture::class,
            MySQLSubclassFixture::class
        ]);

        $this->assertCount(3, $tables);
        $this->assertTrue($db->hasTable('attr_users'));
        $this->assertTrue($db->hasTable('attr_posts'));
        $this->assertTrue($db->hasTable('mysql_sub_table'));
    }

    // Test 2: Table subclass registers correctly
    public function testAddTableSubclass() {
        $db = $this->createMySQLDatabase();
        $table = $db->addTableFromClass(MySQLSubclassFixture::class);

        $this->assertNotNull($table);
        $this->assertEquals('mysql_sub_table', $table->getNormalName());
        $this->assertTrue($db->hasTable('mysql_sub_table'));
    }

    // Test 7: Attribute class with no columns
    public function testAttributeClassNoColumns() {
        $db = $this->createMySQLDatabase();
        $table = $db->addTableFromClass(AttrEmptyFixture::class);

        $this->assertNotNull($table);
        $this->assertEquals('attr_empty', $table->getNormalName());
        $this->assertEquals(0, $table->getColsCount());
    }

    // Test 10: DB type propagation - attribute class on MSSQL
    public function testAttributeClassOnMSSQL() {
        $db = $this->createMSSQLDatabase();
        $table = $db->addTableFromClass(AttrUserFixture::class);

        $this->assertInstanceOf(MSSQLTable::class, $table);
    }

    // Test 18: Partial failure in bulk - fail-fast
    public function testBulkPartialFailure() {
        $this->expectException(InvalidAttributeException::class);
        $db = $this->createMySQLDatabase();
        $db->addTablesFromClasses([
            AttrUserFixture::class,
            PlainClassFixture::class, // This will fail
            AttrPostFixture::class
        ]);
    }

    // Verify first table was registered before failure
    public function testBulkPartialFailureFirstRegistered() {
        $db = $this->createMySQLDatabase();

        try {
            $db->addTablesFromClasses([
                AttrUserFixture::class,
                PlainClassFixture::class,
                AttrPostFixture::class
            ]);
        } catch (\Throwable $e) {
            // Expected
        }

        $this->assertTrue($db->hasTable('attr_users'));
        $this->assertFalse($db->hasTable('attr_posts'));
    }

    // Test 8: Duplicate registration returns false on second addTable
    public function testDuplicateRegistration() {
        $db = $this->createMySQLDatabase();
        $table1 = $db->addTableFromClass(AttrUserFixture::class);
        $table2 = $db->addTableFromClass(AttrUserFixture::class);

        // addTable returns false for duplicates, but addTableFromClass still returns the built table
        // The table should only exist once
        $this->assertNotNull($table1);
        $this->assertNotNull($table2);
        $this->assertTrue($db->hasTable('attr_users'));
    }

    // Test 15: MSSQLTable subclass on MySQL connection gets converted
    public function testMSSQLSubclassOnMySQLConnection() {
        $db = $this->createMySQLDatabase();
        $table = $db->addTableFromClass(MSSQLSubclassFixture::class);

        $this->assertInstanceOf(MySQLTable::class, $table);
        $this->assertEquals('mssql_sub_table', $table->getNormalName());
        $this->assertTrue($table->hasColumn('id'));
        $this->assertTrue($table->hasColumn('title'));
    }

    // Test 14: MySQLTable subclass on MSSQL connection gets converted
    public function testMySQLSubclassOnMSSQLConnection() {
        $db = $this->createMSSQLDatabase();
        $table = $db->addTableFromClass(MySQLSubclassFixture::class);

        $this->assertInstanceOf(MSSQLTable::class, $table);
        $this->assertEquals('mysql_sub_table', $table->getNormalName());
        $this->assertTrue($table->hasColumn('id'));
        $this->assertTrue($table->hasColumn('title'));
    }

    // Test 6: Non-existent class throws
    public function testNonExistentClassThrows() {
        $this->expectException(\Throwable::class);
        $db = $this->createMySQLDatabase();
        $db->addTableFromClass('App\\NonExistent\\Ghost');
    }

    // Test 5: Plain class without #[Table] throws
    public function testPlainClassThrows() {
        $this->expectException(InvalidAttributeException::class);
        $db = $this->createMySQLDatabase();
        $db->addTableFromClass(PlainClassFixture::class);
    }

    // Test 4: Return value has correct columns
    public function testReturnValueCorrectness() {
        $db = $this->createMySQLDatabase();
        $table = $db->addTableFromClass(AttrUserFixture::class);

        $this->assertEquals(3, $table->getColsCount());
        $this->assertTrue($table->hasColumn('id'));
        $this->assertTrue($table->hasColumn('name'));
        $this->assertTrue($table->hasColumn('email'));
    }

    // Test 16: Same engine - no conversion needed
    public function testSameEngineNoConversion() {
        $db = $this->createMySQLDatabase();
        $table = $db->addTableFromClass(MySQLSubclassFixture::class);

        $this->assertInstanceOf(MySQLTable::class, $table);
        $this->assertEquals('mysql_sub_table', $table->getNormalName());
    }

    // Test 17: Class extends Table AND has #[Table] attribute - subclass wins
    public function testSubclassWinsOverAttributes() {
        $db = $this->createMySQLDatabase();
        $table = $db->addTableFromClass(AttrAndSubclassFixture::class);

        // Subclass path is taken, so table name comes from constructor
        $this->assertEquals('subclass_wins', $table->getNormalName());
        // Should have columns defined in constructor, not from attributes
        $this->assertTrue($table->hasColumn('name'));
    }

    // Test 13: Table subclass with required constructor args throws
    public function testSubclassWithRequiredArgsThrows() {
        $this->expectException(DatabaseException::class);
        $db = $this->createMySQLDatabase();
        $db->addTableFromClass(RequiredArgTableFixture::class);
    }

    private function createMSSQLDatabase(): Database {
        $connInfo = new ConnectionInfo('mssql', 'sa', 'password', 'testing_db', '127.0.0.1');

        return new Database($connInfo);
    }
    private function createMySQLDatabase(): Database {
        $connInfo = new ConnectionInfo('mysql', 'root', '123456', 'testing_db', '127.0.0.1');

        return new Database($connInfo);
    }
}
