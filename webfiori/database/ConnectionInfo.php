<?php
/*
 * The MIT License
 *
 * Copyright 2019, WebFiori Framework.
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
namespace webfiori\database;

/**
 * An entity that can be used to store database connection information. 
 * 
 * The information that can be stored includes:
 * <ul>
 * <li>Database host address.</li>
 * <li>Port number.</li>
 * <li>The username of the user that will be used to access the database.</li>
 * <li>The password of the user.</li>
 * <li>The name of the database.</li>
 * </ul>
 * 
 * In addition to the given ones, the developer can give the connection an 
 * optional name.
 *
 * @author Ibrahim
 * 
 * @version 1.0.1
 */
class ConnectionInfo {
    /**
     * An array that contains supported database drivers.
     * 
     * The array has the following values:
     * <ul>
     * <li>mysql</li>
     * <li>mssql</li>
     * <ul>
     * 
     * @since 1.0
     */
    const SUPPORTED_DATABASES = [
        'mysql',
        'mssql'
    ];
    /**
     * A string that represents the name of the connection.
     * 
     * @var string
     * 
     * @since 1.0 
     */
    private $connectionName;
    /**
     * The name of the database.
     * 
     * @var type 
     * 
     * @since 1.0
     */
    private $dbName;
    /**
     * The name of database driver that will be used to build SQL queries.
     * 
     * @var string
     * 
     * @since 1.0 
     */
    private $dbType;
    /**
     * An array that can have extra information to use for the connection.
     * 
     * @var array 
     * 
     * @since 1.0
     */
    private $extras;
    /**
     * Database host address.
     * 
     * This can be an IP address or a domain name.
     * 
     * @var string 
     */
    private $host;
    /**
     *
     * @var string 
     */
    private $pass;
    /**
     *
     * @var string 
     * 
     * @since 1.0
     */
    private $port;
    /**
     *
     * @var string 
     * 
     * @since 1.0
     */
    private $uName;

    /**
     * Creates new instance of the class.
     * 
     * @param string $databaseType Name of the database at which the connection 
     * is for. Can be 'mysql' or 'mssql'.
     * 
     * @param string $user The username of the user that will be used to access 
     * the database.
     * 
     * @param string $pass The password of the user.
     * 
     * @param string $dbname The name of the database.
     * 
     * @param string $host The address of database host or server name.
     * 
     * @param int $port Port number that will be used to access database server. 
     * In case of 'mysql', default is 3306. In case of 'mssql', default is 1433.
     * 
     * @param array $extras An array that can have extra information at which the 
     * connection might need.
     * 
     * @throws DatabaseException If given database is not supported.
     * 
     * @since 1.0
     */
    public function __construct($databaseType, $user, $pass, $dbname, $host = 'localhost', $port = null, array $extras = []) {
        $this->setUsername($user);
        $this->setPassword($pass);
        $this->setDBName($dbname);

        if ($host === null) {
            $this->setHost('localhost');
        } else {
            $this->setHost($host);
        }
        if ($port === null) {
            if ($databaseType == 'mysql') {
                $this->setPort(3306);
            } else if ($databaseType == 'mssql') {
                $this->setPort(1433);
            }
        } else {
            $this->setPort($port);
        }


        if (!isset($extras['connection-name']) || (isset($extras['connection-name']) && !$this->setName($extras['connection-name']))) {
            $this->setName('New_Connection');
        }

        if (!in_array($databaseType, self::SUPPORTED_DATABASES)) {
            throw new DatabaseException('Database not supported: "'.$databaseType.'".');
        }
        $this->dbType = $databaseType;
        $this->extras = $extras;
    }
    /**
     * Returns the type of the database at which the connection will use.
     * 
     * @return string Database type such as 'mysql' or 'maria-db'.
     * 
     * @since 1.0
     */
    public function getDatabaseType() {
        return $this->dbType;
    }
    /**
     * Returns the name of the database.
     * 
     * @return string A string that represents the name of the database.
     * 
     * @since 1.0
     */
    public function getDBName() {
        return $this->dbName;
    }
    /**
     * Returns an array that contains any extra connection information.
     * 
     * @return array An array that contains any extra connection information.
     * 
     * @since 1.0
     */
    public function getExtars() {
        return $this->extras;
    }
    /**
     * Returns the address of database host.
     * 
     * The host address can be a URL, an IP address or 'localhost' if 
     * the database is hosted in the same server that the framework is 
     * installed in.
     * 
     * @return string A string that represents the address of the host. If 
     * it is not set, the method will return 'localhost' by default.
     * 
     * @since 1.0
     */
    public function getHost() {
        return $this->host;
    }
    /**
     * Returns the name of the connection.
     * 
     * @return string The name of the connection. Default return value is 'New_Connection'.
     * 
     * @since 1.0
     */
    public function getName() {
        return $this->connectionName;
    }
    /**
     * Returns the password of the user that will be used to access the database.
     * 
     * @return string A string that represents the password of the user.
     * 
     * @since 1.0
     */
    public function getPassword() {
        return $this->pass;
    }
    /**
     * Returns database server port number.
     * 
     * @return int Server port number. If it is not set, the method will 
     * return 3306 by default.
     * 
     * @since 1.0
     */
    public function getPort() {
        return $this->port;
    }
    /**
     * Returns username of the user that will be used to access the database.
     * 
     * @return string A string that represents the username.
     * 
     * @since 1.0
     */
    public function getUsername() {
        return $this->uName;
    }
    /**
     * Sets the type of the database.
     * 
     * The value is used to select the correct query builder for the database. 
     * Supported values are:
     * 
     * <ul>
     * <li>mysql</li>
     * </ul>
     * 
     * @param string $type Database type such as 'mysql' or 'maria-db'.
     * 
     * @return boolean If the type is set, the method will return true. Other 
     * than that, the method will return false.
     * 
     * @since 1.0
     */
    public function setDatabaseType($type) {
        $trimmed = trim($type);

        if (strlen($trimmed) > 0 && in_array($trimmed, self::SUPPORTED_DATABASES)) {
            $this->dbType = $trimmed;

            return true;
        }

        return false;
    }
    /**
     * Sets the name of the database.
     * 
     * @param string $name The name of the database.
     * 
     * @since 1.0
     */
    public function setDBName($name) {
        $this->dbName = $name;
    }
    /**
     * Sets extra connection information as an array.
     * 
     * The array should contain any extra information at which the connection 
     * might use.
     * 
     * @param array $array An array that contains any extra connection information.
     * 
     * @since 1.0
     */
    public function setExtras(array $array) {
        $this->extras = $array;
    }
    /**
     * Sets the address of database host.
     * 
     * The host address can be a URL, an IP address or 'localhost' if 
     * the database is hosted in the same server that the framework is 
     * installed in.
     * 
     * @param string $hostAddr The address of database host.
     * 
     * @since 1.0
     */
    public function setHost($hostAddr) {
        $this->host = $hostAddr;
    }
    /**
     * Sets the name of the connection.
     * 
     * @param string $newName The new name. Must be non-empty string.
     * 
     * @since 1.0
     */
    public function setName($newName) {
        $trimmed = trim($newName);

        if (strlen($trimmed) != 0) {
            $this->connectionName = $trimmed;

            return true;
        }

        return false;
    }
    /**
     * Sets the password of the user that will be used to access the database.
     * 
     * @param string $password A string that represents the password of the user.
     * 
     * @since 1.0
     */
    public function setPassword($password) {
        $this->pass = $password;
    }
    /**
     * Sets database server port number.
     * 
     * @param int $portNum Server port number. It will be set only if the 
     * given value is greater than 0.
     * 
     * @since 1.0
     */
    public function setPort($portNum) {
        if ($portNum > 0) {
            $this->port = $portNum;
        }
    }
    /**
     * Sets the username of the user that will be used to access the database.
     * 
     * @param string $user A string that represents the username.
     * 
     * @since 1.0
     */
    public function setUsername($user) {
        $this->uName = $user;
    }
}
