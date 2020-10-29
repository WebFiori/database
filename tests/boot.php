
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
require_once $rootDir.'src'.$DS.'webfiori'.$DS.'database'.$DS.'AbstractQuery.php';
require_once $rootDir.'src'.$DS.'webfiori'.$DS.'database'.$DS.'Table.php';
require_once $rootDir.'src'.$DS.'webfiori'.$DS.'database'.$DS.'ForeignKey.php';
require_once $rootDir.'src'.$DS.'webfiori'.$DS.'database'.$DS.'Column.php';
require_once $rootDir.'src'.$DS.'webfiori'.$DS.'database'.$DS.'Connection.php';
require_once $rootDir.'src'.$DS.'webfiori'.$DS.'database'.$DS.'ConnectionInfo.php';
require_once $rootDir.'src'.$DS.'webfiori'.$DS.'database'.$DS.'DatabaseException.php';
require_once $rootDir.'src'.$DS.'webfiori'.$DS.'database'.$DS.'Database.php';
require_once $rootDir.'src'.$DS.'webfiori'.$DS.'database'.$DS.'Expression.php';
require_once $rootDir.'src'.$DS.'webfiori'.$DS.'database'.$DS.'Condition.php';
require_once $rootDir.'src'.$DS.'webfiori'.$DS.'database'.$DS.'EntityMapper.php';
require_once $rootDir.'src'.$DS.'webfiori'.$DS.'database'.$DS.'WhereExpression.php';
require_once $rootDir.'src'.$DS.'webfiori'.$DS.'database'.$DS.'ResultSet.php';
require_once $rootDir.'src'.$DS.'webfiori'.$DS.'database'.$DS.'JoinTable.php';
require_once $rootDir.'src'.$DS.'webfiori'.$DS.'database'.$DS.'SelectExpression.php';

require_once $rootDir.'src'.$DS.'webfiori'.$DS.'database'.$DS.'mysql'.$DS.'MySQLColumn.php';
require_once $rootDir.'src'.$DS.'webfiori'.$DS.'database'.$DS.'mysql'.$DS.'MySQLTable.php';
require_once $rootDir.'src'.$DS.'webfiori'.$DS.'database'.$DS.'mysql'.$DS.'MySQLQuery.php';
require_once $rootDir.'src'.$DS.'webfiori'.$DS.'database'.$DS.'mysql'.$DS.'MySQLConnection.php';

require_once $rootDir.'tests'.$DS.'mysql'.$DS.'MySQLTestSchema.php';

use webfiori\database\ConnectionInfo;
use webfiori\database\mysql\MySQLConnection;
use webfiori\database\tests\MySQLTestSchema;

register_shutdown_function(function()
{
    echo "Dropping test tables...\n";
    $connInfo = new ConnectionInfo('mysql','root', '123456', 'testing_db');
    $conn = new MySQLConnection($connInfo);
    $mysqlSchema = new MySQLTestSchema();
    $mysqlSchema->setConnection($conn);

    
    try{
        $mysqlSchema->table('users_privileges')->drop()->execute();
        $mysqlSchema->table('users_tasks')->drop()->execute();
        $mysqlSchema->table('profile_pics')->drop()->execute();
        $mysqlSchema->table('users')->drop()->execute();
    } catch (Exception $ex) {
        echo $ex->getMessage()."\n";
    }
    
    echo "Finished .\n";
});