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
            'age',
            'firstName',
            'id',
            'lastName',
            
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
        $this->assertTrue($entityMapper->create());
    }
}
