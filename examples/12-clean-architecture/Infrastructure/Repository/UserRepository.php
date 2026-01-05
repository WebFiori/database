<?php

namespace Infrastructure\Repository;

use Domain\User;
use WebFiori\Database\Database;
use WebFiori\Database\Repository\AbstractRepository;

/**
 * Repository implementation using AbstractRepository
 */
class UserRepository extends AbstractRepository {
    protected function getTableName(): string {
        return 'users';
    }

    protected function getIdField(): string {
        return 'id';
    }

    protected function toEntity(array $row): User {
        return new User(
            (int) $row['id'],
            $row['name'],
            $row['email'],
            (int) $row['age']
        );
    }

    protected function toArray(object $entity): array {
        return [
            'id' => $entity->id,
            'name' => $entity->name,
            'email' => $entity->email,
            'age' => $entity->age
        ];
    }

    /** @return User[] */
    public function findByAge(int $minAge): array {
        $result = $this->getDatabase()->table($this->getTableName())
            ->select()
            ->where('age', $minAge, '>=')
            ->execute();

        return array_map(fn($row) => $this->toEntity($row), $result->fetchAll());
    }
}
