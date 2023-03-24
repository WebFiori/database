<?php

use webfiori\database\ConnectionInfo;
use webfiori\database\Database;
use webfiori\database\DatabaseException;

try {
    //This assumes that MySQL is installed on locahost
    //and root password is set to '123456' 
    //and there is a schema with name 'testing_db'
    $connection = new ConnectionInfo('mysql', 'root', '123456', 'testing_db');
    $database = new Database($connection);
} catch (DatabaseException $ex) {
    die($ex->getCode().' - '.$ex->getMessage());
}
