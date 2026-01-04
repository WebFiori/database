<?php
namespace WebFiori\Database\Repository;

/**
 * Represents a page of results from a paginated query.
 * 
 * @template T
 */
class Page {
    private int $currentPage;
    /** @var T[] */
    private array $items;
    private int $perPage;
    private int $totalItems;

    /**
     * @param T[] $items
     */
    public function __construct(array $items, int $currentPage, int $perPage, int $totalItems) {
        $this->items = $items;
        $this->currentPage = $currentPage;
        $this->perPage = $perPage;
        $this->totalItems = $totalItems;
    }

    public function getCurrentPage(): int {
        return $this->currentPage;
    }

    /** @return T[] */
    public function getItems(): array {
        return $this->items;
    }

    public function getNextPage(): ?int {
        return $this->hasNextPage() ? $this->currentPage + 1 : null;
    }

    public function getPerPage(): int {
        return $this->perPage;
    }

    public function getPreviousPage(): ?int {
        return $this->hasPreviousPage() ? $this->currentPage - 1 : null;
    }

    public function getTotalItems(): int {
        return $this->totalItems;
    }

    public function getTotalPages(): int {
        return (int) ceil($this->totalItems / $this->perPage);
    }

    public function hasNextPage(): bool {
        return $this->currentPage < $this->getTotalPages();
    }

    public function hasPreviousPage(): bool {
        return $this->currentPage > 1;
    }
}
