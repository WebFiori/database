<?php
namespace WebFiori\Database\Attributes;

use Attribute;

/**
 * Defines a one-to-many relationship.
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
class HasMany {
    public function __construct(
        public string $entity,
        public string $foreignKey,
        public string $property,
        public ?string $localKey = null,
        public ?string $table = null
    ) {}
}
