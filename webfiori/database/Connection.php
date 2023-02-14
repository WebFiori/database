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

/**
 * A class that represents a connection to a database.
 * 
 * @author Ibrahim
 * 
 * @version 1.0.1
 */
abstract class Connection {
    /**
     *
     * @var ConnectionInfo 
     * 
     * @since 1.0
     */
    private $connParams;
    private $executedQueries;
    /**
     *
     * @var type 
     * 
     * @since 1.0
     */
    private $lastErrCode;
    /**
     *
     * @var type 
     * 
     * @since 1.0
     */
    private $lastErrMsg;
    /**
     *
     * @var type 
     * 
     * @since 1.0
     */
    private $lastQuery;
    /**
     * The result set which contains fetched data.
     * 
     * @var ResultSet 
     */
    private $resultSet;
    /**
     * Creates new instance of the class.
     * 
     * @param ConnectionInfo $connInfo An object that contains database connection 
     * information.
     * 
     * @throws DatabaseException If the connection to the database fails, the method 
     * will throw an exception.
     * 
     * @since 1.0
     */
    public function __construct(ConnectionInfo $connInfo) {
        $this->connParams = $connInfo;
        $this->executedQueries = [];

        if (!$this->connect()) {
            throw new DatabaseException('Unable to connect to database: '.$this->getLastErrCode().' - '.$this->getLastErrMessage());
        }
    }
    /**
     * Adds a query to the set of executed SQL queries.
     * 
     * This method is used to append the queries that reached execution stage.
     * 
     * @param string $query The query that will be executed.
     */
    public function addToExecuted(string $query) {
        $this->executedQueries[] = $query;
    }
    /**
     * Connect to RDBMS.
     * 
     * The developer must implement this method in a way it establishes a connection 
     * to a database using native database driver or PDO. Once the connection is 
     * established without errors, the method should return true.
     * 
     * @return bool If the connection to the database is established, the method 
     * should return true. False otherwise.
     * 
     * @since 1.0
     */
    public abstract function connect() : bool;
    /**
     * Returns an object that contains database connection information.
     * 
     * @return ConnectionInfo An object that contains database connection information.
     * 
     * @since 1.0
     */
    public function getConnectionInfo() : ConnectionInfo {
        return $this->connParams;
    }
    /**
     * Returns an indexed array that contains all executed SQL queries.
     * 
     * @return array An indexed array that contains all executed SQL queries.
     * 
     */
    public function getExecutedQueries() : array {
        return $this->executedQueries;
    }
    /**
     * Returns error code at which that was generated by executing last query.
     * 
     * @return int|string Last error code at which that was generated by executing last query.
     * 
     * @since 1.0
     */
    public function getLastErrCode() {
        return $this->lastErrCode;
    }
    /**
     * Returns the last message at which that was generated by executing a query.
     * 
     * @return string The last message at which that was generated by executing a query.
     * 
     * @since 1.0
     */
    public function getLastErrMessage() : string {
        return $this->lastErrMsg;
    }
    /**
     * Returns last executed query object.
     * 
     * @return AbstractQuery|null Last executed query object. If no query was executed, 
     * the method will return null.
     * 
     * @since 1.0
     */
    public function getLastQuery() {
        return $this->lastQuery;
    }
    /**
     * Returns last result set.
     * 
     * @return ResultSet|null The result set. If the result set is not set, the 
     * method will return null.
     * 
     * @since 1.0
     */
    public function getLastResultSet() {
        return $this->resultSet;
    }
    /**
     * Creates a prepared SQL statement from the query.
     * 
     * The implementation of this method should execute a prepare statement 
     * on the database engine. An example is MySQL. In this case, the developer 
     * must use the method mysqli::prepare(). After the statement is prepared, 
     * then the developer can bind parameters values using the 
     * method mysqli_stmt::bind_param().
     * 
     * @param array $queryParams An optional array of parameters to bind with the 
     * prepared query. The structure of the array will depend on the type of 
     * database engine that will be used.
     * 
     * @since 1.0.1
     */
    public abstract function prepare(array $queryParams = []);
    /**
     * Sets the last query and execute it.
     * 
     * This method should be implemented in a way that it accepts null or an 
     * object of type 'AbstractQuery'. If an object of type 'AbstractQuery' is 
     * passed, then the last query will be set to it. After that, the method should 
     * run the query. If null is passed, the method should check for last 
     * query object. If set, it should execute it.
     * 
     * @since 1.0
     */
    public abstract function runQuery(AbstractQuery $query = null);
    /**
     * Sets error code at which that was generated by executing last query.
     * 
     * @param int|string $code An integer value or any code that represents error code.
     * 
     * @since 1.0
     */
    public function setErrCode($code) {
        $this->lastErrCode = $code;
    }
    /**
     * Sets error message at which that was generated by executing last query.
     * 
     * @param string $message The Error message.
     * 
     * @since 1.0
     */
    public function setErrMessage(string $message) {
        $this->lastErrMsg = $message;
    }
    /**
     * Sets the last executed query.
     * 
     * @param AbstractQuery $query Last executed query.
     * 
     * @since 1.0
     */
    public function setLastQuery(AbstractQuery $query) {
        $this->lastQuery = $query;
    }
    /**
     * Sets result set.
     * 
     * @param ResultSet $result An object that represents result set.
     * 
     * @since 1.0
     */
    public function setResultSet(ResultSet $result) {
        $this->resultSet = $result;
    }
}
