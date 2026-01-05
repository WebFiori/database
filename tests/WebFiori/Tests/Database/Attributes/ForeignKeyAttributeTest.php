<?php

namespace WebFiori\Tests\Database\Attributes;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use WebFiori\Database\Attributes\AttributeTableBuilder;
use WebFiori\Database\Attributes\Column;
use WebFiori\Database\Attributes\ForeignKey;
use WebFiori\Database\Attributes\Table;
use WebFiori\Database\DataType;

// Test classes for attributes

#[Table(name: 'users')]
class TestUser {
    #[Column(type: DataType::INT, primary: true, autoIncrement: true)]
    public int $id;

    #[Column(type: DataType::VARCHAR, size: 100)]
    public string $name;
}

#[Table(name: 'posts')]
class TestPost {
    #[Column(type: DataType::INT, primary: true, autoIncrement: true)]
    public int $id;

    #[Column(type: DataType::INT)]
    #[ForeignKey(table: TestUser::class, column: 'id', name: 'fk_post_user', onUpdate: 'cascade', onDelete: 'cascade')]
    public int $userId;

    #[Column(type: DataType::VARCHAR, size: 200)]
    public string $title;
}

#[Table(name: 'order_items')]
#[Column(name: 'order-id', type: DataType::INT)]
#[Column(name: 'product-id', type: DataType::INT)]
#[Column(name: 'quantity', type: DataType::INT)]
#[ForeignKey(table: 'orders', columns: ['order-id' => 'id'], name: 'fk_item_order', onUpdate: 'cascade', onDelete: 'cascade')]
#[ForeignKey(table: 'products', columns: ['product-id' => 'id'], name: 'fk_item_product', onUpdate: 'cascade', onDelete: 'cascade')]
class TestOrderItem {
}

#[Table(name: 'composite_ref')]
#[Column(name: 'tenant-id', type: DataType::INT)]
#[Column(name: 'user-id', type: DataType::INT)]
#[Column(name: 'data', type: DataType::VARCHAR, size: 100)]
#[ForeignKey(table: 'tenant_users', columns: ['tenant-id' => 'tenant_id', 'user-id' => 'user_id'], name: 'fk_composite', onUpdate: 'cascade', onDelete: 'cascade')]
class TestCompositeFK {
}

class ForeignKeyAttributeTest extends TestCase {
    public function testSingleColumnFK() {
        $fk = new ForeignKey(table: 'users', column: 'id');
        $this->assertEquals(['id'], $fk->getColumnsMap());
    }

    public function testMultipleColumnsFK() {
        $fk = new ForeignKey(table: 'users', columns: ['local_id' => 'id', 'tenant_id' => 'tenant_id']);
        $this->assertEquals(['local_id' => 'id', 'tenant_id' => 'tenant_id'], $fk->getColumnsMap());
    }

    public function testBothColumnAndColumnsThrowsException() {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("ForeignKey: Use either 'column' or 'columns', not both");
        
        new ForeignKey(table: 'users', column: 'id', columns: ['local_id' => 'id']);
    }

    public function testClassReferenceResolution() {
        $table = AttributeTableBuilder::build(TestPost::class, 'mysql');
        $sql = $table->toSQL();
        
        $this->assertStringContainsString('`users`', $sql);
        $this->assertStringContainsString('fk_post_user', $sql);
    }

    public function testPropertyLevelFK() {
        $table = AttributeTableBuilder::build(TestPost::class, 'mysql');
        $sql = $table->toSQL();
        
        $this->assertStringContainsString('foreign key (`user-id`) references `users` (`id`)', $sql);
        $this->assertStringContainsString('on update cascade on delete cascade', $sql);
    }

    public function testClassLevelFKWithColumns() {
        $table = AttributeTableBuilder::build(TestOrderItem::class, 'mysql');
        $sql = $table->toSQL();
        
        $this->assertStringContainsString('fk_item_order', $sql);
        $this->assertStringContainsString('fk_item_product', $sql);
        $this->assertStringContainsString('references `orders`', $sql);
        $this->assertStringContainsString('references `products`', $sql);
    }

    public function testCompositeForeignKey() {
        $table = AttributeTableBuilder::build(TestCompositeFK::class, 'mysql');
        $sql = $table->toSQL();
        
        $this->assertStringContainsString('fk_composite', $sql);
        $this->assertStringContainsString('references `tenant_users`', $sql);
        $this->assertStringContainsString('(`tenant_id`, `user_id`)', $sql);
    }

    public function testDefaultFKName() {
        $fk = new ForeignKey(table: 'users', column: 'id');
        $this->assertNull($fk->name);
    }

    public function testDefaultOnUpdateOnDelete() {
        $fk = new ForeignKey(table: 'users', column: 'id');
        $this->assertEquals('set null', $fk->onUpdate);
        $this->assertEquals('set null', $fk->onDelete);
    }
}
