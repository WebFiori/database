<?php

use WebFiori\Database\Attributes\Column;
use WebFiori\Database\Attributes\ForeignKey;
use WebFiori\Database\Attributes\Table;
use WebFiori\Database\DataType;

/**
 * Comments table - uses stdClass for post relationship (no entity specified).
 */
#[Table(name: 'comments')]
class CommentsTable {
    #[Column(type: DataType::TEXT)]
    public string $content;
    #[Column(type: DataType::INT, primary: true, autoIncrement: true)]
    public int $id;

    #[Column(name: 'post-id', type: DataType::INT)]
    #[ForeignKey(table: PostsTable::class, column: 'id', property: 'post', name: 'fk_comment_post', onUpdate: 'cascade', onDelete: 'cascade')]
    public int $postId;
}
