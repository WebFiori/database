<?php
/**
 * This file is licensed under MIT License.
 * 
 * Copyright (c) 2019 Ibrahim BinAlshikh
 * 
 * For more information on the license, please visit: 
 * https://github.com/WebFiori/.github/blob/main/LICENSE
 * 
 */
namespace webfiori\database;

use Exception;
use webfiori\database\mssql\MSSQLConnection;
use webfiori\database\mssql\MSSQLQuery;
use webfiori\database\mssql\MSSQLTable;
use webfiori\database\mysql\MySQLConnection;
use webfiori\database\mysql\MySQLQuery;
use webfiori\database\mysql\MySQLTable;
/**
 * A class which is used to represent the structure of the database 
 * (database schema). 
 * In addition to that, the class has methods which is used to build some
 * commonly used SQL queries such as 'create', 'insert' or 'update'.
 * 
 * @author Ibrahim
 * 
 * @version 1.0.2
 */
class Database {
    /**
     * The connection which is used to connect to the database.
     * 
     * @var Connection 
     * 
     * 
     */
    private $connection;
    /**
     * An object that holds database connection information.
     * 
     * @var ConnectionInfo
     * 
     *  
     */
    private $connectionInfo;
    /**
     * An array that holds all generated SQL queries.
     * 
     * @var array
     * 
     *  
     */
    private $queries;

    /**
     * The instance which is used to build database queries.
     * 
     * @var AbstractQuery 
     * 
     * 
     */
    private $queryGenerator;
    /**
     * An associative array that holds all tables.
     * 
     * @var array
     * 
     *  
     */
    private $tablesArr;
    private $lastErr;
    /**
     * Creates new instance of the class.
     * 
     * @param ConnectionInfo|null $connectionInfo An object that holds database 
     * connection information.
     * 
     * @throws DatabaseException The method will throw an exception if database 
     * driver is not supported.
     * 
     * 
     */
    public function __construct(?ConnectionInfo $connectionInfo) {
        if ($connectionInfo !== null) {
            $this->setConnectionInfo($connectionInfo);
        }
        $this->queries = [];
        $this->tablesArr = [];
        $this->lastErr = [
            'code' => 0,
            'message' => ''
        ];
    }
    /**
     * Start SQL transaction.
     * 
     * This will disable auto-commit.
     * 
     * @param callable $transaction A function that holds the logic of the transaction.
     * The function must return true or null for success. If false is
     * returned, it means the transaction failed and will be rolled back.
     * 
     * @param array $transactionArgs An optional array of parameters to be passed
     * to the transaction.
     * 
     * @return bool If the transaction completed without errors, the method will
     * return true. False otherwise.
     * 
     * @throws DatabaseException The method will throw an exception if it was
     * rolled back due to an error.
     */
    public function transaction(callable $transaction, array $transactionArgs = []) : bool {
        $conn = $this->getConnection();
        $name = 'transation_'. rand();
        
        try {
            
            $args = array_merge([$this], $transactionArgs);
            $conn->beginTransaction($name);
            $result = call_user_func_array($transaction, $args);
            
            if ($result === null || $result === true) {
                $conn->commit($name);
                return true;
            } else {
                $conn->rollBack($name);
                return false;
            }
        } catch (Exception $ex) {
            $conn->rollBack($name);
            $query = $ex instanceof DatabaseException ? $ex->getSQLQuery() : '';
            throw new DatabaseException($ex->getMessage(), $ex->getCode(), $query, $ex);
        }
    }
    /**
     * Adds a database query to the set of queries at which they were executed.
     * 
     * This method is called internally by the library to add the query. The 
     * developer does not have to call this method manually.
     * 
     * @param string $query SQL query as string.
     * 
     * @param string $type The type of the query such as 'select', 'update' or 
     * 'delete'.
     * 
     * 
     */
    public function addQuery(string $query, string $type) {
        $this->queries[] = [
            'type' => $type,
            'query' => $query
        ];
    }
    /**
     * Reset the bindings which was set by building and executing a query.
     * 
     * @return Database The method will return the instance at which the method
     * is called on.
     */
    public function resetBinding() : Database {
        $this->getQueryGenerator()->resetBinding();
        return $this;
    }
    /**
     * Adds a table to the instance.
     * 
     * @param Table $table the table that will be added.
     * 
     * @param bool $updateOwnerDb If the owner database of the table is already
     * set and this parameter is set to true, the owner database will be
     * updated to the database specified in the instance. This parameter
     * is used to maintain foreign key relationships between tables which
     * belongs to different databases.
     * 
     * @return bool If the table is added, the method will return true. False 
     * otherwise.
     * 
     * 
     */
    public function addTable(Table $table, bool $updateOwnerDb = true) : bool {
        $trimmedName = $table->getNormalName();

        if (!$this->hasTable($trimmedName)) {
            if ($table->getOwner() === null || ($table->getOwner() !== null && $updateOwnerDb)) {
                $table->setOwner($this);
            }
            $this->tablesArr[$trimmedName] = $table;

            return true;
        }

        return false;
    }

    /**
     * Build a 'where' expression.
     *
     * This method can be used to append an 'and' condition to an already existing
     * 'where' condition.
     *
     * @param AbstractQuery|string $col A string that represents the name of the
     * column that will be evaluated. This also can be an object of type
     * 'AbstractQuery' in case the developer would like to build a sub-where
     * condition.
     *
     *
     * @param mixed $val The value (or values) at which the column will be evaluated
     * against. Can be ignored if first parameter is of
     * type 'AbstractQuery'.
     *
     * @param string $cond A string that represents the condition at which column
     * value will be evaluated against. Can be ignored if first parameter is of
     * type 'AbstractQuery'.
     * 
     * @return AbstractQuery The method will return an instance of the class
     * 'AbstractQuery' which can be used to build SQL queries.
     *
     * @throws DatabaseException
     */
    public function andWhere($col, $val, string $cond = '=') : AbstractQuery {
        return $this->where($col, $val, $cond);
    }
    /**
     * Rest all attributes of the class to original values.
     * 
     * 
     */
    public function clear() {
        $this->queries = [];
        $this->getQueryGenerator()->reset();
        $this->resetBinding();
    }
    /**
     * Creates a blueprint of a table that can be used to build table structure.
     * 
     * @param string $name The name of the table as it appears in the database.
     * 
     * @return Table the method will return an instance of the class 'Table'
     * which will be based on the type of DBMS at which the instance is
     * connected to. If connected to MySQL, an instance of 'MySQLTable' is
     * returned. If connected to MSSQL, an instance of MSSQLTable is returned
     * and so on.
     */
    public function createBlueprint(string $name) : Table {
        $connection = $this->getConnection();
        
        if($connection === null) {
            $dbType = 'mysql';
        } else {
            $dbType = $connection->getConnectionInfo()->getDatabaseType();
        }

        if ($dbType == 'mssql') {
            $blueprint = new MSSQLTable($name);
        } else {
            $blueprint = new MySQLTable($name);
        }
        $this->addTable($blueprint);

        return $blueprint;
    }
    /**
     * Constructs a query which can be used to create selected database table.
     * 
     * @return AbstractQuery The method will return an instance of the class 
     * 'AbstractQuery' which can be used to build SQL queries.
     * 
     * 
     */
    public function createTable() : AbstractQuery {
        return $this->getQueryGenerator()->createTable();
    }
    /**
     * Create SQL query which can be used to create all database tables.
     * 
     * @return AbstractQuery The method will return an instance of the class 
     * 'AbstractQuery' which can be used to build SQL queries.
     * 
     * .1
     */
    public function createTables() : AbstractQuery {
        $generatedQuery = '';

        foreach ($this->getTables() as $tableObj) {
            if ($tableObj->getColsCount() != 0) {
                $generatedQuery .= $tableObj->toSQL()."\n";
            }
        }
        $this->getQueryGenerator()->setQuery($generatedQuery, true);

        return $this->getQueryGenerator();
    }
    /**
     * Constructs a query which can be used to remove a record from the 
     * selected table.
     * 
     * @return AbstractQuery The method will return an instance of the class 
     * 'AbstractQuery' which can be used to build SQL queries.
     * 
     * 
     */
    public function delete() : AbstractQuery {
        $this->clear();

        return $this->getQueryGenerator()->delete();
    }
    /**
     * Constructs a query which will drop a database table when executed.
     * 
     * @return AbstractQuery The method will return an instance of the class 
     * 'AbstractQuery' which can be used to build SQL queries.
     * 
     * 
     */
    public function drop() : AbstractQuery {
        $this->clear();

        return $this->getQueryGenerator()->drop();
    }
    /**
     * Execute SQL query.
     * 
     * @throws DatabaseException The method will throw an exception if one 
     * of 3 cases happens:
     * <ul>
     * <li>No connection was established with any database.</li>
     * <li>An error has occurred while executing the query.</li>
     * </ul>
     * 
     * @return ResultSet|null If the last executed query was a select, show or 
     * describe query, the method will return an object of type 'ResultSet' that 
     * holds fetched records. Other than that, the method will return null.
     * 
     * 
     */
    public function execute() {
        $conn = $this->getConnection();
        $lastQuery = $this->getLastQuery();

        if (!$conn->runQuery($this->getQueryGenerator())) {
            throw new DatabaseException($conn->getLastErrCode().' - '.$conn->getLastErrMessage(), $conn->getLastErrCode());
        }
        $this->queries[] = $lastQuery;
        $lastQueryType = $this->getQueryGenerator()->getLastQueryType();
        $this->clear();
        $resultSet = null;

        if (in_array($lastQueryType, ['select', 'show', 'describe'])) {
            $resultSet = $this->getLastResultSet();
        }
        $this->getQueryGenerator()->setQuery(null);

        return $resultSet;
    }
    /**
     * Returns the connection at which the instance will use to run SQL queries.
     * 
     * This method will try to connect to the database if no connection is active. 
     * If the connection was not established, the method will throw an exception. 
     * If the connection is already active, the method will return it.
     * 
     * @return Connection The connection at which the instance will use to run SQL queries.
     *
     * 
     * 
     */
    public function getConnection() : ?Connection {
        $connInfo = $this->getConnectionInfo();
        
        if ($this->connection === null && $connInfo !== null) {
            $driver = $connInfo->getDatabaseType();

            if ($driver == 'mysql') {
                $conn = new MySQLConnection($connInfo);
                $this->setConnection($conn);
            } else if ($driver == 'mssql') {
                $conn = new MSSQLConnection($connInfo);
                $this->setConnection($conn);
            }
        }

        return $this->connection;
    }
    /**
     * Returns an object that holds connection information.
     * 
     * @return ConnectionInfo|null An object that holds connection information.
     * 
     * 
     */
    public function getConnectionInfo() : ?ConnectionInfo {
        return $this->connectionInfo;
    }

    /**
     * Returns an indexed array that contains all executed SQL queries.
     *
     * @return array An indexed array that contains all executed SQL queries.
     *
     */
    public function getExecutedQueries() : array {
        return $this->getConnection()->getExecutedQueries();
    }
    /**
     * Returns the last database error info.
     * 
     * @return array The method will return an associative array with two indices. 
     * The first one is 'message' which contains error message and the second one 
     * is 'code' which contains error code.
     * 
     * 
     */
    public function getLastError() : array {
        if ($this->connection !== null) {
            $this->lastErr = [
                'message' => $this->connection->getLastErrMessage(),
                'code' => $this->connection->getLastErrCode()
            ];
        }

        return $this->lastErr;
    }
    /**
     * Returns the last generated SQL query.
     * 
     * @return string Last generated SQL query as string.
     * 
     * 
     */
    public function getLastQuery() : string {
        return trim($this->getQueryGenerator()->getQuery());
    }

    /**
     * Returns the last result set which was generated from executing a query such
     * as a 'select' query.
     *
     * @return ResultSet|null The last result set. If no result set is available,
     * the method will return null.
     */
    public function getLastResultSet() {
        return $this->getConnection()->getLastResultSet();
    }
    /**
     * Returns the name of the database.
     * 
     * @return string The name of the database.
     * 
     * 
     */
    public function getName() : string {
        return $this->getConnectionInfo()->getDBName();
    }
    /**
     * Returns an indexed array that contains all generated SQL queries.
     * 
     * @return array An indexed array that contains all generated SQL queries.
     * 
     * 
     */
    public function getQueries() : array {
        return $this->queries;
    }
    /**
     * Returns the query builder which is used to build SQL queries.
     *  
     * @return AbstractQuery
     * 
     * 
     */
    public function getQueryGenerator() : AbstractQuery {
        if (!$this->isConnected()) {
            if ($this->getConnectionInfo() === null) {
                throw new DatabaseException("Connection information not set.");
            } else {
                throw new DatabaseException("Not connected to database.");
            }
        }
        return $this->queryGenerator;
    }
    /**
     * Returns a table structure as an object given its name.
     * 
     * @param string $tblName The name of the table.
     * 
     * @return Table|null If a table which has the given name is existed, it will
     * be returned as an object. Other than that, null is returned.
     * 
     *
     */
    public function getTable(string $tblName) {
        $trimmed = trim($tblName);

        if (!isset($this->tablesArr[$trimmed])) {
            return null;
        }
        $engine = 'mysql';
        $info = $this->getConnectionInfo();
        
        if ($info !== null) {
            $engine = $info->getDatabaseType();
        }
        $table = $this->tablesArr[$trimmed];
        
        return TableFactory::map($engine, $table);
    }
    /**
     * Returns an array that contains all added tables.
     * 
     * @return array The method will return an associative array. The indices 
     * of the array are tables names and the values are objects of type 'Table'.
     * 
     * .1
     */
    public function getTables() : array {
        return $this->tablesArr;
    }
    /**
     * Checks if a table exist in the database or not.
     * 
     * @param string $tableName The name of the table.
     * 
     * @return bool If the table exist, the method will return true. 
     * False if it does not exist.
     * 
     * 
     */
    public function hasTable(string $tableName) : bool {
        return isset($this->tablesArr[$tableName]);
    }
    /**
     * Constructs a query which can be used to insert a record in the selected 
     * table.
     * 
     * @param array $colsAndVals An associative array that holds the columns and 
     * values. The indices of the array should be column keys and the values 
     * of the indices are the new values.
     * 
     * @return AbstractQuery The method will return an instance of the class 
     * 'AbstractQuery' which can be used to build SQL queries.
     * 
     * 
     */
    public function insert(array $colsAndVals) : AbstractQuery {
        $this->clear();

        return $this->getQueryGenerator()->insert($colsAndVals);
    }
    public function isConnected() : bool {
        if ($this->getConnectionInfo() === null) {
            return false;
        }
        try {
            if ($this->getConnection() === null) {
                return false;
            }
        } catch (DatabaseException $ex) {
            $this->lastErr = [
                'code' => $ex->getCode(),
                'message' => $ex->getMessage()
            ];
            return false;
        }
        
        return true;
    }
    /**
     * Sets the number of records that will be fetched by the query.
     * 
     * @param int $limit A number which is greater than 0.
     * 
     * @return AbstractQuery The method will return an instance of the class 
     * 'AbstractQuery' which can be used to build SQL queries.
     * 
     * 
     */
    public function limit(int $limit) : AbstractQuery {
        return $this->getQueryGenerator()->limit($limit);
    }
    /**
     * Sets the offset.
     * 
     * The offset is basically the number of records that will be skipped from the 
     * start.
     * 
     * @param int $offset Number of records to skip.
     * 
     * @return AbstractQuery The method will return an instance of the class 
     * 'AbstractQuery' which can be used to build SQL queries.
     * 
     * 
     */
    public function offset(int $offset) : AbstractQuery {
        return $this->getQueryGenerator()->offset($offset);
    }

    /**
     * Build a 'where' expression.
     *
     * This method can be used to append an 'or' condition to an already existing
     * 'where' condition.
     *
     * @param AbstractQuery|string $col A string that represents the name of the
     * column that will be evaluated. This also can be an object of type
     * 'AbstractQuery' in case the developer would like to build a sub-where
     * condition.
     *
     * @param mixed $val The value (or values) at which the column will be evaluated
     * against. Can be ignored if first parameter is of
     * type 'AbstractQuery'.
     * 
     * @param string $cond A string that represents the condition at which column
     * value will be evaluated against. Can be ignored if first parameter is of
     * type 'AbstractQuery'.
     *
     * @return AbstractQuery The method will return an instance of the class
     * 'AbstractQuery' which can be used to build SQL queries.
     *
     * @throws DatabaseException
     */
    public function orWhere(string $col, mixed $val = null, string $cond = '=') : AbstractQuery {
        return $this->where($col, $val, $cond, 'or');
    }
    /**
     * Constructs a query which can be used to fetch a set of records as a page.
     * 
     * @param int $num Page number. It should be a number greater than or equals 
     * to 1.
     * 
     * @param int $itemsCount Number of records per page. Must be a number greater than or equals to 1.
     * 
     * @return AbstractQuery The method will return an instance of the class 
     * 'AbstractQuery' which can be used to build SQL queries.
     * 
     * 
     */
    public function page(int $num, int $itemsCount) : AbstractQuery {
        return $this->getQueryGenerator()->page($num, $itemsCount);
    }
    /**
     * Constructs a query that can be used to get records from a table.
     * 
     * @param array $cols An array that holds the keys of the columns that will 
     * be selected.
     * 
     * @return AbstractQuery The method will return an instance of the class 
     * 'AbstractQuery' which can be used to build SQL queries.
     * 
     * 
     */
    public function select(array $cols = ['*']) : AbstractQuery {
        $this->clear();

        return $this->getQueryGenerator()->select($cols);
    }
    /**
     * Sets the connection that will be used by the schema.
     * 
     * @param Connection $con An active connection.
     * 
     * 
     */
    public function setConnection(Connection $con) {
        $this->connection = $con;
    }
    /**
     * Sets database connection information.
     * 
     * @param ConnectionInfo $info An object that holds connection information.
     * 
     * @throws DatabaseException The method will throw an exception if database 
     * driver is not supported.
     * 
     * 
     */
    public function setConnectionInfo(ConnectionInfo $info) {
        $driver = $info->getDatabaseType();

        if ($driver == 'mysql') {
            $this->queryGenerator = new MySQLQuery();
            $this->queryGenerator->setSchema($this);
        } else if ($driver == 'mssql') {
            $this->queryGenerator = new MSSQLQuery();
            $this->queryGenerator->setSchema($this);
        } else {
            throw new DatabaseException('Driver not supported: "'.$driver.'".');
        }
        $this->connectionInfo = $info;
    }

    /**
     * Sets the database query to a raw SQL query.
     *
     * @param string $query A string that represents the query.
     *
     * @return Database The method will return the same instance at which the
     * method is called on.
     *
     * @throws DatabaseException
     */
    public function setQuery(string $query) : Database {
        $t = $this->getQueryGenerator()->getTable();

        if ($t !== null) {
            $t->getSelect()->clear();
        }
        $this->getQueryGenerator()->setQuery($query);

        return $this;
    }
    /**
     * Select one of the tables which exist on the schema and use it to build
     * SQL queries.
     * 
     * @param string $tblName The name of the table.
     * 
     * @return AbstractQuery The method will return an instance of the class 
     * 'AbstractQuery' which can be used to build SQL queries.
     * 
     * 
     */
    public function table(string $tblName) : AbstractQuery {
        return $this->getQueryGenerator()->table($tblName);
    }
    /**
     * Constructs a query which will truncate a database table when executed.
     * 
     * @return AbstractQuery The method will return an instance of the class 
     * 'AbstractQuery' which can be used to build SQL queries.
     * 
     * 
     */
    public function truncate() : AbstractQuery {
        $this->clear();

        return $this->getQueryGenerator()->truncate();
    }
    /**
     * Constructs a query which can be used to update a record in the selected 
     * table.
     * 
     * @param array $newColsVals An associative array that holds the columns and 
     * values. The indices of the array should be column keys and the values 
     * of the indices are the new values.
     * 
     * @return AbstractQuery The method will return an instance of the class 
     * 'AbstractQuery' which can be used to build SQL queries.
     * 
     * 
     */
    public function update(array $newColsVals) : AbstractQuery {
        $this->clear();

        return $this->getQueryGenerator()->update($newColsVals);
    }

    /**
     * Build a where condition.
     *
     *
     * @param AbstractQuery|string $col A string that represents the name of the
     * column that will be evaluated. This also can be an object of type
     * 'AbstractQuery' in case the developer would like to build a sub-where
     * condition.
     *
     * @param mixed $val The value (or values) at which the column will be evaluated
     * against. Can be ignored if first parameter is of
     * type 'AbstractQuery'.
     * 
     * @param string $cond A string that represents the condition at which column
     * value will be evaluated against. Can be ignored if first parameter is of
     * type 'AbstractQuery'.
     * 
     * @param string $joinCond An optional string which can be used to join
     * multiple where conditions. If not provided, 'and' will be used by default.
     *
     * @return AbstractQuery The method will return an instance of the class
     * 'AbstractQuery' which can be used to build SQL queries.
     *
     * @throws DatabaseException
     */
    public function where($col, mixed $val = null, string $cond = '=', string $joinCond = 'and') : AbstractQuery {
        return $this->getQueryGenerator()->where($col, $val, $cond, $joinCond);
    }
}
