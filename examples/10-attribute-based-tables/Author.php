<?php

use WebFiori\Database\Attributes\Column;
use WebFiori\Database\Attributes\Table;
use WebFiori\Database\DataType;

#[Table(name: 'authors')]
class Author {
    #[Column(type: DataType::TIMESTAMP, default: 'current_timestamp')]
    public ?string $createdAt = null;

    #[Column(type: DataType::VARCHAR, size: 150)]
    public string $email;
    #[Column(type: DataType::INT, primary: true, autoIncrement: true)]
    public int $id;

    #[Column(type: DataType::VARCHAR, size: 100)]
    public string $name;
}
