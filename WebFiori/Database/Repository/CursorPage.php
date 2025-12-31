<?php

namespace WebFiori\Database\Repository;

/**
 * Represents a cursor-based pagination result.
 * 
 * @template T
 */
class CursorPage {
    /** @var T[] */
    private array $items;
    private ?string $nextCursor;
    private ?string $previousCursor;
    private bool $hasMore;
    
    /**
     * @param T[] $items
     */
    public function __construct(array $items, ?string $nextCursor, ?string $previousCursor, bool $hasMore) {
        $this->items = $items;
        $this->nextCursor = $nextCursor;
        $this->previousCursor = $previousCursor;
        $this->hasMore = $hasMore;
    }
    
    /** @return T[] */
    public function getItems(): array {
        return $this->items;
    }
    
    public function getNextCursor(): ?string {
        return $this->nextCursor;
    }
    
    public function getPreviousCursor(): ?string {
        return $this->previousCursor;
    }
    
    public function hasMore(): bool {
        return $this->hasMore;
    }
}
