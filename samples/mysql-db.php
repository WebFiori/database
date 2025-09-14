<?php
/**
 * This file is used to initiate a connection to MySQL database.
 * 
 */
ini_set('display_startup_errors', 1);
ini_set('display_errors', 1);
error_reporting(-1);

require_once '../../vendor/autoload.php';

use WebFiori\Database\ConnectionInfo;
use WebFiori\Database\Database;
use WebFiori\Database\DatabaseException;
use WebFiori\Database\ResultSet;
use WebFiori\Database\ColOption;

function getDatabaseInstance() : Database {
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

    return $database;
}
/**
 * Print the result set as HTML table.
 * 
 * @param ResultSet $result
 */
function displayResult(ResultSet $result) {
    echo '<table border=1>';

    if ($result->getRows() > 0) {
        $headers = array_keys($result->getRows()[0]);
        echo '<tr>';

        foreach ($headers as $headerTxt) {
            echo '<th>'.$headerTxt.'</th>';
        }
        echo '</tr>';

        foreach ($result as $record) {
            echo '<tr>';

            foreach ($headers as $header) {
                echo '<td>'.$record[$header].'</td>';
            }
            echo '</tr>';
        }
    }
    echo '</table>';
}
