<?php

require_once '../../vendor/autoload.php';
require_once __DIR__.'/Domain/User.php';
require_once __DIR__.'/Infrastructure/Schema/UserTable.php';
require_once __DIR__.'/Infrastructure/Repository/UserRepository.php';

use Domain\User;
use Infrastructure\Repository\UserRepository;
use Infrastructure\Schema\UserTable;
use WebFiori\Database\Attributes\AttributeTableBuilder;
use WebFiori\Database\ConnectionInfo;
use WebFiori\Database\Database;

const SEP = "────────────────────────────────────────────────────────────────────\n";

echo "=== WebFiori Database Clean Architecture Example ===\n\n";

echo "Architecture layers:\n";
echo "  - Domain: Pure entities (User.php)\n";
echo "  - Infrastructure/Schema: Table definitions with attributes (UserTable.php)\n";
echo "  - Infrastructure/Repository: Data access with AbstractRepository\n\n";

try {
    $connection = new ConnectionInfo('mysql', 'root', '123456', 'testing_db');
    $database = new Database($connection);

    echo SEP;
    echo "1. Building Table from Attributes:\n";

    $table = AttributeTableBuilder::build(UserTable::class, 'mysql');
    echo "   ✓ Table blueprint built from UserTable attributes\n";
    echo "   Columns: ".implode(', ', array_keys($table->getCols()))."\n\n";

    echo SEP;
    echo "2. Creating Table:\n";
    $database->addTable($table);
    $database->table('users')->drop(true)->execute();
    $database->createTables();
    echo "   ✓ Users table created\n\n";

    echo SEP;
    echo "3. Using Repository (extends AbstractRepository):\n";
    $userRepo = new UserRepository($database);
    echo "   ✓ UserRepository created\n\n";

    echo SEP;
    echo "4. Saving Domain Entities:\n";
    $users = [
        new User(null, 'Ahmed Ali', 'ahmed@example.com', 28),
        new User(null, 'Sara Hassan', 'sara@example.com', 35),
        new User(null, 'Omar Khalil', 'omar@example.com', 22)
    ];

    foreach ($users as $user) {
        $userRepo->save($user);
        echo "   ✓ Saved: {$user->name}\n";
    }
    echo "\n";

    echo SEP;
    echo "5. Repository Operations:\n";

    $all = $userRepo->findAll();
    echo "   All users (".count($all)."):\n";

    foreach ($all as $u) {
        echo "   - {$u->name} ({$u->email}) - Age: {$u->age}\n";
    }

    $user = $userRepo->findById(1);
    echo "\n   Find by ID 1: {$user->name}\n";

    $adults = $userRepo->findByAge(25);
    echo "\n   Users age >= 25 (".count($adults)."):\n";

    foreach ($adults as $u) {
        echo "   - {$u->name} (Age: {$u->age})\n";
    }

    $page = $userRepo->paginate(1, 2);
    echo "\n   Page 1 (2 per page): {$page->getTotalItems()} total, {$page->getTotalPages()} pages\n";

    echo "\n   Total count: ".$userRepo->count()."\n\n";

    echo SEP;
    echo "6. Cleanup:\n";
    $database->table('users')->drop()->execute();
    echo "   ✓ Table dropped\n";
} catch (Exception $e) {
    echo "✗ Error: ".$e->getMessage()."\n";
    try {
        $database->table('users')->drop(true)->execute();
    } catch (Exception $cleanupError) {
    }
}

echo "\n".SEP;
echo "=== Example Complete ===\n";
