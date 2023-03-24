<?php
require_once '../mysql-db.php';

$database = getDatabaseInstance();

$resultSet = $database->table('posts')
        ->select()
        ->execute();

displayResult($resultSet);
