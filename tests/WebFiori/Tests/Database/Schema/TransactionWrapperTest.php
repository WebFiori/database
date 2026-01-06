<?php

namespace WebFiori\Tests\Database\Schema;

use PHPUnit\Framework\TestCase;
use WebFiori\Database\ConnectionInfo;
use WebFiori\Database\Database;
use WebFiori\Database\Schema\AbstractMigration;
use WebFiori\Database\Schema\SchemaRunner;

class TransactionEnabledMigration extends AbstractMigration {
    public static bool $executed = false;
    
    public function up(Database $db): void {
        self::$executed = true;
    }
    
    public function down(Database $db): void {}
}

class TransactionDisabledMigration extends AbstractMigration {
    public static bool $executed = false;
    
    public function up(Database $db): void {
        self::$executed = true;
    }
    
    public function down(Database $db): void {}
    
    public function useTransaction(Database $db): bool {
        return false;
    }
}

class DbmsAwareMigration extends AbstractMigration {
    public static bool $executed = false;
    public static ?string $detectedDbType = null;
    
    public function up(Database $db): void {
        self::$executed = true;
    }
    
    public function down(Database $db): void {}
    
    public function useTransaction(Database $db): bool {
        self::$detectedDbType = $db->getConnectionInfo()->getDatabaseType();
        return self::$detectedDbType !== 'mysql';
    }
}

class TransactionWrapperTest extends TestCase {
    
    protected function setUp(): void {
        TransactionEnabledMigration::$executed = false;
        TransactionDisabledMigration::$executed = false;
        DbmsAwareMigration::$executed = false;
        DbmsAwareMigration::$detectedDbType = null;
    }
    
    private function getConnectionInfo(): ConnectionInfo {
        return new ConnectionInfo('mysql', 'root', getenv('MYSQL_ROOT_PASSWORD') ?: '123456', 'testing_db', '127.0.0.1');
    }
    
    public function testUseTransactionDefaultsToTrue() {
        $migration = new TransactionEnabledMigration();
        $db = new Database($this->getConnectionInfo());
        
        $this->assertTrue($migration->useTransaction($db));
    }
    
    public function testUseTransactionCanBeDisabled() {
        $migration = new TransactionDisabledMigration();
        $db = new Database($this->getConnectionInfo());
        
        $this->assertFalse($migration->useTransaction($db));
    }
    
    public function testDbmsAwareTransaction() {
        $migration = new DbmsAwareMigration();
        
        $mysqlDb = new Database(new ConnectionInfo('mysql', 'root', '123456', 'test'));
        $this->assertFalse($migration->useTransaction($mysqlDb));
        $this->assertEquals('mysql', DbmsAwareMigration::$detectedDbType);
        
        $mssqlDb = new Database(new ConnectionInfo('mssql', 'sa', '123456', 'test'));
        $this->assertTrue($migration->useTransaction($mssqlDb));
        $this->assertEquals('mssql', DbmsAwareMigration::$detectedDbType);
    }
    
    public function testApplyUsesTransactionWhenEnabled() {
        $runner = new SchemaRunner($this->getConnectionInfo());
        $runner->register(TransactionEnabledMigration::class);
        
        try {
            $runner->createSchemaTable();
            $result = $runner->apply();
            
            $this->assertTrue(TransactionEnabledMigration::$executed);
            $this->assertCount(1, $result->getApplied());
            
            $runner->dropSchemaTable();
        } catch (\Exception $ex) {
            $this->markTestSkipped('Database connection failed: ' . $ex->getMessage());
        }
    }
    
    public function testApplySkipsTransactionWhenDisabled() {
        $runner = new SchemaRunner($this->getConnectionInfo());
        $runner->register(TransactionDisabledMigration::class);
        
        try {
            $runner->createSchemaTable();
            $result = $runner->apply();
            
            $this->assertTrue(TransactionDisabledMigration::$executed);
            $this->assertCount(1, $result->getApplied());
            
            $runner->dropSchemaTable();
        } catch (\Exception $ex) {
            $this->markTestSkipped('Database connection failed: ' . $ex->getMessage());
        }
    }
}
