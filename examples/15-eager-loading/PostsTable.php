<?php

use WebFiori\Database\Attributes\Column;
use WebFiori\Database\Attributes\ForeignKey;
use WebFiori\Database\Attributes\HasMany;
use WebFiori\Database\Attributes\Table;
use WebFiori\Database\DataType;

/**
 * Table definition with relationship - separate from domain entity.
 */
#[Table(name: 'posts')]
#[HasMany(entity: Comment::class, foreignKey: 'post-id', property: 'comments', table: 'comments')]
class PostsTable {
    #[Column(type: DataType::INT, primary: true, autoIncrement: true)]
    public int $id;

    #[Column(type: DataType::VARCHAR, size: 200)]
    public string $title;

    #[Column(name: 'author-id', type: DataType::INT)]
    #[ForeignKey(table: AuthorsTable::class, column: 'id', property: 'author', entity: Author::class, name: 'fk_post_author', onUpdate: 'cascade', onDelete: 'cascade')]
    public int $authorId;
}
