<?php
namespace WebFiori\Tests\Database\MsSql;

use WebFiori\Database\Attributes\Table;
use WebFiori\Database\Attributes\Column;
use WebFiori\Database\Attributes\ForeignKey;
use WebFiori\Database\DataType;

/**
 * Test entity using class-level FK with class reference for table resolution.
 */
#[Table(name: 'comments')]
#[Column(name: 'id', type: DataType::INT, primary: true, identity: true)]
#[Column(name: 'body', type: DataType::NVARCHAR, size: 500)]
#[ForeignKey(table: MSSQLAttributeTestUniqueNvarchar::class, column: 'id', name: 'fk_comment_table', onUpdate: 'cascade', onDelete: 'cascade')]
class MSSQLAttributeTestClassRefFK {
}
