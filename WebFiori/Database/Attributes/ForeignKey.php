<?php

namespace WebFiori\Database\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
class ForeignKey {
    public function __construct(
        public string $table,
        public string $column,
        public ?string $name = null,
        public string $onUpdate = 'set null',
        public string $onDelete = 'set null'
    ) {}
}
