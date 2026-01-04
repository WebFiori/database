<?php

namespace WebFiori\Tests\Database\Schema;

use WebFiori\Database\Database;
use WebFiori\Database\Schema\AbstractMigration;

class SuccessfulMigration extends AbstractMigration {
    public function up(Database $db): void {}
    public function down(Database $db): void {}
}
