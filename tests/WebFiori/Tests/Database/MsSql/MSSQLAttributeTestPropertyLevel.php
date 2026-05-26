<?php
namespace WebFiori\Tests\Database\MsSql;

use WebFiori\Database\Attributes\Table;
use WebFiori\Database\Attributes\Column;
use WebFiori\Database\Attributes\ForeignKey;
use WebFiori\Database\DataType;

/**
 * Test entity using property-level attributes with unique, FK, comment, nullable, default.
 */
#[Table(name: 'prop_articles', comment: 'Articles table for testing')]
class MSSQLAttributeTestPropertyLevel {
    #[Column(type: DataType::INT, primary: true, identity: true)]
    public ?int $id = null;

    #[Column(type: DataType::NVARCHAR, size: 200, unique: true)]
    public string $title = '';

    #[Column(type: DataType::INT)]
    #[ForeignKey(table: 'test_users', column: 'id', name: 'fk_article_author', onUpdate: 'cascade', onDelete: 'set null')]
    public ?int $authorId = null;

    #[Column(type: DataType::TEXT, nullable: true, comment: 'Article body')]
    public ?string $content = null;

    #[Column(type: DataType::VARCHAR, size: 50, default: 'draft')]
    public string $status = 'draft';
}
