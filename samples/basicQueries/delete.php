<?php
require_once '../mysql-db.php';

$postToDelete = 'Post #0';

$resultSet = $database->table('posts')
        ->select(['id'])
        ->where('title', $postToDelete)
        ->execute();

if ($resultSet->getRowsCount() >= 1) {
    echo "Deleting Post With Title '$postToDelete' ...<br>";
    $record = $resultSet->getRows()[0];
    $database->delete()->where('id', $record['id'])->execute();
} else {
    echo "No Post With Title '$postToDelete'<br>";
}

$resultSet2 = $database->table('posts')
        ->select()
        ->execute();

displayResult($resultSet2);
