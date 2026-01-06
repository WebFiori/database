<?php

use WebFiori\Database\Database;
use WebFiori\Database\Schema\AbstractMigration;

/**
 * Migration to add a unique index on the email column.
 */
class AddEmailIndexMigration extends AbstractMigration {
    public function down(Database $db): void {
        $db->raw("ALTER TABLE users DROP INDEX idx_users_email")->execute();
    }

    public function getDependencies(): array {
        return ['CreateUsersTableMigration'];
    }

    public function up(Database $db): void {
        $db->raw("ALTER TABLE users ADD UNIQUE INDEX idx_users_email (email)")->execute();
    }
}
