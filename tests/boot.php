
<?php

ini_set('display_startup_errors', 1);
ini_set('display_errors', 1);
error_reporting(-1);
define('SQL_SERVER_HOST', 'localhost');
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

require_once $rootDir.$DS.'vendor'.$DS.'autoload.php';


use WebFiori\Database\ConnectionInfo;
use WebFiori\Database\MySql\MySQLConnection;
use WebFiori\Database\Tests\MySql\MySQLTestSchema;
use WebFiori\Database\MsSql\MSSQLConnection;
use WebFiori\Database\Tests\MsSql\MSSQLTestSchema;
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
            $mssqlConnInfo = new ConnectionInfo('mssql','sa', '1234567890@Eu', 'testing_db', SQL_SERVER_HOST, 1433, [
                'TrustServerCertificate' => 'true'
            ]);
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