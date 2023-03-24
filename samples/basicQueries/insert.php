<?php
require_once '../mysql-db.php';

$database = getDatabaseInstance();

for ($x = 0 ; $x < 5 ; $x++) {
    $database->table('posts')->insert([
        'title' => 'Post #'.$x,
        'author' => 'Me'
    ])->execute();
}

$resultSet = $database->table('posts')
        ->select()
        ->execute();

displayResult($resultSet);
