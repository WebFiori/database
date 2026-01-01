<?php

namespace WebFiori\Database\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
class Column {
    public function __construct(
        public string $type,
        public ?int $size = null,
        public ?int $scale = null,
        public bool $primary = false,
        public bool $unique = false,
        public bool $nullable = false,
        public bool $autoIncrement = false,
        public bool $identity = false,
        public bool $autoUpdate = false,
        public mixed $default = null,
        public ?string $name = null,
        public ?string $comment = null,
        public mixed $callback = null
    ) {}
}
