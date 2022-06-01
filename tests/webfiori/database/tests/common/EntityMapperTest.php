<?php
namespace webfiori\database\tests\common;
use PHPUnit\Framework\TestCase;
use webfiori\database\EntityMapper;
use webfiori\database\tests\mysql\MySQLTestSchema;

/**
 * Description of EntityMapperTest
 *
 * @author Ibrahim
 */
class EntityMapperTest extends TestCase {
    /**
     * @test
     */
    public function test00() {
        $schema = new MySQLTestSchema();
        $entityMapper = new EntityMapper($schema->getTable('users'), 'UserClass');
        $entityMapper->setUseJsonI(true);
        $this->assertEquals('UserClass', $entityMapper->getEntityName());
        $this->assertEquals([
            'age'=> 'age',
            'first-name' => 'firstName',
            'id' => 'id',
            'last-name' => 'lastName',
            
        ], $entityMapper->getAttribitesNames());
        $this->assertEquals([
            'setters' => [
                'setAge',
                'setFirstName',
                'setId',
                'setLastName',
                
            ],
            'getters' => [
                'getAge',
                'getFirstName',
                'getId',
                'getLastName',
                
            ]
        ], $entityMapper->getEntityMethods());
        $this->assertEquals([
            'setId' => 'id',
            'setFirstName' => 'first_name',
            'setLastName' => 'last_name',
            'setAge' => 'age',
        ], $entityMapper->getSettersMap());
        
        $entityMapper->addAttribute('extraAttribute');
        $this->assertEquals([
            'setters' => [
                'setAge',
                'setExtraAttribute',
                'setFirstName',
                'setId',
                'setLastName',
                
            ],
            'getters' => [
                'getAge',
                'getExtraAttribute',
                'getFirstName',
                'getId',
                'getLastName',
                
            ]
        ], $entityMapper->getEntityMethods());
        $this->assertTrue($entityMapper->create());
    }
    /**
     * @test
     */
    public function test01() {
        $schema = new MySQLTestSchema();
        $entityMapper = new EntityMapper($schema->getTable('users'), '', '', '');
        $this->assertEquals('NewEntity', $entityMapper->getEntityName());
        $this->assertEquals('webfiori\\database\\entity', $entityMapper->getNamespace());
        $this->assertFalse($entityMapper->addAttribute(''));
        $this->assertFalse($entityMapper->addAttribute('0cool'));
        $this->assertFalse($entityMapper->addAttribute('not valid'));
        $this->assertFalse($entityMapper->addAttribute('also$not_valid'));
        $this->assertTrue($entityMapper->addAttribute('validName'));
        $this->assertFalse($entityMapper->addAttribute('validName  '));
        $this->assertFalse($entityMapper->setEntityName('0Invalid'));
        $this->assertFalse($entityMapper->setEntityName('Invalid Class Name'));
        $this->assertTrue($entityMapper->setEntityName('ValidName'));
        $this->assertFalse($entityMapper->setEntityName('Invalid%Name'));
    }
}
