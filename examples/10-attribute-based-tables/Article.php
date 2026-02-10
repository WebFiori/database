<?php

use WebFiori\Database\Attributes\Column;
use WebFiori\Database\Attributes\ForeignKey;
use WebFiori\Database\Attributes\Table;
use WebFiori\Database\DataType;

#[Table(name: 'articles')]
class Article {
    #[Column(type: DataType::INT)]
    #[ForeignKey(table: Author::class, column: 'id', name: 'fk_article_author', onUpdate: 'cascade', onDelete: 'cascade')]
    public int $authorId;

    #[Column(type: DataType::TEXT)]
    public string $content;
    #[Column(type: DataType::INT, primary: true, autoIncrement: true)]
    public int $id;

    #[Column(type: DataType::TIMESTAMP, default: 'current_timestamp')]
    public ?string $publishedAt = null;

    #[Column(type: DataType::VARCHAR, size: 200)]
    public string $title;
}
