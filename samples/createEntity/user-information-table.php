<?php

$database->createBlueprint('users_information')->addColumns([
    'id' => [
        ColOption::TYPE => DataType::INT,
        ColOption::SIZE => 5,
        ColOption::PRIMARY => true,
        ColOption::AUTO_INCREMENT => true
    ],
    'first-name' => [
        ColOption::TYPE => DataType::VARCHAR,
        ColOption::SIZE => 15
    ],
    'last-name' => [
        ColOption::TYPE => DataType::VARCHAR,
        ColOption::SIZE => 15
    ],
    'email' => [
        ColOption::TYPE => DataType::VARCHAR,
        ColOption::SIZE => 128
    ]
]);
