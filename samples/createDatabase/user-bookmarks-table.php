<?php 

//Create Blueprint of second table.
$database->createBlueprint('user_bookmarks')->addColumns([
    'id' => [
        'type' => 'int',
        'size' => 6
    ],
    'title' => [
        'type' => 'varchar',
        'size' => 128,
        'default' => 'New Bookmark'
    ],
    'url' => [
        'type' => 'varchar',
        'size' => 256
    ],
    'bookmarked-on' => [
        'type' => 'timestamp',
        'default' => 'current_timestamp'
    ],
    'user_id' => [
        'type' => 'int',
        'size' => 5
    ],
])->addReference('users_information', [
    'user-id' => 'id'
], 'user_id_fk', 'cascade', 'restrict');