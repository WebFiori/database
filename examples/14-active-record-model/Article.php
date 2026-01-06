<?php

use WebFiori\Database\Attributes\Column;
use WebFiori\Database\Attributes\Table;
use WebFiori\Database\Database;
use WebFiori\Database\DataType;
use WebFiori\Database\Repository\AbstractRepository;

#[Table(name: 'articles')]
class Article extends AbstractRepository {
    #[Column(type: DataType::INT, primary: true, autoIncrement: true)]
    public ?int $id = null;

    #[Column(type: DataType::VARCHAR, size: 200)]
    public string $title = '';

    #[Column(type: DataType::TEXT)]
    public string $content = '';

    #[Column(name: 'author-name', type: DataType::VARCHAR, size: 100)]
    public string $authorName = '';

    #[Column(name: 'created-at', type: DataType::TIMESTAMP, default: 'now()')]
    public ?string $createdAt = null;

    public function __construct(Database $db) {
        parent::__construct($db);
    }

    protected function getTableName(): string {
        return 'articles';
    }

    protected function getIdField(): string {
        return 'id';
    }

    protected function toEntity(array $row): object {
        $article = new self($this->db);
        $article->id = (int) $row['id'];
        $article->title = $row['title'];
        $article->content = $row['content'];
        $article->authorName = $row['author-name'] ?? '';
        $article->createdAt = $row['created-at'] ?? null;
        return $article;
    }

    protected function toArray(object $entity): array {
        return [
            'id' => $entity->id,
            'title' => $entity->title,
            'content' => $entity->content,
            'author-name' => $entity->authorName,
        ];
    }

    // Custom query methods
    public function findByAuthor(string $author): array {
        $result = $this->getDatabase()->table($this->getTableName())
            ->select()
            ->where('author-name', $author)
            ->execute();

        return array_map(fn($row) => $this->toEntity($row), $result->fetchAll());
    }
}
