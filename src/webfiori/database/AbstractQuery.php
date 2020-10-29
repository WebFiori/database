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
/**
 * A base class that can be used to build SQL queries.
 * 
 * @author Ibrahim
 * 
 * @version 1.0
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
    public function copyQuery() {
        $driver = $this->getSchema()->getConnectionInfo()->getDatabaseType();

        if ($driver == 'mysql') {
            $copy = new MySQLQuery();
            $copy->limit = $this->limit;
            $copy->offset = $this->offset;
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
     * Removes a record from the active table.
     * 
     * @return AbstractQuery The method should return the same instance at which 
     * the method is called on.
     * 
     * @since 1.0
     */
    public abstract function delete();
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
     * Constructs a query that can be used to drop a column.
     * 
     * @param string $colKey The name of column key as specified when the column 
     * was added to the table.
     * 
     * @return AbstractQuery The method should return the same instance at which 
     * the method is called on.
     * 
     * @since 1.0
     */
    public abstract function dropCol($colKey);
    /**
     * Constructs a query which can be used to drop a primary key constrain from a 
     * table. 
     * 
     * @param string $pkName The name of the primary key.
     * 
     * @return AbstractQuery The method should return the same instance at which 
     * the method is called on.
     */
    public abstract function dropPrimaryKey($pkName = null);

    /**
     * Execute the generated SQL query.
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
            $this->getSchema()->execute();
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

        if ($lastQType == 'select' || $lastQType == 'delete' || $lastQType == 'update') {
            $whereExp = $this->getTable()->getSelect()->getWhereStr();

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
     * @return Table The associated table as an object.
     * 
     * @throws DatabaseException If no table was associated with the query builder, 
     * the method will throw an exception.
     * 
     * @since 1.0
     */
    public function getTable() {
        if ($this->associatedTbl === null) {
            throw new DatabaseException('No associated table.');
        }

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

        $alias = $leftTable->getName();

        if ($leftTable instanceof JoinTable) {
            $nameAsInt = intval(substr($alias, 1));
            $alias = 'T'.(++$nameAsInt);
        }


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
     * with existing one.
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
                $leftCol->setWithTablePrefix(true);

                if ($table->getLeft() instanceof JoinTable) {
                    $leftCol->setOwner($table);
                    $leftColName = $leftCol->getName();
                    $leftCol->setOwner($leftCol->getPrevOwner());
                } else {
                    $leftColName = $leftCol->getName();
                }

                $rightCol = $table->getRight()->getColByKey($rightCol);

                if ($rightCol instanceof Column) {
                    $rightCol->setWithTablePrefix(true);
                    $rightColName = $rightCol->getName();
                    $cond = new Condition($leftColName, $rightColName, $cond);
                    $table->addJoinCondition($cond, $joinWith);
                } else {
                    $tblName = $table->getRight()->getName();
                    throw new DatabaseException("The table $tblName has no column with key '$rightCol'.");
                }
            } else {
                $tblName = $table->getLeft()->getName();
                throw new DatabaseException("The table $tblName has no column with key '$leftCol'.");
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
     * will be selected. To give an alias for a column, simply supply the alias 
     * as a value for the key.
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
        $thisTable = $this->getTable();
        $thisCols = $thisTable->getSelect()->getColsStr();

        if ($thisTable instanceof JoinTable) {
            $columnsToSelect = $this->_getColsToSelect();

            $tableSQL = $this->getTable()->toSQL(true);

            if (strlen($columnsToSelect) == 0) {
                $selectVal = substr($selectVal, 0, strlen($selectVal) - strlen($this->getTable()->getName()));
                $this->setQuery($selectVal.$tableSQL);
            } else {
                if ($thisTable->getLeft() instanceof JoinTable && strlen($columnsToSelect) == 0) {
                    $this->setQuery("select $columnsToSelect from $tableSQL as Temp");
                } else {
                    if (strlen($columnsToSelect) != 0) {
                        $this->setQuery("select $columnsToSelect from ".$tableSQL);
                    } else {
                        $this->setQuery("select $thisCols from ".$tableSQL);
                    }
                }
            }
        } else {
            $this->setQuery($selectVal);
        }

        return $this;
    }
    /**
     * Sets a raw SQL query.
     * 
     * @param string $query SQL query.
     * 
     */
    public function setQuery($query) {
        if ($query === null) {
            $this->query = '';
            $this->lastQueryType = '';

            return;
        }
        $exp = explode(' ', $query);

        if (!empty($exp)) {
            $this->lastQueryType = $exp[0];
        }
        $this->query = $query;
        $this->getSchema()->addQuery($query, $this->getLastQueryType());
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
     * @return MySQLQuery The method will return the same instance at which the 
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
                    throw new DatabaseException("The table '$tableName' has no column with key '$col'.");
                }
                $colObj->setWithTablePrefix(true);
                $colName = $colObj->getName();
                $cleanVal = $colObj->cleanValue($val);
                $this->getTable()->getSelect()->addWhere($colName, $cleanVal, $cond, $joinCond);
            } else {
                throw new DatabaseException("Last query must be a 'select', delete' or 'update' in order to add a 'where' condition.");
            }
        }

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
