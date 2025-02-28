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

use Throwable;
use webfiori\database\mssql\MSSQLQuery;
use webfiori\database\mysql\MySQLQuery;
/**
 * A base class that can be used to build SQL queries.
 * 
 * @author Ibrahim
 * 
 * @version 1.0.4
 */
abstract class AbstractQuery {
    private $tempBinding;
    /**
     *
     * @var Table|null 
     * 
     * @since 1.0
     */
    private $associatedTbl;
    /**
     * 
     * @var InsertHelper
     */
    private $insertHelper;
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
        $this->tempBinding = [];
    }
    public abstract function addBinding(Column $col, $value);
    public abstract function getBindings() : array ;
    public abstract function resetBinding();
    public abstract function setBindings(array $binding, string $merge = 'none');
    /**
     * Constructs a query that can be used to add a column to a database table.
     * 
     * The developer should implement this method in a way it creates SQL query 
     * that can be used to add a column to a table.
     * 
     * @param string $colKey The name of column key as specified when the column 
     * was added to the table.
     * 
     * @param string|null $location The name of the column that the new column will be added after.
     * 
     * @return AbstractQuery The method should return the same instance at which 
     * the method is called on.
     * 
     * @since 1.0
     */
    public abstract function addCol(string $colKey, ?string $location = null);
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
    public function addForeignKey(string $keyName) {
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
    public abstract function addPrimaryKey(string $pkName, array $pkCols);

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
     * @param string|null $cond A string that represents the condition at which column
     * value will be evaluated against (e.g. '=', '&lt;&gt;', '&lt;' etc...). Can be ignored if first parameter is of
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
    public function andWhere($col, $val = null, string $cond = '=') {
        return $this->where($col, $val, $cond, 'and');
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
     * @return AbstractQuery
     * 
     * @since 1.0
     */
    public abstract function copyQuery() : AbstractQuery ;
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
     */
    public function dropCol($colKey) {
        $table = $this->getTable();
        $tblName = $table->getName();
        $colObj = $table->getColByKey($colKey);

        if ($colObj === null) {
            $table->addColumns([
                $colKey => ['type' => DataType::VARCHAR]
            ]);
            $colObj = $table->getColByKey($colKey);
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
    public abstract function dropPrimaryKey(?string $pkName = null);
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
            $errQuery = $this->getSchema()->getLastQuery();
            throw new DatabaseException($ex->getMessage(), $ex->getCode(), $errQuery, $ex);
        }
    }
    /**
     * 
     * @return InsertBuilder|null
     */
    public function getInsertBuilder() {
        return $this->insertHelper;
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
     * @return bool The method will return true if the query is a multi-query. 
     * False if not.
     * 
     * @since 1.0.1
     */
    public function isMultiQuery() {
        return $this->isMultiQuery;
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
    public abstract function modifyCol($colKey, ?string $location = null);
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
    public function on(string $leftCol, string $rightCol, $cond = '=', $joinWith = 'and') {
        $table = $this->getTable();

        if ($table instanceof JoinTable) {
            $leftColObj = $table->getLeft()->getColByKey($leftCol);
            
            if ($leftColObj === null) {
                $table->getLeft()->addColumns([
                    $leftCol => ['type' => DataType::VARCHAR]
                ]);
                $leftColObj = $table->getLeft()->getColByKey($leftCol);
            }

            $leftColObj->setWithTablePrefix(false);

            if ($leftColObj->getOwner() instanceof JoinTable && $leftColObj->getAlias() !== null) {
                $leftColObj->setName($leftColObj->getAlias());
            }
            $leftColName = $leftColObj->getOwner()->getName().'.'.$leftColObj->getOldName();

            $rightColObj = $table->getRight()->getColByKey($rightCol);

            if ($rightColObj === null) {
                $table->getRight()->addColumns([
                    $rightCol => ['type' => DataType::VARCHAR]
                ]);
                $rightColObj = $table->getRight()->getColByKey($rightCol);
            }
            $rightColObj->setWithTablePrefix(false);
            $rightColName = $rightColObj->getOwner()->getName().'.'.$rightColObj->getOldName();
            $cond = new Condition($leftColName, $rightColName, $cond);
            $table->addJoinCondition($cond, $joinWith);
            
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
    public function orWhere($col, $val = null, string $cond = '=') {
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
     * @return AbstractQuery The method will return the same instance at which 
     * the method is called on.
     * 
     * @since 1.0
     */
    public function page(int $num, int $itemsCount) {
        if ($num > 0 && $itemsCount > 0) {
            $this->limit($itemsCount);
            $this->offset(($num - 1) * $itemsCount);
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
    public function selectCount(?string $colName = null, $alias = 'count') {
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
    public function setInsertBuilder(InsertBuilder $builder) {
        $this->insertHelper = $builder;
        $this->setQuery($builder->getQuery());
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
     * @param Table|null $table The table that will be associated.
     * 
     * @param bool $clearSelect If set to true, select statement of the table
     * will be cleared.
     * 
     * @since 1.0
     */
    public function setTable(?Table $table = null, bool $clearSelect = true) {
        if ($table !== null && $clearSelect) {
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
        $tableObj = $this->checkIsClass($tblName);

        if ($tableObj === null) {
            $tableObj = $this->getSchema()->getTable($tblName);

            if ($tableObj === null) {
                $tableObj = $this->getSchema()->createBlueprint($tblName);
            }
        }
        $this->getSchema()->addTable($tableObj);
        $this->prevQueryObj = $this->copyQuery();
        $this->prevQueryObj->resetBinding();
        
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
            $whereExpr = $query->getTable()->getSelect()->getWhereExpr();
            if ($whereExpr !== null) {
                $whereExpr->setValue('');
            }
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
     * @param mixed $val The value at which column value will be evaluated againest.
     *
     * @param string $cond A string such as '=' or '!='.
     * 
     * @param string $joinCond An optional string which could be used to join 
     * more than one condition ('and' or 'or'). If not given, 'and' is used as 
     * default value.
     * 
     * @return AbstractQuery|MySQLQuery|MSSQLQuery The method will return the same instance at which the
     * method is called on.
     *
     * 
     * @since 1.0
     */
    public function where($col, mixed $val = null, string $cond = '=', string $joinCond = 'and') : AbstractQuery {
        if ($col instanceof AbstractQuery) {
            //Prev where was a sub where
            $this->getTable()->getSelect()->addWhere($col, null, null, $joinCond);
        } else {
            // A where condition based on last select, delete or update
            $lastQType = $this->getLastQueryType();
            $table = $this->getTable();

            if ($lastQType == 'select' || $lastQType == 'delete' || $lastQType == 'update') {
                $colObj = $table->getColByKey($col);

                if ($colObj === null) {
                    $table->addColumns([
                        $col => ['type' => $this->getColType($val)]
                    ]);
                    $colObj = $table->getColByKey($col);
                }
                $colObj->setWithTablePrefix(true);
                $colName = $colObj->getName();
                $cleanVal = $colObj->cleanValue($val);
                if ($val !== null) {
                    $this->addBinding($colObj, $val);
                }
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
     * 
     * @since 1.0.3
     */
    public function whereBetween($col, $firstVal, $secondVal, $joinCond = 'and', $not = false) {
        $this->addWhereHelper([
            'col-key' => $col,
            'first-value' => $firstVal,
            'second-value' => $secondVal,
            'join-cond' => $joinCond,
            'not' => $not,
            'func' => 'between'
        ]);

        return $this;
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
     */
    public function whereIn($col, array $vals, $joinCond = 'and', $not = false) {
        $this->addWhereHelper([
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
     * 
     * @param string|array $val The value at which the condition will be compared with. 
     * This also can be an array of values if the condition is 'in' or 'not in'.
     * 
     * @param string $cond A condition at which the comparison will be based on. 
     * can only have 4 values, '=', '!=', 'in' and 'not in'.
     * 
     * @param string $joinCond An optional string which could be used to join 
     * more than one condition ('and' or 'or'). If not given, 'and' is used as 
     * default value.
     * 
     * @return AbstractQuery|MySQLQuery The method will return the same instance at which the 
     * method is called on.
     * 
     */
    public function whereLeft($col, $charsCount, $cond, $val, $joinCond = 'and') {
        $this->addWhereHelper([
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
     */
    public function whereLike($col, $val, $joinCond = 'and', $not = false) {
        $this->addWhereHelper([
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
     */
    public function whereNull($col, $join = 'and', $not = false) {
        $this->addWhereHelper([
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
     */
    public function whereRight($col, $charsCount, $cond, $val, $joinCond = 'and') {
        $this->addWhereHelper([
            'col-key' => $col,
            'join-cond' => $joinCond,
            'func' => 'right',
            'count' => $charsCount,
            'value' => $val,
            'condition' => $cond
        ]);

        return $this;
    }
    private function getColType($phpVar) {
        $type = gettype($phpVar);
        if ($type == 'integer') {
            return 'int';
        } else if ($type == 'double') {
            return 'decimal';
        } else if ($type == 'boolean') {
            return $type;
        } else {
            return 'varchar';
        }
    }
    private function addWhereHelper($options) {
        $lastQType = $this->getLastQueryType();
        $table = $this->getTable();

        $col = $options['col-key'];
        $joinCond = $options['join-cond'];
        $not = isset($options['not']) ? $options['not'] : false;

        if ($lastQType == 'select' || $lastQType == 'delete' || $lastQType == 'update') {
            $colObj = $table->getColByKey($col);
            $val = '';
            if (isset($options['value'])) {
                $val = $options['value'];
            } else if (isset ($options['first-value'])) {
                $val = $options['first-value'];
            }
            if ($colObj === null) {
                $table->addColumns([
                    $col => ['type' => $this->getColType($val)]
                ]);
                $colObj = $table->getColByKey($col);
            }
            $colObj->setWithTablePrefix(true);
            $colName = $colObj->getName();

            if ($options['func'] == 'between') {
                $firstCleanVal = $options['first-value'];
                $secCleanVal = $options['second-value'];
                $this->addBinding($colObj, $firstCleanVal);
                $this->addBinding($colObj, $secCleanVal);
                $this->getTable()->getSelect()->addWhereBetween($colName, $joinCond, $not);
            } else if ($options['func'] == 'in') {
                if (count($options['values']) != 0) {
                    foreach ($options['values'] as $val) {
                        $this->addBinding($colObj, $val);
                    }
                
                    $this->getTable()->getSelect()->addWhereIn($colName, $options['values'], $joinCond, $not);
                }
            } else if ($options['func'] == 'left') {
                $cleanVal = $options['value'];
                $cleanType = gettype($cleanVal);
                $charsCount = $options['count'];
                $cond = $options['condition'];

                if ($cleanType == 'string' || $cleanType == 'array') {
                    if ($cleanType == 'string') {
                        $this->addBinding($colObj, $cleanVal);
                    } else {
                        foreach ($cleanVal as $val) {
                            $this->addBinding($colObj, $val);
                        }
                    }
                    $this->getTable()->getSelect()->addLeft($colName, $charsCount, $cond, $cleanVal, $joinCond);
                }
            } else if ($options['func'] == 'like') {
                $cleanVal = $options['value'];

                if (gettype($cleanVal) == 'string') {
                    $this->addBinding($colObj, $cleanVal);
                    $this->getTable()->getSelect()->addLike($colName, $joinCond, $not);
                }
            } else if ($options['func'] == 'null') {
                $this->getTable()->getSelect()->addWhereNull($colName, $joinCond, $not);
            } else if ($options['func'] == 'right') {
                $cleanVal = $options['value'];
                $cleanType = gettype($cleanVal);
                $charsCount = $options['count'];
                $cond = $options['condition'];

                if ($cleanType == 'string' || $cleanType == 'array') {
                    if ($cleanType == 'string') {
                        $this->addBinding($colObj, $cleanVal);
                    } else {
                        foreach ($cleanVal as $val) {
                            $this->addBinding($colObj, $val);
                        }
                    }
                    $this->getTable()->getSelect()->addRight($colName, $charsCount, $cond, $cleanVal, $joinCond);
                }
            }
        } else {
            throw new DatabaseException("Last query must be a 'select', delete' or 'update' in order to add a 'where' condition.");
        }
    }
    private function checkIsClass($str) {
        if (class_exists($str)) {
            try {
                $clazz = new $str();

                if ($clazz instanceof Table) {
                    return $clazz;
                }
            } catch (Throwable $ex) {
            }
        }
    }
    private function getColsToSelect() {
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
