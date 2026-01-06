<?php

namespace WebFiori\Tests\Database\Schema;

use WebFiori\Database\Database;
use WebFiori\Database\Schema\AbstractSeeder;

class DevOnlySeeder extends AbstractSeeder {
    public function getEnvironments(): array {
        return ['dev'];
    }
    public function run(Database $db): void {}
}
