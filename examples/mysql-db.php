<?php
/**
 * This file is used to initiate a connection to MySQL database.
 * 
 * In addition to that, it will attempt to create a table called 'posts'
 * based on the file "mysql-tables.sql"
 * 
 */
ini_set('display_startup_errors', 1);
ini_set('display_errors', 1);
error_reporting(-1);

require_once '../../vendor/autoload.php';

use webfiori\database\ConnectionInfo;
use webfiori\database\Database;
use webfiori\database\DatabaseException;

$connection = new ConnectionInfo('mysql', 'root', '123456', 'testing_db');
$database = new Database($connection);

try {
    //Do basic select to check if tables exist.
    //If exception is thrown, it means database is not initialized.
    $database->table('posts')->select()->execute();
} catch (DatabaseException $ex) {
    if ($ex->getCode() == 1146) {
        die('Please run the query "mysql-tables.sql" to execute the examples.');
    }
}

