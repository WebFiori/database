<?php
namespace WebFiori\Database\Attributes;

use Attribute;

/**
 * Defines a foreign key constraint and optionally a belongsTo relationship.
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
class ForeignKey {
    public function __construct(
        public string $table,
        public ?string $column = null,
        public array $columns = [],
        public ?string $name = null,
        public string $onUpdate = 'set null',
        public string $onDelete = 'set null',
        public ?string $property = null  // For belongsTo relationship
    ) {
        if ($column !== null && !empty($columns)) {
            throw new InvalidAttributeException(
                "ForeignKey: Use either 'column' or 'columns', not both"
            );
        }
    }

    /**
     * Get columns mapping as array ['localCol' => 'refCol']
     */
    public function getColumnsMap(): array {
        if ($this->column !== null) {
            return [$this->column];
        }
        return $this->columns;
    }
}
