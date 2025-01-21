
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

require_once $rootDir.$DS.'webfiori'.$DS.'database'.$DS.'DataType.php';
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
require_once $rootDir.$DS.'webfiori'.$DS.'database'.$DS.'RecordMapper.php';

require_once $rootDir.$DS.'webfiori'.$DS.'database'.$DS.'mysql'.$DS.'MySQLColumn.php';
require_once $rootDir.$DS.'webfiori'.$DS.'database'.$DS.'mysql'.$DS.'MySQLTable.php';
require_once $rootDir.$DS.'webfiori'.$DS.'database'.$DS.'mysql'.$DS.'MySQLQuery.php';
require_once $rootDir.$DS.'webfiori'.$DS.'database'.$DS.'mysql'.$DS.'MySQLConnection.php';

require_once $rootDir.'tests'.$DS.'webfiori'.$DS.'database'.$DS.'tests'.$DS.'mysql'.$DS.'MySQLTestSchema.php';

require_once $rootDir.$DS.'webfiori'.$DS.'database'.$DS.'mssql'.$DS.'MSSQLColumn.php';
require_once $rootDir.$DS.'webfiori'.$DS.'database'.$DS.'mssql'.$DS.'MSSQLTable.php';
require_once $rootDir.$DS.'webfiori'.$DS.'database'.$DS.'mssql'.$DS.'MSSQLQuery.php';
require_once $rootDir.$DS.'webfiori'.$DS.'database'.$DS.'mssql'.$DS.'MSSQLConnection.php';

require_once $rootDir.'tests'.$DS.'webfiori'.$DS.'database'.$DS.'tests'.$DS.'mssql'.$DS.'MSSQLTestSchema.php';
require_once $rootDir.'tests'.$DS.'webfiori'.$DS.'database'.$DS.'tests'.$DS.'common'.$DS.'HelloTable.php';
require_once $rootDir.'tests'.$DS.'webfiori'.$DS.'database'.$DS.'tests'.$DS.'migrations'.$DS.'Mig00.php';
require_once $rootDir.'tests'.$DS.'webfiori'.$DS.'database'.$DS.'tests'.$DS.'migrations'.$DS.'Mig01.php';
require_once $rootDir.'tests'.$DS.'webfiori'.$DS.'database'.$DS.'tests'.$DS.'migrations'.$DS.'NotMig.php';
require_once $rootDir.'tests'.$DS.'webfiori'.$DS.'User.php';

$jsonLibPath = $rootDir.'vendor'.$DS.'webfiori'.$DS.'jsonx'.$DS.'webfiori'.$DS.'json';
require_once $jsonLibPath.$DS.'JsonI.php';
require_once $jsonLibPath.$DS.'Json.php';
require_once $jsonLibPath.$DS.'JsonConverter.php';
require_once $jsonLibPath.$DS.'CaseConverter.php';
require_once $jsonLibPath.$DS.'JsonTypes.php';
require_once $jsonLibPath.$DS.'Property.php';

use webfiori\database\ConnectionInfo;
use webfiori\database\mysql\MySQLConnection;
use webfiori\database\tests\mysql\MySQLTestSchema;
use webfiori\database\mssql\MSSQLConnection;
use webfiori\database\tests\mssql\MSSQLTestSchema;
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
            $mssqlConnInfo = new ConnectionInfo('mssql','sa', '1234567890@Eu', 'testing_db', SQL_SERVER_HOST);
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