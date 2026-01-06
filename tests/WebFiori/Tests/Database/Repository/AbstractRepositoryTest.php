<?php

namespace WebFiori\Tests\Database\Repository;

use PHPUnit\Framework\TestCase;
use WebFiori\Database\ConnectionInfo;
use WebFiori\Database\Database;
use WebFiori\Database\ColOption;
use WebFiori\Database\DataType;
use WebFiori\Database\Repository\AbstractRepository;
use WebFiori\Database\Repository\RepositoryException;

class TestEntity {
    public ?int $id = null;
    public string $name;
    public int $value;

    public function __construct(?int $id = null, string $name = '', int $value = 0) {
        $this->id = $id;
        $this->name = $name;
        $this->value = $value;
    }
}

class TestRepository extends AbstractRepository {
    protected function getTableName(): string {
        return 'test_entities';
    }

    protected function getIdField(): string {
        return 'id';
    }

    protected function toEntity(array $row): object {
        return new TestEntity(
            (int) $row['id'],
            $row['name'],
            (int) $row['value']
        );
    }

    protected function toArray(object $entity): array {
        return [
            'id' => $entity->id,
            'name' => $entity->name,
            'value' => $entity->value
        ];
    }
}

class AbstractRepositoryTest extends TestCase {
    private static ?Database $db = null;
    private static ?TestRepository $repo = null;

    public static function setUpBeforeClass(): void {
        $conn = new ConnectionInfo('mysql', 'root', '123456', 'testing_db', '127.0.0.1');
        self::$db = new Database($conn);

        self::$db->createBlueprint('test_entities')->addColumns([
            'id' => [
                ColOption::TYPE => DataType::INT,
                ColOption::PRIMARY => true,
                ColOption::AUTO_INCREMENT => true
            ],
            'name' => [
                ColOption::TYPE => DataType::VARCHAR,
                ColOption::SIZE => 100
            ],
            'value' => [
                ColOption::TYPE => DataType::INT
            ]
        ]);

        self::$db->table('test_entities')->createTable()->execute();
        self::$repo = new TestRepository(self::$db);
    }

    public static function tearDownAfterClass(): void {
        self::$db->setQuery('DROP TABLE IF EXISTS test_entities');
        self::$db->execute();
    }

    protected function setUp(): void {
        self::$db->table('test_entities')->delete()->execute();
    }

    public function testSaveNewEntity() {
        $entity = new TestEntity(null, 'Test', 100);
        self::$repo->save($entity);

        $this->assertEquals(1, self::$repo->count());
        $found = self::$repo->findAll()[0];
        $this->assertEquals('Test', $found->name);
        $this->assertEquals(100, $found->value);
    }

    public function testSaveExistingEntity() {
        $entity = new TestEntity(null, 'Original', 50);
        self::$repo->save($entity);

        $found = self::$repo->findAll()[0];
        $found->name = 'Updated';
        $found->value = 75;
        self::$repo->save($found);

        $this->assertEquals(1, self::$repo->count());
        $updated = self::$repo->findById($found->id);
        $this->assertEquals('Updated', $updated->name);
        $this->assertEquals(75, $updated->value);
    }

    public function testSaveAllEmpty() {
        self::$repo->saveAll([]);
        $this->assertEquals(0, self::$repo->count());
    }

    public function testSaveAllNewEntities() {
        $entities = [
            new TestEntity(null, 'Item1', 10),
            new TestEntity(null, 'Item2', 20),
            new TestEntity(null, 'Item3', 30)
        ];

        self::$repo->saveAll($entities);

        $this->assertEquals(3, self::$repo->count());
        $all = self::$repo->findAll();
        $names = array_map(fn($e) => $e->name, $all);
        $this->assertContains('Item1', $names);
        $this->assertContains('Item2', $names);
        $this->assertContains('Item3', $names);
    }

    public function testSaveAllExistingEntities() {
        // Insert initial entities
        self::$repo->saveAll([
            new TestEntity(null, 'A', 1),
            new TestEntity(null, 'B', 2)
        ]);

        // Update them
        $all = self::$repo->findAll();
        foreach ($all as $entity) {
            $entity->value = $entity->value * 10;
        }
        self::$repo->saveAll($all);

        $this->assertEquals(2, self::$repo->count());
        $updated = self::$repo->findAll();
        $values = array_map(fn($e) => $e->value, $updated);
        sort($values);
        $this->assertEquals([10, 20], $values);
    }

    public function testSaveAllMixed() {
        // Insert one entity first
        self::$repo->save(new TestEntity(null, 'Existing', 100));
        $existing = self::$repo->findAll()[0];
        $existing->name = 'Modified';

        // Save mix of new and existing
        self::$repo->saveAll([
            $existing,
            new TestEntity(null, 'New1', 200),
            new TestEntity(null, 'New2', 300)
        ]);

        $this->assertEquals(3, self::$repo->count());
        
        $modified = self::$repo->findById($existing->id);
        $this->assertEquals('Modified', $modified->name);
        
        $all = self::$repo->findAll();
        $names = array_map(fn($e) => $e->name, $all);
        $this->assertContains('New1', $names);
        $this->assertContains('New2', $names);
    }

    public function testFindByIdWithNullThrowsException() {
        $this->expectException(RepositoryException::class);
        $this->expectExceptionMessage('Cannot find: no ID provided');

        self::$repo->findById(null);
    }

    public function testDeleteByIdWithNullThrowsException() {
        $this->expectException(RepositoryException::class);
        $this->expectExceptionMessage('Cannot delete: no ID provided');

        self::$repo->deleteById(null);
    }

    public function testFindByIdWithValidId() {
        self::$repo->save(new TestEntity(null, 'FindMe', 42));
        $all = self::$repo->findAll();
        $id = $all[0]->id;

        $found = self::$repo->findById($id);

        $this->assertNotNull($found);
        $this->assertEquals('FindMe', $found->name);
    }

    public function testDeleteByIdWithValidId() {
        self::$repo->save(new TestEntity(null, 'DeleteMe', 99));
        $all = self::$repo->findAll();
        $id = $all[0]->id;

        self::$repo->deleteById($id);

        $this->assertEquals(0, self::$repo->count());
    }

    public function testSaveWithNullOnPureRepoThrowsException() {
        $this->expectException(RepositoryException::class);
        $this->expectExceptionMessage('Cannot save: no entity provided');

        self::$repo->save();
    }

    public function testReloadWithEntity() {
        $entity = new TestEntity(null, 'Original', 100);
        self::$repo->save($entity);
        $saved = self::$repo->findAll()[0];

        // Modify in database directly
        self::$db->table('test_entities')
            ->update(['name' => 'Modified'])
            ->where('id', $saved->id)
            ->execute();

        $reloaded = self::$repo->reload($saved);

        $this->assertEquals('Modified', $reloaded->name);
    }

    public function testReloadWithNullOnPureRepoThrowsException() {
        $this->expectException(RepositoryException::class);
        $this->expectExceptionMessage('Cannot find: no ID provided');

        self::$repo->reload();
    }
}
