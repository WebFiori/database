<?php

namespace WebFiori\Tests\Database\Schema;

use WebFiori\Database\Database;
use WebFiori\Database\Schema\AbstractMigration;

class FailingMigration extends AbstractMigration {
    public function up(Database $db): void {
        throw new \Exception('Migration failed');
    }
    public function down(Database $db): void {}
}
