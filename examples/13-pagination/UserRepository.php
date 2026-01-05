<?php

require_once __DIR__.'/User.php';

use WebFiori\Database\Repository\AbstractRepository;

class UserRepository extends AbstractRepository {
    protected function getTableName(): string {
        return 'users';
    }

    protected function getIdField(): string {
        return 'id';
    }

    protected function toEntity(array $row): object {
        $user = new User();
        $user->id = (int) $row['id'];
        $user->name = $row['name'];
        $user->email = $row['email'];
        $user->age = (int) $row['age'];
        return $user;
    }

    protected function toArray(object $entity): array {
        return [
            'id' => $entity->id,
            'name' => $entity->name,
            'email' => $entity->email,
            'age' => $entity->age
        ];
    }
}
