<?php

use WebFiori\Database\ColOption;
use WebFiori\Database\DataType;
use WebFiori\Database\FK;
use WebFiori\Database\mysql\MySQLTable;

class UserBookmarksTable extends MySQLTable {
    public function __construct() {
        parent::__construct('user_bookmarks');

        $this->addColumns([
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
                ColOption::TYPE => 'varchar',
                ColOption::SIZE => 256
            ],
            'bookmarked-on' => [
                ColOption::TYPE => DataType::TIMESTAMP,
                ColOption::DEFAULT => 'current_timestamp'
            ],
            'user_id' => [
                ColOption::TYPE => DataType::INT,
                ColOption::SIZE => 5,
                ColOption::FK => [
                    ColOption::FK_TABLE => UserInformation::class,
                    ColOption::FK_NAME => 'user_id_fk',
                    ColOption::FK_COL => 'id',
                    ColOption::FK_ON_UPDATE => FK::CASCADE,
                    ColOption::FK_ON_DELETE => FK::RESTRICT
                ]
            ],
        ]);
    }
}
