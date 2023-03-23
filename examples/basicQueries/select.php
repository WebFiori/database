<?php
require_once '../mysql-db.php';

$resultSet = $database->table('posts')
        ->select()
        ->execute();

displayResult($resultSet);
