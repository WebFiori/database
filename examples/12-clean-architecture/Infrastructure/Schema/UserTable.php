<?php
namespace Infrastructure\Schema;

use WebFiori\Database\Attributes\Column;
use WebFiori\Database\Attributes\Table;
use WebFiori\Database\DataType;

/**
 * Table definition using PHP 8 attributes at class level
 */
#[Table(name: 'users')]
#[Column(name: 'id', type: DataType::INT, primary: true, autoIncrement: true)]
#[Column(name: 'name', type: DataType::VARCHAR, size: 100)]
#[Column(name: 'email', type: DataType::VARCHAR, size: 150)]
#[Column(name: 'age', type: DataType::INT)]
class UserTable {
}
