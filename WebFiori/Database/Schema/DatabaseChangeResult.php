<?php
/**
 * This file is licensed under MIT License.
 * 
 * Copyright (c) 2026-present WebFiori Framework
 * 
 * For more information on the license, please visit: 
 * https://github.com/WebFiori/.github/blob/main/LICENSE
 * 
 */
namespace WebFiori\Database\Schema;

use Countable;
use IteratorAggregate;
use ArrayIterator;
use Traversable;
use WebFiori\Database\ConnectionInfo;

/**
 * Result of applying database changes (migrations and seeders).
 * 
 * This class provides detailed information about what happened during
 * a call to SchemaRunner::apply(). It implements Countable and IteratorAggregate
 * for backward compatibility - count() returns applied count, foreach iterates applied.
 */
class DatabaseChangeResult implements Countable, IteratorAggregate {
    /**
     * @var array<DatabaseChange> Changes that were successfully applied
     */
    private array $applied = [];
    
    /**
     * @var array<array{change: DatabaseChange, reason: string}> Changes that were skipped
     */
    private array $skipped = [];
    
    /**
     * @var array<array{change: DatabaseChange, error: \Throwable}> Changes that failed
     */
    private array $failed = [];
    
    /**
     * @var float Total execution time in milliseconds
     */
    private float $totalTimeMs = 0;

    /**
     * @var ConnectionInfo|null Connection info for the database changes were applied to
     */
    private ?ConnectionInfo $connectionInfo = null;

    /**
     * Add an applied change.
     */
    public function addApplied(DatabaseChange $change): void {
        $this->applied[] = $change;
    }

    /**
     * Add a skipped change with reason.
     */
    public function addSkipped(DatabaseChange $change, string $reason): void {
        $this->skipped[] = ['change' => $change, 'reason' => $reason];
    }

    /**
     * Add a failed change with error.
     */
    public function addFailed(DatabaseChange $change, \Throwable $error): void {
        $this->failed[] = ['change' => $change, 'error' => $error];
    }

    /**
     * Get all applied changes.
     * 
     * @return array<DatabaseChange>
     */
    public function getApplied(): array {
        return $this->applied;
    }

    /**
     * Get all skipped changes with reasons.
     * 
     * @return array<array{change: DatabaseChange, reason: string}>
     */
    public function getSkipped(): array {
        return $this->skipped;
    }

    /**
     * Get all failed changes with errors.
     * 
     * @return array<array{change: DatabaseChange, error: \Throwable}>
     */
    public function getFailed(): array {
        return $this->failed;
    }

    /**
     * Set total execution time.
     */
    public function setTotalTime(float $timeMs): void {
        $this->totalTimeMs = $timeMs;
    }

    /**
     * Get total execution time in milliseconds.
     */
    public function getTotalTime(): float {
        return $this->totalTimeMs;
    }

    /**
     * Check if all changes were successful (none failed).
     */
    public function isSuccessful(): bool {
        return empty($this->failed);
    }

    /**
     * Set the connection info for the database changes were applied to.
     */
    public function setConnectionInfo(ConnectionInfo $connectionInfo): void {
        $this->connectionInfo = $connectionInfo;
    }

    /**
     * Get the connection info for the database changes were applied to.
     */
    public function getConnectionInfo(): ?ConnectionInfo {
        return $this->connectionInfo;
    }

    /**
     * Get the database name changes were applied to.
     */
    public function getDatabaseName(): ?string {
        return $this->connectionInfo?->getDBName();
    }

    /**
     * Get count of applied changes (Countable interface).
     */
    public function count(): int {
        return count($this->applied);
    }

    /**
     * Iterate over applied changes (IteratorAggregate interface).
     */
    public function getIterator(): Traversable {
        return new ArrayIterator($this->applied);
    }
}
