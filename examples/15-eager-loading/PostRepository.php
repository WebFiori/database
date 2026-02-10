<?php

use WebFiori\Database\Repository\AbstractRepository;

require_once __DIR__.'/Author.php';
require_once __DIR__.'/Post.php';
require_once __DIR__.'/Comment.php';
require_once __DIR__.'/PostsTable.php';

class PostRepository extends AbstractRepository {
    protected function getIdField(): string {
        return 'id';
    }
    protected function getTableClass(): string {
        return PostsTable::class;
    }

    protected function getTableName(): string {
        return 'posts';
    }

    protected function toArray(object $entity): array {
        return [
            'id' => $entity->id,
            'title' => $entity->title,
            'author-id' => $entity->authorId
        ];
    }

    protected function toEntity(array $row): object {
        $post = new Post();
        $post->id = (int) $row['id'];
        $post->title = $row['title'];
        $post->authorId = (int) ($row['author-id'] ?? $row['author_id'] ?? 0);

        return $post;
    }
}
