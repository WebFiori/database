<?php



//Create Blueprint of second table.
$database->createBlueprint('user_bookmarks')->addColumns([
    'id' => [
        ColOption::TYPE => DataType::INT,
        ColOption::SIZE => 6
    ],
    'title' => [
        ColOption::TYPE => DataType::VARCHAR,
        ColOption::SIZE => 128,
        ColOption::DEFAULT => 'New Bookmark'
    ],
    'url' => [
        ColOption::TYPE => DataType::VARCHAR,
        ColOption::SIZE => 256
    ],
    'bookmarked-on' => [
        ColOption::TYPE => DataType::TIMESTAMP,
        ColOption::DEFAULT => 'current_timestamp'
    ],
    'user_id' => [
        ColOption::TYPE => DataTypes::INT,
        ColOption::SIZE => 5
    ],
])->addReference('users_information', [
    'user-id' => 'id'
], 'user_id_fk', 'cascade', 'restrict');
