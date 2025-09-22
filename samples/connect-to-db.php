<?php

use WebFiori\Database\ConnectionInfo;
use WebFiori\Database\Database;

//This assumes that MySQL is installed on locahost
//and root password is set to '123456' 
//and there is a schema with name 'testing_db'
$connection = new ConnectionInfo('mysql', 'root', '123456', 'testing_db');
$database = new Database($connection);
