<?php
namespace Domain;

/**
 * Domain entity - pure PHP, no framework dependencies
 */
class User {
    public function __construct(
        public ?int $id,
        public string $name,
        public string $email,
        public int $age
    ) {
    }
}
