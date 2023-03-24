<?php

require_once '../mysql-db.php';

$database = getDatabaseInstance();

$postToUpdate = 'Post #1';
$newTitle = 'New Post Title';
$newAuthor = 'New Author';

$resultSet = $database->table('posts')
        ->select(['id'])
        ->where('title', $postToUpdate)
        ->execute();

if ($resultSet->getRowsCount() >= 1) {
    echo "Updating Post With Title '$postToUpdate' ...<br>";
    $record = $resultSet->getRows()[0];
    $database->table('posts')->update([
        'author' => $newAuthor,
        'title' => $newTitle
    ])->where('id', $record['id'])->execute();
} else {
    echo "No Post With Title '$postToUpdate'<br>";
}

$resultSet2 = $database->table('posts')
        ->select()
        ->execute();

displayResult($resultSet2);
