<?php

use WebFiori\Database\Repository\AbstractRepository;

require_once __DIR__.'/PostsTable.php';
require_once __DIR__.'/CommentsTable.php';

class CommentRepository extends AbstractRepository {
    protected function getIdField(): string {
        return 'id';
    }
    protected function getTableClass(): string {
        return CommentsTable::class;
    }

    protected function getTableName(): string {
        return 'comments';
    }

    protected function toArray(object $entity): array {
        return [
            'id' => $entity->id,
            'content' => $entity->content,
            'post-id' => $entity->postId
        ];
    }

    protected function toEntity(array $row): object {
        $comment = new stdClass();
        $comment->id = (int) $row['id'];
        $comment->content = $row['content'];
        $comment->postId = (int) ($row['post-id'] ?? $row['post_id'] ?? 0);

        return $comment;
    }
}
