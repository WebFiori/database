<?php
namespace webfiori\database\tests;
use PHPUnit\Framework\TestCase;
use webfiori\database\EntityMapper;
use webfiori\database\tests\MySQLTestSchema;

/**
 * Description of EntityMapperTest
 *
 * @author Ibrahim
 */
class EntityMapperTest extends TestCase {
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
}
