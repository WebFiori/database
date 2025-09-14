<?php
namespace WebFiori\Tests\Database\Common;

use PHPUnit\Framework\TestCase;
use UserClass;
use WebFiori\Database\ColOption;
use WebFiori\Database\DataType;
use WebFiori\Database\EntityMapper;
use WebFiori\Tests\Database\MySql\MySQLTestSchema;

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
        $t = $schema->getTable('users');
        $t->addColumns([
            'c-x-file' => [
            ColOption::TYPE => DataType::BOOL
            ]
        ]);
        $entityMapper = new EntityMapper($t, 'UserClass');
        
        $entityMapper->setPath(__DIR__);
        $entityMapper->setUseJsonI(true);
        $this->assertEquals('UserClass', $entityMapper->getEntityName());
        $this->assertEquals('\UserClass', $entityMapper->getEntityName(true));
        $this->assertEquals([
            'age'=> 'age',
            'first-name' => 'firstName',
            'id' => 'id',
            'last-name' => 'lastName',
            'c-x-file' => 'cxFile'
        ], $entityMapper->getAttribitesNames());
        $this->assertEquals([
            'setters' => [
                'setAge',
                'setCXFile',
                'setFirstName',
                'setId',
                'setLastName',
                
            ],
            'getters' => [
                'getAge',
                'getCXFile',
                'getFirstName',
                'getId',
                'getLastName',
                
            ]
        ], $entityMapper->getEntityMethods());
        $this->assertEquals([
            'setId' => 'id',
            'setCXFile' => 'c_x_file',
            'setFirstName' => 'first_name',
            'setLastName' => 'last_name',
            'setAge' => 'age',
            
        ], $entityMapper->getSettersMap());
        $this->assertEquals([
            'setId' => 'id',
            'setCXFile' => 'c-x-file',
            'setFirstName' => 'first-name',
            'setLastName' => 'last-name',
            'setAge' => 'age',
            
        ], $entityMapper->getSettersMap(true));
        $this->assertEquals([
            'getId' => 'id',
            'getCXFile' => 'c_x_file',
            'getFirstName' => 'first_name',
            'getLastName' => 'last_name',
            'getAge' => 'age',
            
        ], $entityMapper->getGettersMap());
        
        $this->assertEquals([
            'getId' => 'id',
            'getCXFile' => 'c-x-file',
            'getFirstName' => 'first-name',
            'getLastName' => 'last-name',
            'getAge' => 'age',
            
        ], $entityMapper->getGettersMap(true));
        
        $entityMapper->addAttribute('extraAttribute');
        $this->assertEquals([
            'setters' => [
                'setAge',
                'setCXFile',
                'setExtraAttribute',
                'setFirstName',
                'setId',
                
                'setLastName',
                
            ],
            'getters' => [
                'getAge',
                'getCXFile',
                'getExtraAttribute',
                'getFirstName',
                'getId',
                'getLastName',
                
            ]
        ], $entityMapper->getEntityMethods());
        $this->assertTrue($entityMapper->create());
        $this->assertFalse($entityMapper->setNamespace('\\A\\B'));
        $this->assertFalse($entityMapper->setNamespace('6\\B'));
        $this->assertFalse($entityMapper->setNamespace("A\\\\\B"));
        $this->assertFalse($entityMapper->setNamespace('A\\$AX'));
        return $entityMapper;
    }
    /**
     * @test
     * @depends test00
     */
    public function test02(EntityMapper $m) {
        require_once __DIR__.DIRECTORY_SEPARATOR.'UserClass.php';
        $recordsMapper = $m->getRecordMapper();
        $this->assertEquals('\UserClass', $recordsMapper->getClass());
        $this->assertEquals([
            'setId' => ['id'],
            'setFirstName' => ['first_name'],
            'setLastName' => ['last_name'],
            'setAge' => ['age'],
            'setCXFile' => ['c_x_file']
        ], $recordsMapper->getSettersMap());
        $obj = $recordsMapper->map([
            'id' => 55,
            'first_name' => 'Ibrahim',
            'last_name' => 'BinAlshikh',
            'age' => 28
        ]);
        $this->assertTrue($obj instanceof UserClass);
        $this->assertEquals(55, $obj->getId());
        $this->assertEquals('Ibrahim', $obj->getFirstName());
        $this->assertEquals('BinAlshikh', $obj->getLastName());
        $this->assertEquals(28, $obj->getAge());
        unlink(__DIR__.DIRECTORY_SEPARATOR.'UserClass.php');
    }
    /**
     * @test
     */
    public function test01() {
        $schema = new MySQLTestSchema();
        $entityMapper = new EntityMapper($schema->getTable('users'), '', '', '');
        $this->assertEquals('NewEntity', $entityMapper->getEntityName());
        $this->assertEquals('', $entityMapper->getNamespace());
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
