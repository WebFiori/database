<?php

use webfiori\database\FK;
use webfiori\database\mysql\MySQLTable;

class UserBookmarksTable extends MySQLTable {
    public function __construct() {
        parent::__construct('user_bookmarks');

        $this->addColumns([
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
                'size' => 5,
                'fk' => [
                    'table' => UserInformation::class,
                    'name' => 'user_id_fk',
                    'col' => 'id',
                    'on-update' => FK::CASCADE,
                    'on-delete' => FK::RESTRICT
                ]
            ],
        ]);
    }
}
