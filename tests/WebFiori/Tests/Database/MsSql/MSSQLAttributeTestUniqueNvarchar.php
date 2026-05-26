<?php
namespace WebFiori\Tests\Database\MsSql;

use WebFiori\Database\Attributes\Table;
use WebFiori\Database\Attributes\Column;
use WebFiori\Database\DataType;

/**
 * Test entity matching the exact scenario from issue #153:
 * NVARCHAR column with unique: true.
 */
#[Table(name: 'test_table')]
#[Column(name: 'id', type: DataType::INT, primary: true, identity: true)]
#[Column(name: 'name', type: DataType::NVARCHAR, size: 64, unique: true)]
class MSSQLAttributeTestUniqueNvarchar {
}
