<?php
namespace WebFiori\Tests\Database\Common;

use PHPUnit\Framework\TestCase;
use WebFiori\Database\ColOption;
use WebFiori\Database\DataType;
use WebFiori\Database\Entity\EntityGenerator;
use WebFiori\Database\MySql\MySQLTable;

class EntityGeneratorTest extends TestCase {
    private string $outputDir;

    protected function setUp(): void {
        $this->outputDir = sys_get_temp_dir().'/entity_gen_test_'.uniqid();
        mkdir($this->outputDir);
    }

    protected function tearDown(): void {
        array_map('unlink', glob($this->outputDir.'/*.php'));
        rmdir($this->outputDir);
    }

    /**
     * @test
     */
    public function testGenerateBasicEntity() {
        $table = new MySQLTable('users');
        $table->addColumns([
            'id' => [ColOption::TYPE => DataType::INT, ColOption::PRIMARY => true, ColOption::AUTO_INCREMENT => true],
            'name' => [ColOption::TYPE => DataType::VARCHAR, ColOption::SIZE => 100],
            'email' => [ColOption::TYPE => DataType::VARCHAR, ColOption::SIZE => 200, ColOption::NULL => true],
            'age' => [ColOption::TYPE => DataType::INT],
            'is-active' => [ColOption::TYPE => DataType::BOOL, ColOption::DEFAULT => true],
        ]);

        $gen = new EntityGenerator($table, 'UserEntity', $this->outputDir, 'App\\Entity');
        $result = $gen->generate();

        $this->assertTrue($result);
        $this->assertFileExists($this->outputDir.'/UserEntity.php');

        $code = file_get_contents($this->outputDir.'/UserEntity.php');
        $this->assertStringContainsString('namespace App\\Entity;', $code);
        $this->assertStringContainsString('class UserEntity', $code);
        $this->assertStringContainsString('protected ?int $id', $code);
        $this->assertStringContainsString('protected string $name', $code);
        $this->assertStringContainsString('$email', $code);
        $this->assertStringContainsString('protected int $age', $code);
        $this->assertStringContainsString('public function getId()', $code);
        $this->assertStringContainsString('public function getName()', $code);
        $this->assertStringContainsString('public function getIsActive()', $code);
    }

    /**
     * @test
     */
    public function testGenerateWithoutNamespace() {
        $table = new MySQLTable('items');
        $table->addColumns([
            'id' => [ColOption::TYPE => DataType::INT, ColOption::PRIMARY => true, ColOption::AUTO_INCREMENT => true],
            'price' => [ColOption::TYPE => DataType::DECIMAL, ColOption::SIZE => 10],
        ]);

        $gen = new EntityGenerator($table, 'ItemEntity', $this->outputDir);
        $result = $gen->generate();

        $this->assertTrue($result);
        $code = file_get_contents($this->outputDir.'/ItemEntity.php');
        $this->assertStringNotContainsString('namespace', $code);
        $this->assertStringContainsString('class ItemEntity', $code);
        $this->assertStringContainsString('float', $code);
    }

    /**
     * @test
     */
    public function testGenerateWithDefaultValues() {
        $table = new MySQLTable('config');
        $table->addColumns([
            'key-name' => [ColOption::TYPE => DataType::VARCHAR, ColOption::SIZE => 50, ColOption::DEFAULT => 'default_key'],
            'int-val' => [ColOption::TYPE => DataType::INT, ColOption::DEFAULT => 42],
        ]);

        $gen = new EntityGenerator($table, 'ConfigEntity', $this->outputDir, 'App');
        $result = $gen->generate();

        $this->assertTrue($result);
        $code = file_get_contents($this->outputDir.'/ConfigEntity.php');
        $this->assertStringContainsString('$keyName', $code);
        $this->assertStringContainsString('$intVal', $code);
    }
}
