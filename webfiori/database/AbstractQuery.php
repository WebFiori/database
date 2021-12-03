<?php
/**
 * MIT License
 *
 * Copyright (c) 2019, WebFiori Framework.
 * 
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */
namespace webfiori\database;

use webfiori\database\mysql\MySQLQuery;
use webfiori\database\mssql\MSSQLQuery;
/**
 * A base class that can be used to build SQL queries.
 * 
 * @author Ibrahim
 * 
 * @version 1.0.4
 */
abstract class AbstractQuery {
    /**
     *
     * @var Table|null 
     * 
     * @since 1.0
     */
    private $associatedTbl;
    /**
     *
     * @var boolean
     * 
     * @since 1.0.1 
     */
    private $isMultiQuery;
    /**
     *
     * @var boolean
     * 
     * @since 1.0.2 
     */
    private $isPrepare;
    /**
     * @var string 
     * 
     * @since 1.0
     */
    private $lastQueryType;

    /**
     *
     * @var int
     * 
     * @since 1.0
     */
    private $limit;
    /**
     *
     * @var int
     * 
     * @since 1.0
     */
    private $offset;
    /**
     *
     * @var array 
     */
    private $params;
    /**
     *
     * @var AbstractQuery|null 
     */
    private $prevQueryObj;
    /**
     *
     * @var string 
     * 
     * @since 1.0
     */
    private $query;
    /**
     *
     * @var Database 
     * 
     * @since 1.0
     */
    private $schema;
    /**
     * Creates new instance of the class.
     * 
     * @since 1.0
     */
    public function __construct() {
        $this->limit = -1;
        $this->offset = -1;
        $this->query = '';
        $this->params = [];
        $this->isPrepare = false;
    }
    /**
     * Constructs a query that can be used to add a column to a database table.
     * 
     * The developer should implement this method in a way it creates SQL query 
     * that can be used to add a column to a table.
     * 
     * @param string $colKey The name of column key as specified when the column 
     * was added to the table.
     * 
     * @param string $location The location at which the column will be added to. 
     * This usually the name of the column that the new column will be added after.
     * 
     * @return AbstractQuery The method should return the same instance at which 
     * the method is called on.
     * 
     * @since 1.0
     */
    public abstract function addCol($colKey, $location = null);
    /**
     * Constructs a query that can be used to add foreign key constraint.
     * 
     * @param string $keyName The name of the foreign key as specified when creating 
     * the table.
     * 
     * @return AbstractQuery The method should return the same instance at which 
     * the method is called on.
     * 
     * @throws DatabaseException If no key with the given name exist in the table.
     * 
     * @since 1.0.1
     */
    public function addForeignKey($keyName) {
        $fkObj = $this->getTable()->getForeignKey($keyName);

        if ($fkObj === null) {
            throw new DatabaseException("No such foreign key: '$keyName'.");
        }

        $sourceCols = [];

        foreach ($fkObj->getSourceCols() as $colObj) {
            $sourceCols[] = $colObj->getName();
        }
        $targetCols = [];

        foreach ($fkObj->getOwnerCols() as $colObj) {
            $targetCols[] = $colObj->getName();
        }
        $fkConstraint = "constraint ".$fkObj->getKeyName().' '
                .'foreign key ('.implode(', ', $targetCols).') '
                .'references '.$fkObj->getSourceName().' ('.implode(', ', $sourceCols).')';
        $tblName = $this->getTable()->getName();

        if ($fkObj->getOnUpdate() !== null) {
            $fkConstraint .= ' on update '.$fkObj->getOnUpdate();
        }

        if ($fkObj->getOnDelete() !== null) {
            $fkConstraint .= ' on delete '.$fkObj->getOnDelete();
        }
        $finalQuery = "alter table $tblName add $fkConstraint;";
        $this->setQuery($finalQuery);

        return $this;
    }
    /**
     * Constructs a query which can be used to add a primary key constrain to a 
     * table. 
     * 
     * @param string $pkName The name of the primary key.
     * 
     * @param array $pkCols An array that contains the keys of the columns that the 
     * primary key is composed of.
     * 
     * @return AbstractQuery The method should return the same instance at which 
     * the method is called on.
     */
    public abstract function addPrimaryKey($pkName, array $pkCols);

    /**
     * Build a where condition.
     * 
     * This method can be used to append an 'and' condition to an already existing 
     * 'where' condition.
     * 
     * @param AbstractQuery|string $col A string that represents the name of the 
     * column that will be evaluated. This also can be an object of type 
     * 'AbstractQuery' in case the developer would like to build a sub-where 
     * condition.
     * 
     * @param string $cond A string that represents the condition at which column 
     * value will be evaluated against. Can be ignored if first parameter is of 
     * type 'AbstractQuery'.
     * 
     * @param mixed $val The value (or values) at which the column will be evaluated 
     * against. Can be ignored if first parameter is of 
     * type 'AbstractQuery'.
     * 
     * 
     * @return AbstractQuery Returns the same instance at which the method is 
     * called on.
     * 
     * @since 1.0
     */
    public function andWhere($col, $cond = null, $val = null) {
        return $this->where($col, $cond, $val, 'and');
    }
    /**
     * Creates and returns a copy of the builder.
     * 
     * The information that will be copied includes:
     * <ul>
     * <li>Limit.</li>
     * <li>Offset.</li>
     * <li>Linked table.</li>
     * <li>Linked schema.</li>
     * </ul>
     * 
     * @return MySQLQuery
     * 
     * @since 1.0
     */
    public function copyQuery() {
        $driver = $this->getSchema()->getConnectionInfo()->getDatabaseType();

        if ($driver == 'mysql') {
            $copy = new MySQLQuery();
            $copy->limit = $this->limit;
            $copy->offset = $this->offset;
            $copy->associatedTbl = $this->associatedTbl;
            $copy->schema = $this->schema;

            return $copy;
        } else if ($driver == 'mssql') {
            $copy = new MSSQLQuery();
            $copy->associatedTbl = $this->associatedTbl;
            $copy->schema = $this->schema;
            
            return $copy;
        }
    }
    /**
     * Constructs a query which when executed will create the table in the database. 
     * 
     * @return AbstractQuery The method will return the same instance at which 
     * the method is called on.
     * 
     * @since 1.0
     */
    public function createTable() {
        $table = $this->getTable();
        $this->setQuery($table->toSQL());

        return $this;
    }
    /**
     * Constructs a query which can be used to remove a record from the associated 
     * table.
     * 
     * @return MSSQLQuery|MySQLQuery The method will return the same instance at which the 
     * method is called on.
     * 
     * @since 1.0
     */
    public function delete() {
        $tblName = $this->getTable()->getName();
        $this->setQuery("delete from $tblName");

        return $this;
    }
    /**
     * Constructs a query which will drop a database table when executed.
     * 
     * @return AbstractQuery The method will return the same instance at which 
     * the method is called on.
     * 
     * @since 1.0
     */
    public function drop() {
        $table = $this->getTable();
        $this->setQuery('drop table '.$table->getName().';');

        return $this;
    }
    /**
     * Constructs a query which can be used to drop a column from associated 
     * table.
     * 
     * @param string $colKey The name of column key taken from the table.
     * 
     * @return MSSQLQuery|MySQLQuery The method will return the same instance at which the 
     * method is called on.
     * 
     * @throws DatabaseException If no column which has the given key, the method 
     * will throw an exception.
     * 
     * @since 1.0
     */
    public function dropCol($colKey) {
        $tblName = $this->getTable()->getName();
        $colObj = $this->getTable()->getColByKey($colKey);

        if (!($colObj instanceof Column)) {
            throw new DatabaseException("The table $tblName has no column with key '$colKey'.");
        }
        $withTick = $colObj->getName();
        $stm = "alter table $tblName drop column $withTick;";
        $this->setQuery($stm);

        return $this;
    }
    /**
     * Constructs a query that can be used to drop foreign key constraint.
     * 
     * Note that the syntax will support only SQL Server and Oracle. The developer 
     * may have to override this method to support other databases.
     * 
     * @param string $keyName The name of the key.
     * 
     * @return AbstractQuery The method should return the same instance at which 
     * the method is called on.
     * 
     * @since 1.0.1
     */
    public function dropForeignKey($keyName) {
        $trimmed = trim($keyName);

        if (strlen($trimmed) != 0) {
            $tableName = $this->getTable()->getName();
            $alterQuery = "alter table $tableName drop constraint $trimmed;";
            $this->setQuery($alterQuery);
        }

        return $this;
    }
    /**
     * Constructs a query which can be used to drop a primary key constrain from a 
     * table. 
     * 
     * @param string $pkName The name of the primary key.
     * 
     * @return AbstractQuery The method should return the same instance at which 
     * the method is called on.
     * 
     * @since 1.0
     */
    public abstract function dropPrimaryKey($pkName = null);
    /**
     * Execute the generated SQL query.
     * 
     * @return ResultSet|null If the last executed query was a select, show or 
     * describe query, the method will return an object of type 'ResultSet' that 
     * holds fetched records. Other than that, the method will return null.
     * 
     * @throws DatabaseException The method will throw an exception if one 
     * of 3 cases happens:
     * <ul>
     * <li>No schema is associated with the instance.</li>
     * <li>No connection was established with any database.</li>
     * <li>An error has occurred while executing the query.</li>
     * </ul>
     * 
     * @since 1.0
     */
    public function execute() {
        try {
            return $this->getSchema()->execute();
        } catch (DatabaseException $ex) {
            throw new DatabaseException($ex->getMessage());
        }
    }
    /**
     * Returns the type of last generated SQL query.
     * 
     * @return string The method will return a string such as 'select' or 'update'.
     * 
     * @since 1.0
     */
    public function getLastQueryType() {
        return $this->lastQueryType;
    }
    /**
     * Returns a number that represents the limit.
     * 
     * The limit is basically the number of records that will be fetched.
     * 
     * @return int Number of records will be fetched. Default is -1.
     * 
     * @since 1.0
     */
    public function getLimit() {
        return $this->limit;
    }
    /**
     * Returns a number that represents the offset.
     * 
     * The offset is basically the number of records that will be skipped 
     * from the start when fetching the result.
     * 
     * @return int Number of records will be skipped. Default is -1.
     * 
     * @since 1.0
     */
    public function getOffset() {
        return $this->offset;
    }
    /**
     * Returns an array that contains the values at which the prepared query 
     * will be bind to.
     * 
     * @return array An array that contains the values at which the prepared query 
     * will be bind to.
     * 
     * @since 1.0.2
     */
    public function getParams() {
        return $this->params;
    }
    /**
     * Returns the previously lined query builder.
     * 
     * @return AbstractQuery|null
     */
    public function getPrevQuery() {
        return $this->prevQueryObj;
    }
    /**
     * Returns the generated SQL query.
     * 
     * @return string Returns the generated query as string.
     * 
     * @since 1.0
     */
    public function getQuery() {
        $retVal = $this->query;

        $lastQType = $this->getLastQueryType();

        $table = $this->getTable();

        if ($table !== null && ($lastQType == 'select' || $lastQType == 'delete' || $lastQType == 'update')) {
            $whereExp = $table->getSelect()->getWhereStr();

            if (strlen($whereExp) != 0) {
                $retVal .= $whereExp;
            }
        }

        return $retVal;
    }
    /**
     * Returns the schema at which the generator is associated with.
     * 
     * @return Database The schema at which the generator is associated with.
     * 
     * @throws DatabaseException If the builder is not associated with any 
     * schema, the method will throw an exception.
     * 
     * @since 1.0
     */
    public function getSchema() {
        if ($this->schema === null) {
            throw new DatabaseException('No schema was associated with the query.');
        }

        return $this->schema;
    }
    /**
     * Returns the table which was associated with the query.
     * 
     * @return Table|null The associated table as an object. If no table is 
     * associated, the method will return null.
     * 
     * @throws DatabaseException If no table was associated with the query builder, 
     * the method will throw an exception.
     * 
     * @since 1.0
     */
    public function getTable() {
        return $this->associatedTbl;
    }
    /**
     * Adds a set of columns to the 'group by' part of the query.
     * 
     * @param string|array $colOrColsArr This can be one column key or an 
     * array that contains columns keys.
     * 
     * @return AbstractQuery The method will return the same instance at which 
     * the method is called on.
     * 
     * @since 1.0
     */
    public function groupBy($colOrColsArr) {
        if (gettype($colOrColsArr) == 'array') {
            foreach ($colOrColsArr as $colKey) {
                $this->getTable()->getSelect()->groupBy($colKey);
            }
        } else {
            $this->getTable()->getSelect()->groupBy($colOrColsArr);
        }

        return $this;
    }
    /**
     * Constructs a query which can be used to insert a record in a table.
     * 
     * @param array $colsAndVals An associative array that holds the columns and 
     * values. The indices of the array should be column keys and the values 
     * of the indices are the new values.
     * 
     * @return AbstractQuery The method should return the same instance at which 
     * the method is called on.
     * 
     * @since 1.0
     */
    public abstract function insert(array $colsAndVals);
    /**
     * Checks if the query represents a multi-query.
     * 
     * @return boolean The method will return true if the query is a multi-query. 
     * False if not.
     * 
     * @since 1.0.1
     */
    public function isMultiQuery() {
        return $this->isMultiQuery;
    }
    /**
     * Checks if the query will be prepared before execution or not.
     * 
     * @return boolean The method will return true if the query will be prepared 
     * before execution. False if not.
     * 
     * @since 1.0.2
     */
    public function isPrepareBeforeExec() {
        return $this->isPrepare;
    }
    /**
     * Perform a join query.
     * 
     * @param AbstractQuery $query The query at which the current query 
     * result will be joined with.
     * 
     * @param string $joinType The type of the join such as 'left join'.
     * 
     * @return AbstractQuery The method will return the same instance at which 
     * the method is called on.
     * 
     * @since 1.0
     */
    public function join(AbstractQuery $query, $joinType = 'join') {
        $leftTable = $this->getPrevQuery()->getTable();
        $rightTable = $query->getTable();

        $alias = $leftTable->getNormalName();


        $nameAsInt = intval(substr($alias, -1));
        $alias = 'T'.(++$nameAsInt);
        


        $joinTable = new JoinTable($leftTable, $rightTable, $joinType, $alias);
        $this->setTable($joinTable);

        return $this;
    }
    /**
     * Perform a left join query.
     * 
     * @param AbstractQuery $query The query at which the current query 
     * result will be joined with.
     * 
     * @return AbstractQuery The method will return the same instance at which 
     * the method is called on.
     * 
     * @since 1.0
     */
    public function leftJoin(AbstractQuery $query) {
        return $this->join($query, 'left join');
    }
    /**
     * Sets the number of records that will be fetched by the query.
     * 
     * @param int $limit A number which is greater than 0.
     * 
     * @return AbstractQuery The method will return the same instance at which 
     * the method is called on.
     * 
     * @since 1.0
     */
    public function limit($limit) {
        if ($limit > 0) {
            $this->limit = $limit;
        }

        return $this;
    }
    /**
     * Constructs a query that can be used to modify a column.
     * 
     * @param string $colKey The name of column key as specified when the column 
     * was added to the table.
     * 
     * @param string $location The location at which the column will be moved to. 
     * This usually the name of the column that the column will be added after.
     * 
     * @return AbstractQuery The method should return the same instance at which 
     * the method is called on.
     * 
     * @since 1.0
     */
    public abstract function modifyCol($colKey, $location = null);
    /**
     * Sets the offset.
     * 
     * The offset is basically the number of records that will be skipped from the 
     * start.
     * 
     * @param int $offset Number of records to skip.
     * 
     * @return AbstractQuery The method will return the same instance at which 
     * the method is called on.
     * 
     * @since 1.0
     */
    public function offset($offset) {
        if ($offset > 0) {
            $this->offset = $offset;
        }

        return $this;
    }
    /**
     * Adds an 'on' condition to a join query.
     * 
     * @param string $leftCol The name of the column key which exist in the left table.
     * 
     * @param string $rightCol The name of the column key which exist in the right table.
     * 
     * @param string $cond A condition which is used to join a new 'on' condition 
     * with existing one. The value of this attribute can be only 'and' or 'or'.
     * 
     * @return AbstractQuery The method will return the same instance at which 
     * the method is called on.
     * 
     * @since 1.0
     */
    public function on($leftCol, $rightCol, $cond = '=', $joinWith = 'and') {
        $table = $this->getTable();

        if ($table instanceof JoinTable) {
            $leftCol = $table->getLeft()->getColByKey($leftCol);

            if ($leftCol instanceof Column) {
                $leftCol->setWithTablePrefix(false);
                if ($leftCol->getOwner() instanceof JoinTable && $leftCol->getAlias() !== null) {
                    $leftCol->setName($leftCol->getAlias());
                }
                $leftColName = $leftCol->getOwner()->getName().'.'.$leftCol->getOldName();
                
                $rightCol = $table->getRight()->getColByKey($rightCol);

                if ($rightCol instanceof Column) {
                    $rightCol->setWithTablePrefix(false);
                    $rightColName = $rightCol->getOwner()->getName().'.'.$rightCol->getOldName();
                    $cond = new Condition($leftColName, $rightColName, $cond);
                    $table->addJoinCondition($cond, $joinWith);
                } else {
                    $tableName = $table->getName();
                    $colsKeys = $table->getColsKeys();
                    $message = "The table '$tableName' has no column with key '$rightCol'. Available columns: ".implode(',', $colsKeys);
                    throw new DatabaseException($message);
                }
            } else {
                $tableName = $table->getName();
                $colsKeys = $table->getColsKeys();
                $message = "The table '$tableName' has no column with key '$leftCol'. Available columns: ".implode(',', $colsKeys);
                throw new DatabaseException($message);
            }
        } else {
            throw new DatabaseException("The 'on' condition can be only used with join tables.");
        }

        return $this;
    }
    /**
     * Adds a set of columns to the 'order by' part of the query.
     * 
     * @param array $colsArr An array that contains columns keys. To specify 
     * order type, the indices should be columns keys and the values are order 
     * type. Order type can have two values, 'a' for 
     * ascending or 'd' for descending.
     * 
     * @return @return AbstractQuery The method will return the same instance at which 
     * the method is called on.
     * 
     * @since 1.0
     */
    public function orderBy(array $colsArr) {
        foreach ($colsArr as $colKey => $orderTypeOrColKey) {
            if (gettype($colKey) == 'string') {
                $this->getTable()->getSelect()->orderBy($colKey, $orderTypeOrColKey);
            } else {
                $this->getTable()->getSelect()->orderBy($orderTypeOrColKey);
            }
        }

        return $this;
    }
    /**
     * Build a where condition.
     * 
     * This method can be used to append an 'or' condition to an already existing 
     * 'where' condition.
     * 
     * @param AbstractQuery|string $col A string that represents the name of the 
     * column that will be evaluated. This also can be an object of type 
     * 'AbstractQuery' in case the developer would like to build a sub-where 
     * condition.
     * 
     * @param string $cond A string that represents the condition at which column 
     * value will be evaluated against. Can be ignored if first parameter is of 
     * type 'AbstractQuery'.
     * 
     * @param mixed $val The value (or values) at which the column will be evaluated 
     * against. Can be ignored if first parameter is of 
     * type 'AbstractQuery'.
     * 
     * 
     * @return AbstractQuery Returns the same instance at which the method is 
     * called on.
     * 
     * @since 1.0
     */
    public function orWhere($col, $cond = null, $val = null) {
        return $this->where($col, $cond, $val, 'or');
    }
    /**
     * Constructs a query which can be used to fetch a set of records as a page.
     * 
     * @param int $num Page number. It should be a number greater than or equals 
     * to 1.
     * 
     * @param int $itemsCount Number of records per page. Must be a number greater 
     * than or equals to 1.
     * 
     * @return AbstractQuery The method will return the same instance at which 
     * the method is called on.
     * 
     * @since 1.0
     */
    public function page($num, $itemsCount) {
        if ($num > 0 && $itemsCount > 0) {
            $this->limit($itemsCount);
            $this->offset($num * $itemsCount);
        }

        return $this;
    }
    /**
     * Constructs a query which can be used to rename a column.
     * 
     * @param string $colKey The name of column key as specified when the column 
     * was added to the table.
     * 
     * @param string $newName The new name of the column.
     * 
     * @return AbstractQuery The method should return the same instance at which 
     * the method is called on.
     */
    public abstract function renameCol($colKey);
    /**
     * Reset query parameters to default values.
     * 
     * @since 1.0
     */
    public function reset() {
        $this->query = '';
        $this->lastQueryType = '';
        $this->limit = -1;
        $this->offset = -1;
        $this->associatedTbl = null;
    }
    /**
     * Perform a right join query.
     * 
     * @param AbstractQuery $query The query at which the current query 
     * result will be joined with.
     * 
     * @return AbstractQuery The method will return the same instance at which 
     * the method is called on.
     * 
     * @since 1.0
     */
    public function rightJoin(AbstractQuery $query) {
        return $this->join($query, 'right join');
    }
    /**
     * Constructs a select query based on associated table.
     * 
     * @param array $cols An array that contains the keys of the columns that 
     * will be selected. This also can be an array that holds objects of type 
     * 'Expression'. Also, it can be an associative array of columns keys and 
     * sub arrays. The sub arrays can have options for the columns that will be 
     * selected. Supported options are:
     * <ul>
     * <li>'obj': An object of type column or an expression.</li>
     * <li>'alias': An optional string which can act as an alias.</li>
     * <li>'aggregate': Aggregate function to use in the column such as 
     * 'avg' or 'max'.</li>
     * </ul>
     * 
     * @return AbstractQuery The method will return the same instance at which the 
     * method is called on.
     * 
     * @since 1.0
     */
    public function select($cols = ['*']) {
        $select = $this->getTable()->getSelect();
        $select->clear();
        $select->select($cols);
        $selectVal = $select->getValue();
        
        $this->setQuery($selectVal);

        return $this;
    }
    /**
     * Constructs a select query which can be used to find the average value 
     * of a column.
     * 
     * @param string $colName The name of column key.
     * 
     * @param string $alias An optional alias for the column that will hold the 
     * value of the average. Default is 'avg'.
     * 
     * @return AbstractQuery The method will return the same instance at which 
     * the method is called on.
     * 
     * @since 1.0.5
     */
    public function selectAvg($colName, $alias = 'avg') {
        if ($colName !== null) {
            $xAlias = strlen(trim($alias)) != 0 ? trim($alias) : 'avg';

            $this->select([
                $colName => [
                    'aggregate' => 'avg',
                    'as' => $xAlias
                ]
            ]);
        }

        return $this;
    }
    /**
     * Constructs a select query which can be used to find the number of rows 
     * of a result.
     * 
     * @param string $colName Optional name of column key. Default is null. 
     * 
     * @param string $alias An optional alias for the column that will hold the 
     * value. Default is 'count'.
     * 
     * @return AbstractQuery The method will return the same instance at which 
     * the method is called on.
     * 
     * @since 1.0.5
     */
    public function selectCount($colName = null, $alias = 'count') {
        $xAlias = strlen(trim($alias)) != 0 ? trim($alias) : 'count';

        if ($colName !== null) {
            $this->select([
                $colName => [
                    'aggregate' => 'count',
                    'as' => $xAlias
                ]
            ]);
        } else {
            $expr = new Expression('count(*) as '.$xAlias);
            $this->select([$expr]);
        }

        return $this;
    }
    /**
     * Constructs a select query which can be used to find the minimum value 
     * of a column.
     * 
     * @param string $colName The name of column key.
     * 
     * @param string $alias An optional alias for the column that will hold the 
     * value. Default is 'max'.
     * 
     * @return AbstractQuery The method will return the same instance at which 
     * the method is called on.
     * 
     * @since 1.0.5
     */
    public function selectMax($colName, $alias = 'max') {
        if ($colName !== null) {
            $xAlias = strlen(trim($alias)) != 0 ? trim($alias) : 'max';

            $this->select([
                $colName => [
                    'aggregate' => 'max',
                    'as' => $xAlias
                ]
            ]);
        }

        return $this;
    }
    /**
     * Constructs a select query which can be used to find the minimum value 
     * of a column.
     * 
     * @param string $colName The name of column key.
     * 
     * @param string $alias An optional alias for the column that will hold the 
     * value. Default is 'min'.
     * 
     * @return AbstractQuery The method will return the same instance at which 
     * the method is called on.
     * 
     * @since 1.0.5
     */
    public function selectMin($colName, $alias = 'min') {
        if ($colName !== null) {
            $xAlias = strlen(trim($alias)) != 0 ? trim($alias) : 'min';

            $this->select([
                $colName => [
                    'aggregate' => 'min',
                    'as' => $xAlias
                ]
            ]);
        }

        return $this;
    }
    /**
     * Sets the parameters which will be used in case the query will be prepared.
     * 
     * @param array $parameters An array that holds the parameters. The structure of 
     * the array depends on how the developer have implemented the method 
     * Connection::bind().
     * 
     * @since 1.0.2
     */
    public function setParams(array $parameters) {
        $this->params = $parameters;
    }
    /**
     * Sets the value of the property which is used to tell if the query 
     * will be prepared query or not.
     * 
     * This will mostly be used in case of raw SQL queries.
     * 
     * @param boolean $bool True to make a prepared query before execution. False 
     * to execute the query without preparation.
     * 
     * @since 1.0.2
     */
    public function setPrepareBeforeExec($bool) {
        $this->isPrepare = $bool === true;
    }
    /**
     * Sets a raw SQL query.
     * 
     * @param string $query SQL query.
     * 
     * @param boolean $multiQuery A boolean which is set to true if the query 
     * represents multi-query.
     * 
     * @since 1.0
     */
    public function setQuery($query, $multiQuery = false) {
        if ($query === null) {
            $this->query = '';
            $this->lastQueryType = '';

            return;
        }
        $trimmed = trim($query);
        $exp = explode(' ', $trimmed);

        if (!empty($exp)) {
            $this->lastQueryType = strtolower($exp[0]);
        }
        $this->isMultiQuery = $multiQuery === true;
        $this->query = $trimmed;
        $this->getSchema()->addQuery($trimmed, $this->getLastQueryType());
    }
    /**
     * Associate query generator with a database schema.
     * 
     * @param Database $schema The schema at which the generator will be associated 
     * with.
     * 
     * @since 1.0
     */
    public function setSchema(Database $schema) {
        $this->schema = $schema;
    }
    /**
     * Associate a table with the query builder.
     * 
     * @param Table $table The table that will be associated.
     * 
     * @since 1.0
     */
    public function setTable(Table $table) {
        if ($table !== null) {
            $table->getSelect()->clear();
        }
        $this->associatedTbl = $table;
    }
    /**
     * Sets the table at which the generator will generate queries for.
     * 
     * @param string $tblName The name of the table.
     * 
     * @return AbstractQuery The method will return the same instance at which 
     * the method is called on.
     * 
     * @since 1.0
     */
    public function table($tblName) {
        $tableObj = $this->getSchema()->getTable($tblName);
        $this->prevQueryObj = $this->copyQuery();

        if (strlen($this->query) != 0) {
            $this->setQuery($this->getQuery());
            $this->reset();
        }

        $this->setTable($tableObj);

        return $this;
    }
    /**
     * Constructs a query which will truncate a database table when executed.
     * 
     * @return AbstractQuery The method will return the same instance at which 
     * the method is called on.
     * 
     * @since 1.0
     */
    public function truncate() {
        $table = $this->getTable();
        $this->setQuery('truncate table `'.$table->getName().'`;');

        return $this;
    }
    /**
     * 
     * @param AbstractQuery $query
     * 
     * @param boolean $all
     * 
     * @return AbstractQuery The method will return the same instance at which 
     * the method is called on.
     */
    public function union(AbstractQuery $query, $all = false) {
        $queries = $this->getSchema()->getQueries();
        $count = count($queries);

        if ($count > 1 && $queries[$count - 2]['type'] == 'select' && $query->getLastQueryType() == 'select') {
            $uAll = $all === true;
            $unionStm = $uAll ? "\nunion all\n" : "\nunion\n";
            $this->setQuery($queries[$count - 2]['query'].$unionStm.$query->getQuery());
        }

        return $this;
    }
    /**
     * Constructs a query which can be used to update a record.
     * 
     * @param array $newColsVals An associative array that holds the columns and 
     * values. The indices of the array should be column keys and the values 
     * of the indices are the new values.
     * 
     * @return AbstractQuery The method should return the same instance at which 
     * the method is called on.
     * 
     * @since 1.0
     */
    public abstract function update(array $newColsVals);
    /**
     * Adds a 'where' condition to an existing select, update or delete query.
     * 
     * @param AbstractQuery|string $col The key of the column. This also can be an 
     * object of type AbstractQuery. The object is used to build a sub 
     * where condition.
     * 
     * @param string $cond A string such as '=' or '!='.
     * 
     * @param mixed $val The value at which column value will be evaluated againest.
     * 
     * @param string $joinCond An optional string which could be used to join 
     * more than one condition ('and' or 'or'). If not given, 'and' is used as 
     * default value.
     * 
     * @return AbstractQuery|MySQLQuery The method will return the same instance at which the 
     * method is called on.
     * 
     * @throws DatabaseException If one of the columns does not exist, the method 
     * will throw an exception.
     * 
     * @since 1.0
     */
    public function where($col, $cond = null, $val = null, $joinCond = 'and') {
        if ($col instanceof AbstractQuery) {
            //Prev where was a sub where
            $this->getTable()->getSelect()->addWhere($col, null, null, $joinCond);
        } else {
            // A where condition based on last select, delete or update
            $lastQType = $this->getLastQueryType();
            $table = $this->getTable();
            $tableName = $table->getName();

            if ($lastQType == 'select' || $lastQType == 'delete' || $lastQType == 'update') {
                $colObj = $table->getColByKey($col);

                if ($colObj === null) {
                    $colsKeys = $table->getColsKeys();
                    $message = "The table '$tableName' has no column with key '$col'. Available columns: ".implode(',', $colsKeys);
                    throw new DatabaseException($message);
                }
                $colObj->setWithTablePrefix(true);
                $colName = $colObj->getName();
                $cleanVal = $colObj->cleanValue($val);
                $table->getSelect()->addWhere($colName, $cleanVal, $cond, $joinCond);
            } else {
                throw new DatabaseException("Last query must be a 'select', delete' or 'update' in order to add a 'where' condition.");
            }
        }

        return $this;
    }
    /**
     * Constructs a 'where between ' condition.
     * 
     * @param string $col The key of the column that the condition will be based 
     * on.
     * 
     * @param mixed $firstVal The left hand side operand of the between condition.
     * 
     * @param mixed $secondVal The right hand side operand of the between condition.
     * 
     * @param string $joinCond An optional string which could be used to join 
     * more than one condition ('and' or 'or'). If not given, 'and' is used as 
     * default value.
     * 
     * @param boolean $not If set to true, the 'between' condition will be set 
     * to 'not between'.
     * 
     * @return AbstractQuery|MySQLQuery The method will return the same instance at which the 
     * method is called on.
     * 
     * @throws DatabaseException If the table has no column with given key name, 
     * the method will throw an exception.
     * 
     * @since 1.0.3
     */
    public function whereBetween($col, $firstVal, $secondVal, $joinCond = 'and', $not = false) {
        $this->_addWhere([
            'col-key' => $col,
            'first-value' => $firstVal,
            'second-value' => $secondVal,
            'join-cond' => $joinCond,
            'not' => $not,
            'func' => 'between'
        ]);

        return $this;
    }
    private function _addWhere($options) {
        $lastQType = $this->getLastQueryType();
        $table = $this->getTable();
        $tableName = $table->getName();
        $col = $options['col-key'];
        $joinCond = $options['join-cond'];
        $not = isset($options['not']) ? $options['not'] : false;
        
        if ($lastQType == 'select' || $lastQType == 'delete' || $lastQType == 'update') {
            $colObj = $table->getColByKey($col);

            if ($colObj === null) {
                $colsKeys = $table->getColsKeys();
                $message = "The table '$tableName' has no column with key '$col'. Available columns: ".implode(',', $colsKeys);
                throw new DatabaseException($message);
            }
            $colObj->setWithTablePrefix(true);
            $colName = $colObj->getName();
            
            if ($options['func'] == 'between') {
                $firstCleanVal = $colObj->cleanValue($options['first-value']);
                $secCleanVal = $colObj->cleanValue($options['second-value']);
                $this->getTable()->getSelect()->addWhereBetween($colName, $firstCleanVal, $secCleanVal, $joinCond, $not);
            } else if ($options['func'] == 'in') {
                $cleanedVals = $colObj->cleanValue($options['values']);
                $this->getTable()->getSelect()->addWhereIn($colName, $cleanedVals, $joinCond, $not);
            } else if ($options['func'] == 'left') {
                $cleanVal = $colObj->cleanValue($options['value']);
                $cleanType = gettype($cleanVal);
                $charsCount = $options['count'];
                $cond = $options['condition'];
                
                if ($cleanType == 'string' || $cleanType == 'array') {
                    $this->getTable()->getSelect()->addLeft($colName, $charsCount, $cond, $cleanVal, $joinCond);
                }
            } else if ($options['func'] == 'like') {
                
                $cleanVal = $colObj->cleanValue($options['value']);

                if (gettype($cleanVal) == 'string') {
                    $this->getTable()->getSelect()->addLike($colName, $cleanVal, $joinCond, $not);
                }
            } else if ($options['func'] == 'null') {
                $this->getTable()->getSelect()->addWhereNull($colName, $joinCond, $not);
            } else if ($options['func'] == 'right') {
                $cleanVal = $colObj->cleanValue($options['value']);
                $cleanType = gettype($cleanVal);
                $charsCount = $options['count'];
                $cond = $options['condition'];
                
                if ($cleanType == 'string' || $cleanType == 'array') {
                    $this->getTable()->getSelect()->addRight($colName, $charsCount, $cond, $cleanVal, $joinCond);
                }
            }
        } else {
            throw new DatabaseException("Last query must be a 'select', delete' or 'update' in order to add a 'where' condition.");
        }
    }
    /**
     * Constructs a 'where in()' condition.
     * 
     * @param string $col The key of the column that the condition will be based 
     * on.
     * 
     * @param array $vals An array that holds the values that will be checked.
     * 
     * @param string $joinCond An optional string which could be used to join 
     * more than one condition ('and' or 'or'). If not given, 'and' is used as 
     * default value.
     * 
     * @param boolean $not If set to true, the 'in' condition will be set 
     * to 'not in'.
     * 
     * @return AbstractQuery|MySQLQuery The method will return the same instance at which the 
     * method is called on.
     * 
     * @throws DatabaseException If the table has no column with given key name, 
     * the method will throw an exception.
     * 
     * @since 1.0.3
     */
    public function whereIn($col, array $vals, $joinCond = 'and', $not = false) {
        $this->_addWhere([
            'col-key' => $col,
            'values' => $vals,
            'join-cond' => $joinCond,
            'not' => $not,
            'func' => 'in'
        ]);

        return $this;
    }
    /**
     * Adds a 'left()' condition to the 'where' part of the select.
     * 
     * @param string $col The key of the column that the condition will be based 
     * on. Note that the column type must be a string type such as varchar or the 
     * call to the method will be ignored.
     * 
     * @param int $charsCount The number of characters that will be taken from 
     * the left of the column value.
     * 
     * @param string $cond A condition at which the comparison will be based on. 
     * can only have 4 values, '=', '!=', 'in' and 'not in'.
     * 
     * @param string|array $val The value at which the condition will be compared with. 
     * This also can be an array of values if the condition is 'in' or 'not in'.
     * 
     * @param string $joinCond An optional string which could be used to join 
     * more than one condition ('and' or 'or'). If not given, 'and' is used as 
     * default value.
     * 
     * @return AbstractQuery|MySQLQuery The method will return the same instance at which the 
     * method is called on.
     * 
     * @throws DatabaseException If the table has no column with given key name, 
     * the method will throw an exception.
     * 
     * @since 1.0.4
     */
    public function whereLeft($col, $charsCount, $cond, $val, $joinCond = 'and') {
        $this->_addWhere([
            'col-key' => $col,
            'join-cond' => $joinCond,
            'func' => 'left',
            'count' => $charsCount,
            'value' => $val,
            'condition' => $cond
        ]);

        return $this;
    }
    /**
     * Constructs a 'where like' condition.
     * 
     * @param string $col The key of the column that the condition will be based 
     * on. Note that the column type must be a string type such as varchar or the 
     * call to the method will be ignored.
     * 
     * @param string $val The value at which the 'like' condition will be 
     * based on.
     * 
     * @param string $joinCond An optional string which could be used to join 
     * more than one condition ('and' or 'or'). If not given, 'and' is used as 
     * default value.
     * 
     * @param boolean $not If set to true, the 'like' condition will be set 
     * to 'not like'.
     * 
     * @return AbstractQuery|MySQLQuery The method will return the same instance at which the 
     * method is called on.
     * 
     * @throws DatabaseException If the table has no column with given key name, 
     * the method will throw an exception.
     * 
     * @since 1.0.4
     */
    public function whereLike($col, $val, $joinCond = 'and', $not = false) {
        $this->_addWhere([
            'col-key' => $col,
            'join-cond' => $joinCond,
            'func' => 'like',
            'value' => $val,
            'not' => $not
        ]);

        return $this;
    }
    /**
     * Constructs a 'where not between ' condition.
     * 
     * @param string $col The key of the column that the condition will be based 
     * on.
     * 
     * @param mixed $firstVal The left hand side operand of the between condition.
     * 
     * @param mixed $secVal The right hand side operand of the between condition.
     * 
     * @param string $joinCond An optional string which could be used to join 
     * more than one condition ('and' or 'or'). If not given, 'and' is used as 
     * default value.
     * 
     * @return AbstractQuery|MySQLQuery The method will return the same instance at which the 
     * method is called on.
     * 
     * @throws DatabaseException If the table has no column with given key name, 
     * the method will throw an exception.
     * 
     * @since 1.0.3
     */
    public function whereNotBetween($col, $firstVal, $secVal, $joinCond = 'and') {
        return $this->whereBetween($col, $firstVal, $secVal, $joinCond, true);
    }
    /**
     * Constructs a 'where not in()' condition.
     * 
     * @param string $col The key of the column that the condition will be based 
     * on.
     * 
     * @param array $vals An array that holds the values that will be checked.
     * 
     * @param string $joinCond An optional string which could be used to join 
     * more than one condition ('and' or 'or'). If not given, 'and' is used as 
     * default value.
     * 
     * @return AbstractQuery|MySQLQuery The method will return the same instance at which the 
     * method is called on.
     * 
     * @throws DatabaseException If the table has no column with given key name, 
     * the method will throw an exception.
     * 
     * @since 1.0.3
     */
    public function whereNotIn($col, array $vals, $joinCond = 'and') {
        return $this->whereIn($col, $vals, $joinCond, true);
    }
    /**
     * Constructs a 'where like' condition.
     * 
     * @param string $col The key of the column that the condition will be based 
     * on. Note that the column type must be a string type such as varchar or the 
     * call to the method will be ignored.
     * 
     * @param string $val The value at which the 'like' condition will be 
     * based on.
     * 
     * @param string $joinCond An optional string which could be used to join 
     * more than one condition ('and' or 'or'). If not given, 'and' is used as 
     * default value.
     * 
     * @return AbstractQuery|MySQLQuery The method will return the same instance at which the 
     * method is called on.
     * 
     * @throws DatabaseException If the table has no column with given key name, 
     * the method will throw an exception.
     */
    public function whereNotLike($col, $val, $joinCond = 'and') {
        return $this->whereLike($col, $val, $joinCond, true);
    }
    /**
     * Constructs a 'where is not null' condition.
     * 
     * @param string $col The key of the column that the condition will be based 
     * on.
     * 
     * @param string $join An optional string which could be used to join 
     * more than one condition ('and' or 'or'). If not given, 'and' is used as 
     * default value.
     * 
     * @return AbstractQuery|MySQLQuery The method will return the same instance at which the 
     * method is called on.
     * 
     * @throws DatabaseException If the table has no column with given key name, 
     * the method will throw an exception.
     * 
     * @since 1.0.4
     */
    public function whereNotNull($col, $join = 'and') {
        return $this->whereNull($col, $join, true);
    }
    /**
     * Constructs a 'where is null' condition.
     * 
     * @param string $col The key of the column that the condition will be based 
     * on.
     * 
     * @param string $join An optional string which could be used to join 
     * more than one condition ('and' or 'or'). If not given, 'and' is used as 
     * default value.
     * 
     * @param boolean $not If set to true, the 'is null' condition will be set 
     * to 'is not null'.
     * 
     * @return AbstractQuery|MySQLQuery The method will return the same instance at which the 
     * method is called on.
     * 
     * @throws DatabaseException If the table has no column with given key name, 
     * the method will throw an exception.
     * 
     * @since 1.0.4
     */
    public function whereNull($col, $join = 'and', $not = false) {
        $this->_addWhere([
            'col-key' => $col,
            'join-cond' => $join,
            'not' => $not,
            'func' => 'null'
        ]);

        return $this;
    }
    /**
     * Adds a 'right()' condition to the 'where' part of the select.
     * 
     * @param string $col The key of the column that the condition will be based 
     * on. Note that the column type must be a string type such as varchar or the 
     * call to the method will be ignored.
     * 
     * @param int $charsCount The number of characters that will be taken from 
     * the right of the column value.
     * 
     * @param string $cond A condition at which the comparison will be based on. 
     * can only have 4 values, '=', '!=', 'in' and 'not in'.
     * 
     * @param string|array $val The value at which the condition will be compared with. 
     * This also can be an array of values if the condition is 'in' or 'not in'.
     * 
     * @param string $joinCond An optional string which could be used to join 
     * more than one condition ('and' or 'or'). If not given, 'and' is used as 
     * default value.
     * 
     * @return AbstractQuery|MySQLQuery The method will return the same instance at which the 
     * method is called on.
     * 
     * @throws DatabaseException If the table has no column with given key name, 
     * the method will throw an exception.
     * 
     * @since 1.0.4
     */
    public function whereRight($col, $charsCount, $cond, $val, $joinCond = 'and') {
        $this->_addWhere([
            'col-key' => $col,
            'join-cond' => $joinCond,
            'func' => 'right',
            'count' => $charsCount,
            'value' => $val,
            'condition' => $cond
        ]);

        return $this;
    }
    private function _getColsToSelect() {
        $thisTable = $this->getTable();

        $rightCols = $thisTable->getRight()->getSelect()->getColsStr();

        if (!($thisTable->getLeft() instanceof JoinTable)) {
            $leftCols = $thisTable->getLeft()->getSelect()->getColsStr();
        } else {
            $leftCols = '*';
        }
        $thisCols = $thisTable->getSelect()->getColsStr();
        $columnsToSelect = '';

        if ($thisCols != '*') {
            $columnsToSelect .= $thisCols;
        }

        if ($leftCols != '*') {
            if (strlen($columnsToSelect) != 0) {
                $columnsToSelect .= ", $leftCols";
            } else {
                $columnsToSelect = $leftCols;
            }
        }

        if ($rightCols != '*') {
            if (strlen($columnsToSelect) != 0) {
                $columnsToSelect .= ", $rightCols";
            } else {
                $columnsToSelect = $rightCols;
            }
        }

        return $columnsToSelect;
    }
}
