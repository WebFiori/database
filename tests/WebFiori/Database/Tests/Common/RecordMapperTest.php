<?php
namespace WebFiori\Database\tests\common;

use PHPUnit\Framework\TestCase;
use WebFiori\Database\RecordMapper;
use WebFiori\User;
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
        $this->assertEquals([], $mapper->getSettersMap());
        $this->assertEquals('', $mapper->getClass());
        $mapper->addSetterMap('');
        $this->assertEquals([], $mapper->getSettersMap());
        $mapper->addSetterMap('  ');
        $this->assertEquals([], $mapper->getSettersMap());
    }
    /**
     * @test
     */
    public function test01() {
        $mapper = new RecordMapper();
        $mapper->addSetterMap('id');
        $this->assertEquals([
            'setId' => ['id']
        ], $mapper->getSettersMap());
        $mapper->addSetterMap('  email_address');
        $this->assertEquals([
            'setId' => ['id'],
            'setEmailAddress' => ['email_address']
        ], $mapper->getSettersMap());
        $mapper->addSetterMap('c_file_x');
        $this->assertEquals([
            'setId' => ['id'],
            'setEmailAddress' => ['email_address'],
            'setCFileX' => ['c_file_x']
        ], $mapper->getSettersMap());
        $mapper->addSetterMap('user_id', 'setId');
        $this->assertEquals([
            'setId' => ['id', 'user_id'],
            'setEmailAddress' => ['email_address'],
            'setCFileX' => ['c_file_x']
        ], $mapper->getSettersMap());
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
        $this->expectException(\WebFiori\Database\DatabaseException::class);
        $this->expectExceptionMessage('Class not found: throwException');
        $mapper = new RecordMapper('throwException');
        
    }
}
