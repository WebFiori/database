<?php

//Create Blueprint of first table.
$database->createBlueprint('users_information')->addColumns([
    'id' => [
        ColOption::TYPE => 'int',
        ColOption::SIZE => 5,
        ColOption::PRIMARY => true,
        ColOption::AUTO_INCREMENT => true
    ],
    'first-name' => [
        ColOption::TYPE => 'varchar',
        ColOption::SIZE => 15
    ],
    'last-name' => [
        ColOption::TYPE => 'varchar',
        ColOption::SIZE => 15
    ],
    'email' => [
        ColOption::TYPE => 'varchar',
        ColOption::SIZE => 128
    ]
]);
