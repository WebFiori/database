<?php
namespace WebFiori\Tests\Database\MsSql;

use WebFiori\Database\Attributes\Table;
use WebFiori\Database\Attributes\Column;
use WebFiori\Database\DataType;

#[Table(name: 'test_users')]
#[Column(name: 'id', type: DataType::INT, primary: true, identity: true)]
#[Column(name: 'name', type: DataType::VARCHAR, size: 100)]
#[Column(name: 'email', type: DataType::VARCHAR, size: 150, unique: true)]
class MSSQLAttributeTestUser {
}
