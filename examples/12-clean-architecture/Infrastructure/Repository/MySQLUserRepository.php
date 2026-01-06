<?php

namespace Infrastructure\Repository;

use Domain\User;
use Domain\UserRepositoryInterface;
use WebFiori\Database\Database;
use WebFiori\Database\Repository\AbstractRepository;

/**
 * Infrastructure implementation - depends on WebFiori Database
 */
class MySQLUserRepository extends AbstractRepository implements UserRepositoryInterface {
    protected function getTableName(): string { return 'users'; }
    protected function getIdField(): string { return 'id'; }

    protected function toEntity(array $row): object {
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

    public function findById(mixed $id): ?User {
        return parent::findById($id);
    }

    public function findAll(): array {
        return parent::findAll();
    }

    public function delete(mixed $id): void {
        $this->deleteById($id);
    }
}
