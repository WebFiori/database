
<?php
/* 
 * The MIT License
 *
 * Copyright 2018 Ibrahim.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */
ini_set('display_startup_errors', 1);
ini_set('display_errors', 1);
error_reporting(-1);
$stderr = fopen('php://stderr', 'w');
$testsDirName = 'tests';
$rootDir = substr(__DIR__, 0, strlen(__DIR__) - strlen($testsDirName));
$DS = DIRECTORY_SEPARATOR;
$rootDirTrimmed = trim($rootDir,'/\\');
fwrite($stderr,'Include Path: \''.get_include_path().'\''."\n");

if (explode($DS, $rootDirTrimmed)[0] == 'home') {
    //linux.
    $rootDir = $DS.$rootDirTrimmed.$DS;
} else {
    $rootDir = $rootDirTrimmed.$DS;
}
define('ROOT', $rootDir);
fwrite($stderr,'Root Directory: \''.$rootDir.'\'.'."\n");

require_once $rootDir.$DS.'webfiori'.$DS.'database'.$DS.'AbstractQuery.php';
require_once $rootDir.$DS.'webfiori'.$DS.'database'.$DS.'Table.php';
require_once $rootDir.$DS.'webfiori'.$DS.'database'.$DS.'ForeignKey.php';
require_once $rootDir.$DS.'webfiori'.$DS.'database'.$DS.'Column.php';
require_once $rootDir.$DS.'webfiori'.$DS.'database'.$DS.'Connection.php';
require_once $rootDir.$DS.'webfiori'.$DS.'database'.$DS.'ConnectionInfo.php';
require_once $rootDir.$DS.'webfiori'.$DS.'database'.$DS.'DatabaseException.php';
require_once $rootDir.$DS.'webfiori'.$DS.'database'.$DS.'Database.php';
require_once $rootDir.$DS.'webfiori'.$DS.'database'.$DS.'Expression.php';
require_once $rootDir.$DS.'webfiori'.$DS.'database'.$DS.'Condition.php';
require_once $rootDir.$DS.'webfiori'.$DS.'database'.$DS.'EntityMapper.php';
require_once $rootDir.$DS.'webfiori'.$DS.'database'.$DS.'WhereExpression.php';
require_once $rootDir.$DS.'webfiori'.$DS.'database'.$DS.'ResultSet.php';
require_once $rootDir.$DS.'webfiori'.$DS.'database'.$DS.'JoinTable.php';
require_once $rootDir.$DS.'webfiori'.$DS.'database'.$DS.'SelectExpression.php';
require_once $rootDir.$DS.'webfiori'.$DS.'database'.$DS.'DateTimeValidator.php';
require_once $rootDir.$DS.'webfiori'.$DS.'database'.$DS.'ColumnFactory.php';

require_once $rootDir.$DS.'webfiori'.$DS.'database'.$DS.'mysql'.$DS.'MySQLColumn.php';
require_once $rootDir.$DS.'webfiori'.$DS.'database'.$DS.'mysql'.$DS.'MySQLTable.php';
require_once $rootDir.$DS.'webfiori'.$DS.'database'.$DS.'mysql'.$DS.'MySQLQuery.php';
require_once $rootDir.$DS.'webfiori'.$DS.'database'.$DS.'mysql'.$DS.'MySQLConnection.php';

require_once $rootDir.'tests'.$DS.'mysql'.$DS.'MySQLTestSchema.php';

require_once $rootDir.$DS.'webfiori'.$DS.'database'.$DS.'mssql'.$DS.'MSSQLColumn.php';
require_once $rootDir.$DS.'webfiori'.$DS.'database'.$DS.'mssql'.$DS.'MSSQLTable.php';
require_once $rootDir.$DS.'webfiori'.$DS.'database'.$DS.'mssql'.$DS.'MSSQLQuery.php';
require_once $rootDir.$DS.'webfiori'.$DS.'database'.$DS.'mssql'.$DS.'MSSQLConnection.php';

require_once $rootDir.'tests'.$DS.'mssql'.$DS.'MSSQLTestSchema.php';
use webfiori\database\ConnectionInfo;
use webfiori\database\mysql\MySQLConnection;
use webfiori\database\tests\MySQLTestSchema;
use webfiori\database\mssql\MSSQLConnection;
use mssql\MSSQLTestSchema;
register_shutdown_function(function()
{
    $tablesToDrop = [
        'users_privileges',
        'users_tasks',
        'profile_pics',
        'users',
    ];
    echo "Dropping test tables from MySQL Server...\n";
    try {
        $connInfo = new ConnectionInfo('mysql','root', '123456', 'testing_db', '127.0.0.1');
        $conn = new MySQLConnection($connInfo);
        $mysqlSchema = new MySQLTestSchema();
        $mysqlSchema->setConnection($conn);
        foreach ($tablesToDrop as $tblName) {
            try{
                $mysqlSchema->table($tblName)->drop();
                echo $mysqlSchema->getLastQuery()."\n";
                $mysqlSchema->execute();
            } catch (Exception $ex) {
                echo $ex->getMessage()."\n";
            }
        }
        
    } catch (Exception $ex) {
        echo "An exception is thrown.\n";
        echo $ex->getMessage()."\n";
    }
    if (PHP_MAJOR_VERSION == 5) {
       echo ('PHP 5 has no MSSQL driver in selected setup.');
    } else {
        echo "Dropping test tables from MSSQL Server...\n";
        try{
            $mssqlConnInfo = new ConnectionInfo('mssql','sa', '1234567890', 'testing_db', 'localhost');
            $mssqlConn = new MSSQLConnection($mssqlConnInfo);
            $mssqlSchema = new MSSQLTestSchema();
            $mssqlSchema->setConnection($mssqlConn);
            foreach ($tablesToDrop as $tblName) {
                try{
                    $mssqlSchema->table($tblName)->drop();
                    echo $mssqlSchema->getLastQuery()."\n";
                    $mssqlSchema->execute();
                } catch (Exception $ex) {
                    echo $ex->getMessage()."\n";
                }
            }
        } catch (\Exception $ex) {
            echo "An exception is thrown.\n";
            echo $ex->getMessage()."\n";
        }
    }
    echo "Finished .\n";
});