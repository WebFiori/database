<?php
namespace WebFiori\Tests\Database\MsSql;

use WebFiori\Database\Attributes\Table;
use WebFiori\Database\Attributes\Column;
use WebFiori\Database\Attributes\ForeignKey;
use WebFiori\Database\DataType;

#[Table(name: 'test_posts')]
#[Column(name: 'id', type: DataType::INT, primary: true, identity: true)]
#[Column(name: 'user_id', type: DataType::INT)]
#[Column(name: 'title', type: DataType::VARCHAR, size: 200)]
#[ForeignKey(name: 'fk_post_user', column: 'user-id', table: 'test_users', onUpdate: 'cascade', onDelete: 'restrict')]
class MSSQLAttributeTestPost {
}
