<?php

//Create Blueprint of first table.
$database->createBlueprint('users_information')->addColumns([
    'id' => [
        'type' => 'int',
        'size' => 5,
        'primary' => true,
        'auto-inc' => true
    ],
    'first-name' => [
        'type' => 'varchar',
        'size' => 15
    ],
    'last-name' => [
        'type' => 'varchar',
        'size' => 15
    ],
    'email' => [
        'type' => 'varchar',
        'size' => 128
    ]
]);
