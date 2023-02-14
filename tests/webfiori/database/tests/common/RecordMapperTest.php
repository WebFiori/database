<?php
namespace webfiori\database\tests\common;

use PHPUnit\Framework\TestCase;
use webfiori\database\RecordMapper;
use webfiori\User;
/**
 * Description of RecordMapperTest
 *
 * @author Ibrahim
 */
class RecordMapperTest extends TestCase {
    /**
     * @test
     */
    public function test00() {
        $mapper = new RecordMapper();
        $this->assertEquals([], $mapper->getSettrsMap());
        $this->assertEquals('', $mapper->getClass());
        $mapper->addSetterMap('');
        $this->assertEquals([], $mapper->getSettrsMap());
        $mapper->addSetterMap('  ');
        $this->assertEquals([], $mapper->getSettrsMap());
    }
    /**
     * @test
     */
    public function test01() {
        $mapper = new RecordMapper();
        $mapper->addSetterMap('id');
        $this->assertEquals([
            'setId' => ['id']
        ], $mapper->getSettrsMap());
        $mapper->addSetterMap('  email_address');
        $this->assertEquals([
            'setId' => ['id'],
            'setEmailAddress' => ['email_address']
        ], $mapper->getSettrsMap());
        $mapper->addSetterMap('c_file_x');
        $this->assertEquals([
            'setId' => ['id'],
            'setEmailAddress' => ['email_address'],
            'setCFileX' => ['c_file_x']
        ], $mapper->getSettrsMap());
        $mapper->addSetterMap('user_id', 'setId');
        $this->assertEquals([
            'setId' => ['id', 'user_id'],
            'setEmailAddress' => ['email_address'],
            'setCFileX' => ['c_file_x']
        ], $mapper->getSettrsMap());
    }
    /**
     * @test
     */
    public function test02() {
        $mapper = new RecordMapper(User::class, [
            'id',
            'email',
            'username'
        ]);
        $user = $mapper->map([
            'id' => 44,
            'email' => 'ib@gg.com',
            'username' => 'Ibrahim'
        ]);
        $this->assertTrue($user instanceof User);
        $this->assertEquals('ID:  Name:  Email: ib@gg.com Username: Super User', $user.'');
        $mapper->addSetterMap('id', 'setUserID');
        $user = $mapper->map([
            'id' => 44,
            'email' => 'ib@gg.com',
            'username' => 'Ibrahim'
        ]);
        $this->assertTrue($user instanceof User);
        $this->assertEquals('ID: 44 Name:  Email: ib@gg.com Username: Super User', $user.'');
    }
    /**
     * @test
     */
    public function test03() {
        $this->expectException(\webfiori\database\DatabaseException::class);
        $this->expectExceptionMessage('Class not found: throwException');
        $mapper = new RecordMapper('throwException');
        
    }
}
