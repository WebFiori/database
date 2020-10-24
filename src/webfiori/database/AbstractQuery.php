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
use webfiori\database\mysql\MySQLTable;
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
     * @var type 
     * 
     * @since 1.0
     */
    private $joins;
    /**
     *
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
     *
     * @var Table|null 
     * 
     * @since 1.0
     */
    private $associatedTbl;
    /**
     *
     * @var Table|null 
     * 
     * @since 1.0
     */
    private $prevAssociatedTable;
    /**
     *
     * @var WhereExpression 
     * 
     * @since 1.0
     */
    private $whereExp;
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
    public function addJoin($joinCond, $join) {
        if (count($this->joins) != 0) {
            if (!in_array($join, ['and', 'or'])) {
                $join = 'and';
            }
            $this->joins[] = $joinCond;
        }
        $this->joins[] = $joinCond;
    }
    /**
     * 
     * @param AbstractQuery $query
     * @return AbstractQuery
     */
    public function join(AbstractQuery $query, $joinType = 'join') {
        $joinTable = new JoinTable($this->getPrevTable(), $query->getTable(), $joinType);
        $this->setTable($joinTable);
        return $this;
    }
    /**
     * 
     * @param type $col1
     * @param type $col2
     * @param type $cond
     * @return AbstractQuery
     */
    public function on($col1, $col2, $cond = '=', $joinWith = 'and') {
        $table = $this->getTable();
        if ($table instanceof JoinTable) {
            $leftCol = $table->getLeft()->getColByKey($col1);
            if ($leftCol instanceof Column) {
                $leftCol->setWithTablePrefix(true);
                $rightCol = $table->getRight()->getColByKey($col2);
                if ($rightCol instanceof Column) {
                    $rightCol->setWithTablePrefix(true);
                    $cond = new Condition($leftCol->getName(), $rightCol->getName(), $cond);
                    $table->addJoinCondition($cond, $joinWith);
                } else {
                    $tblName = $table->getRight()->getName();
                    throw new DatabaseException("The table $tblName has no column with key '$col2'.");
                }
            } else {
                $tblName = $table->getLeft()->getName();
                throw new DatabaseException("The table $tblName has no column with key '$col1'.");
            }
        } else {
            throw new DatabaseException("The 'on' condition can be only used with join tables.");
        }
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
     * 
     * @param type $leftOpOrExp
     * @param type $rightOp
     * @param type $cond
     * @param string $join
     */
    public function addWhere($leftOpOrExp, $rightOp = null, $cond = null, $join = 'and') {
        if (!in_array($join, ['and', 'or'])) {
            $join = 'and';
        }

        if ($leftOpOrExp instanceof AbstractQuery) {
            $parentWhere = new WhereExpression('');
            $this->whereExp->setJoinCondition($join);
            $this->whereExp->setParent($parentWhere);

            $this->whereExp = $parentWhere;
        } else {
            if ($this->whereExp === null) {
                $this->whereExp = new WhereExpression('');
            }
            $condition = new Condition($leftOpOrExp, $rightOp, $cond);
            $this->whereExp->addCondition($condition, $join);
        }
    }
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

            $copy->whereExp = $this->whereExp;

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
        $this->setQuery('drop table `'.$table->getName().'`;');

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
    public function getJoinStatement() {
        return implode(' ', $this->joins);
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
     * Returns the generated SQL query.
     * 
     * @return string Returns the generated query as string.
     * 
     * @since 1.0
     */
    public function getQuery() {
        $retVal = $this->query;

        if ($this->whereExp !== null) {
            $retVal .= ' where '.$this->getWhereStatement();
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
     * 
     * @return null|Table
     * 
     * @since 1.0
     */
    public function getPrevTable() {
        return $this->prevAssociatedTable;
    }
    public function getWhereStatement() {
        return $this->whereExp->getValue();
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
        $this->whereExp = null;
        $this->lastQueryType = '';
        $this->limit = -1;
        $this->offset = -1;
    }
    /**
     * Constructs a query that can be used to get records from a table.
     * 
     * @param array $cols An array that holds the keys of the columns that will 
     * be selected.
     * 
     * @return AbstractQuery The method should return the same instance at which 
     * the method is called on.
     * 
     * @since 1.0
     */
    public abstract function select($cols = ['*']);
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
        $this->prevAssociatedTable = $this->associatedTbl;
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
        if (strlen($this->query) != 0) {
            $this->setQuery($this->getQuery());
            $this->reset();
        }
        $this->setTable($this->getSchema()->getTable($tblName));

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
     * Build a where condition.
     * 
     * This method must be implemented in a way it builds the 'where' part of 
     * the query. 
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
     * @param string $joinCond An optional string which can be used to join 
     * multiple where conditions. If not provided, 'and' will be used by default.
     * 
     * @return AbstractQuery The method should be implemented in a way it returns 
     * the same instance at which the method is called on.
     * 
     * @since 1.0
     */
    public abstract function where($col, $cond = null, $val = null, $joinCond = 'and');
}
