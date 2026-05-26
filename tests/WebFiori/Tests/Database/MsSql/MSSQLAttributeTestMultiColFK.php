<?php
namespace WebFiori\Tests\Database\MsSql;

use WebFiori\Database\Attributes\Table;
use WebFiori\Database\Attributes\Column;
use WebFiori\Database\Attributes\ForeignKey;
use WebFiori\Database\DataType;

/**
 * Test entity with multi-column FK using 'columns' parameter.
 */
#[Table(name: 'order_items')]
#[Column(name: 'order_id', type: DataType::INT)]
#[Column(name: 'product_id', type: DataType::INT)]
#[Column(name: 'qty', type: DataType::INT)]
#[ForeignKey(table: 'orders', columns: ['order_id' => 'id'], name: 'fk_item_order', onUpdate: 'cascade', onDelete: 'cascade')]
class MSSQLAttributeTestMultiColFK {
}
