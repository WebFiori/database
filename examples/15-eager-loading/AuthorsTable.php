<?php

use WebFiori\Database\Attributes\Column;
use WebFiori\Database\Attributes\HasMany;
use WebFiori\Database\Attributes\Table;
use WebFiori\Database\DataType;

/**
 * Table definition with relationship - separate from domain entity.
 */
#[Table(name: 'authors')]
#[HasMany(entity: Post::class, foreignKey: 'author-id', property: 'posts', table: 'posts')]
class AuthorsTable {
    #[Column(type: DataType::INT, primary: true, autoIncrement: true)]
    public int $id;

    #[Column(type: DataType::VARCHAR, size: 100)]
    public string $name;
}
