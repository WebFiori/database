<?php

namespace Domain;

/**
 * Repository interface - defines contract, no implementation details
 */
interface UserRepositoryInterface {
    public function findById(mixed $id): ?User;
    public function findAll(): array;
    public function save(User $user): void;
    public function delete(mixed $id): void;
}
