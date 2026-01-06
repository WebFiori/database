<?php

namespace WebFiori\Tests\Database\Repository;

use PHPUnit\Framework\TestCase;
use WebFiori\Database\Attributes\Column;
use WebFiori\Database\Attributes\ForeignKey;
use WebFiori\Database\Attributes\HasMany;
use WebFiori\Database\Attributes\Table;
use WebFiori\Database\ColOption;
use WebFiori\Database\ConnectionInfo;
use WebFiori\Database\Database;
use WebFiori\Database\DataType;
use WebFiori\Database\Repository\AbstractRepository;

// Domain entities (pure - no DB knowledge)
class Author {
    public ?int $id = null;
    public string $name = '';
    public array $posts = [];
}

class Post {
    public ?int $id = null;
    public string $title = '';
    public int $authorId;
    public ?Author $author = null;
}

// Table definitions (infrastructure)
#[Table(name: 'authors')]
#[HasMany(entity: Post::class, foreignKey: 'author-id', property: 'posts', table: 'posts')]
class AuthorsTable {
    #[Column(type: DataType::INT, primary: true, autoIncrement: true)]
    public int $id;

    #[Column(type: DataType::VARCHAR, size: 100)]
    public string $name;
}

#[Table(name: 'posts')]
class PostsTable {
    #[Column(type: DataType::INT, primary: true, autoIncrement: true)]
    public int $id;

    #[Column(type: DataType::VARCHAR, size: 200)]
    public string $title;

    #[Column(name: 'author-id', type: DataType::INT)]
    #[ForeignKey(table: AuthorsTable::class, column: 'id', property: 'author')]
    public int $authorId;
}

// Repositories
class AuthorRepository extends AbstractRepository {
    protected function getTableClass(): string {
        return AuthorsTable::class;
    }

    protected function getTableName(): string {
        return 'authors';
    }

    protected function getIdField(): string {
        return 'id';
    }

    protected function toEntity(array $row): object {
        $author = new Author();
        $author->id = (int) $row['id'];
        $author->name = $row['name'];
        return $author;
    }

    protected function toArray(object $entity): array {
        return [
            'id' => $entity->id,
            'name' => $entity->name
        ];
    }

    protected function relatedToEntity(string $relation, array $row): object {
        if ($relation === 'posts') {
            $post = new Post();
            $post->id = (int) $row['id'];
            $post->title = $row['title'];
            $post->authorId = (int) ($row['author-id'] ?? $row['author_id']);
            return $post;
        }
        return (object) $row;
    }
}

class PostRepository extends AbstractRepository {
    protected function getTableClass(): string {
        return PostsTable::class;
    }

    protected function getTableName(): string {
        return 'posts';
    }

    protected function getIdField(): string {
        return 'id';
    }

    protected function toEntity(array $row): object {
        $post = new Post();
        $post->id = (int) $row['id'];
        $post->title = $row['title'];
        $post->authorId = (int) ($row['author-id'] ?? $row['author_id']);
        return $post;
    }

    protected function toArray(object $entity): array {
        return [
            'id' => $entity->id,
            'title' => $entity->title,
            'author-id' => $entity->authorId
        ];
    }

    protected function relatedToEntity(string $relation, array $row): object {
        if ($relation === 'author') {
            $author = new Author();
            $author->id = (int) $row['id'];
            $author->name = $row['name'];
            return $author;
        }
        return (object) $row;
    }
}

class EagerLoadingTest extends TestCase {
    private static ?Database $db = null;
    private static ?AuthorRepository $authorRepo = null;
    private static ?PostRepository $postRepo = null;

    public static function setUpBeforeClass(): void {
        $conn = new ConnectionInfo('mysql', 'root', '123456', 'testing_db', '127.0.0.1');
        self::$db = new Database($conn);

        // Create authors table
        self::$db->createBlueprint('authors')->addColumns([
            'id' => [ColOption::TYPE => DataType::INT, ColOption::PRIMARY => true, ColOption::AUTO_INCREMENT => true],
            'name' => [ColOption::TYPE => DataType::VARCHAR, ColOption::SIZE => 100]
        ]);

        // Create posts table
        self::$db->createBlueprint('posts')->addColumns([
            'id' => [ColOption::TYPE => DataType::INT, ColOption::PRIMARY => true, ColOption::AUTO_INCREMENT => true],
            'title' => [ColOption::TYPE => DataType::VARCHAR, ColOption::SIZE => 200],
            'author-id' => [ColOption::TYPE => DataType::INT]
        ]);

        self::$db->table('authors')->createTable()->execute();
        self::$db->table('posts')->createTable()->execute();

        self::$authorRepo = new AuthorRepository(self::$db);
        self::$postRepo = new PostRepository(self::$db);
    }

    public static function tearDownAfterClass(): void {
        self::$db->raw('DROP TABLE IF EXISTS posts')->execute();
        self::$db->raw('DROP TABLE IF EXISTS authors')->execute();
    }

    protected function setUp(): void {
        self::$db->table('posts')->delete()->execute();
        self::$db->table('authors')->delete()->execute();
    }

    private function seedData(): array {
        // Create authors
        self::$db->table('authors')->insert(['name' => 'Ahmad'])->execute();
        self::$db->table('authors')->insert(['name' => 'Fatima'])->execute();

        // Get actual IDs
        $authors = self::$db->table('authors')->select()->execute()->fetchAll();
        $ahmadId = null;
        $fatimaId = null;
        foreach ($authors as $a) {
            if ($a['name'] === 'Ahmad') $ahmadId = $a['id'];
            if ($a['name'] === 'Fatima') $fatimaId = $a['id'];
        }

        // Create posts with actual IDs
        self::$db->table('posts')->insert(['title' => 'Post 1 by Ahmad', 'author-id' => $ahmadId])->execute();
        self::$db->table('posts')->insert(['title' => 'Post 2 by Ahmad', 'author-id' => $ahmadId])->execute();
        self::$db->table('posts')->insert(['title' => 'Post 3 by Fatima', 'author-id' => $fatimaId])->execute();

        return ['ahmad' => $ahmadId, 'fatima' => $fatimaId];
    }

    public function testFindAllWithoutEagerLoading() {
        $this->seedData();

        $authors = self::$authorRepo->findAll();

        $this->assertCount(2, $authors);
        $this->assertEmpty($authors[0]->posts);
        $this->assertEmpty($authors[1]->posts);
    }

    public function testFindAllWithHasManyEagerLoading() {
        $this->seedData();

        $authors = self::$authorRepo->with(['posts'])->findAll();

        $this->assertCount(2, $authors);

        // Ahmad has 2 posts
        $ahmad = $authors[0]->name === 'Ahmad' ? $authors[0] : $authors[1];
        $this->assertCount(2, $ahmad->posts);
        $this->assertInstanceOf(Post::class, $ahmad->posts[0]);

        // Fatima has 1 post
        $fatima = $authors[0]->name === 'Fatima' ? $authors[0] : $authors[1];
        $this->assertCount(1, $fatima->posts);
    }

    public function testFindByIdWithHasManyEagerLoading() {
        $ids = $this->seedData();

        $author = self::$authorRepo->with(['posts'])->findById($ids['ahmad']);

        $this->assertNotNull($author);
        $this->assertEquals('Ahmad', $author->name);
        $this->assertCount(2, $author->posts);
    }

    public function testFindAllWithBelongsToEagerLoading() {
        $this->seedData();

        $posts = self::$postRepo->with(['author'])->findAll();

        $this->assertCount(3, $posts);

        foreach ($posts as $post) {
            $this->assertNotNull($post->author);
            $this->assertInstanceOf(Author::class, $post->author);
        }

        // Verify correct author assignment
        $post1 = array_filter($posts, fn($p) => $p->title === 'Post 1 by Ahmad');
        $post1 = reset($post1);
        $this->assertEquals('Ahmad', $post1->author->name);
    }

    public function testFindByIdWithBelongsToEagerLoading() {
        $this->seedData();

        // Get first post ID
        $posts = self::$db->table('posts')->select()->execute()->fetchAll();
        $postId = $posts[0]['id'];

        $post = self::$postRepo->with(['author'])->findById($postId);

        $this->assertNotNull($post);
        $this->assertNotNull($post->author);
    }

    public function testPaginateWithEagerLoading() {
        $this->seedData();

        $page = self::$authorRepo->with(['posts'])->paginate(1, 10);

        $this->assertEquals(2, $page->getTotalItems());
        $items = $page->getItems();

        $hasPostsCount = 0;
        foreach ($items as $author) {
            if (!empty($author->posts)) {
                $hasPostsCount++;
            }
        }
        $this->assertEquals(2, $hasPostsCount);
    }

    public function testWithReturnsClone() {
        $repo1 = self::$authorRepo;
        $repo2 = $repo1->with(['posts']);

        $this->assertNotSame($repo1, $repo2);
    }

    public function testUnknownRelationThrowsException() {
        $this->seedData();

        $this->expectException(\WebFiori\Database\Repository\RepositoryException::class);
        $this->expectExceptionMessage('Unknown relationship: unknown');

        self::$authorRepo->with(['unknown'])->findAll();
    }

    public function testEmptyResultWithEagerLoading() {
        // No data seeded
        $authors = self::$authorRepo->with(['posts'])->findAll();

        $this->assertEmpty($authors);
    }

    public function testAuthorWithNoPosts() {
        // Create author without posts
        $author = new Author();
        $author->name = 'No Posts Author';
        self::$authorRepo->save($author);

        $authors = self::$authorRepo->with(['posts'])->findAll();

        $this->assertCount(1, $authors);
        $this->assertEmpty($authors[0]->posts);
    }
}
