<?php

use WebFiori\Database\Database;
use WebFiori\Database\Repository\AbstractRepository;

require_once __DIR__.'/Author.php';
require_once __DIR__.'/Post.php';
require_once __DIR__.'/AuthorsTable.php';

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
}
