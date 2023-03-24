<?php

require_once '../mysql-db.php';

$database = getDatabaseInstance();

//First create the blueprint
require_once 'user-information-table.php';
require_once 'user-bookmarks-table.php';

//Build the query
$database->createTables();
echo '<pre>'.$database->getLastQuery().'</pre>';

//Execute
$database->execute();

echo 'Database tables created.';