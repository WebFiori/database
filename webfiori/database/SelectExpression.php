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

use InvalidArgumentException;

/**
 * A class which is used to build the select expression of a select query.
 *
 * @author Ibrahim
 * 
 * @version 1.0.2
 */
class SelectExpression extends Expression {
    /**
     *
     * @var array
     * 
     * @since 1.0 
     */
    private $groupByCols;
    /**
     *
     * @var array
     * 
     * @since 1.0 
     */
    private $orderByCols;
    /**
     *
     * @var array
     * 
     * @since 1.0 
     */
    private $selectCols;
    /**
     *
     * @var Table
     * 
     * @since 1.0 
     */
    private $table;
    /**
     *
     * @var WhereExpression 
     * 
     * @since 1.0
     */
    private $whereExp;
    /**
     * Constructs a new instance of the class.
     * 
     * @param Table $table The table at which the select expression will be 
     * based on.
     * 
     * @since 1.0
     */
    public function __construct(Table $table) {
        parent::__construct('');
        $this->table = $table;
        $this->selectCols = [];
        $this->orderByCols = [];
        $this->groupByCols = [];
    }
    /**
     * Adds new column to the set of columns in the select.
     * 
     * @param string $colKey The key of the column as specified when the column 
     * was added to associated table.
     * 
     * @param string|null $options An optional alias for the column.
     * 
     * @throws DatabaseException If column does not exist in the table that the 
     * select is based on.
     * 
     * @since 1.0
     */
    public function addColumn($colKey, $options = null) {
        if ($colKey != '*') {
            $colObj = $this->getTable()->getColByKey($colKey);

            if ($colObj === null) {
                $tblName = $this->getTable()->getName();
                throw new DatabaseException("The table $tblName has no column with key '$colKey'.");
            }
            $opArr = [
                'obj' => $colObj,
            ];
            $this->_setAlias($colObj, $options, $opArr);

            if (isset($options['aggregate'])) {
                $opArr['aggregate'] = $options['aggregate'];
            }
            $this->selectCols[$colKey] = $opArr;
        }
    }
    /**
     * Adds an expression as a part of the select expression.
     * 
     * @param Expression $expr An object that represents the expression.
     */
    public function addExpression(Expression $expr) {
        $this->selectCols[hash('sha256', $expr->getValue())] = ['obj' => $expr];
    }
    /**
     * Adds a 'left()' condition to the 'where' part of the select.
     * 
     * @param string $colName The name of the column that the condition will be 
     * based on as it appears in the database.
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
     * @param string $join An optional string which could be used to join 
     * more than one condition ('and' or 'or'). If not given, 'and' is used as 
     * default value.
     * 
     * @since 1.0.2
     */
    public function addLeft($colName, $charsCount, $cond, $val, $join = 'and') {
        $this->addLeftOrRight($colName, $charsCount, $cond, $val, $join, true);
    }
    /**
     * Adds a 'like' condition to the 'where' part of the select.
     * 
     * @param string $colName The name of the column that the condition will be 
     * based on as it appears in the database.
     * 
     * @param string $val The value of the 'like' condition.
     * 
     * @param string $join An optional string which could be used to join 
     * more than one condition ('and' or 'or'). If not given, 'and' is used as 
     * default value.
     * 
     * @param boolean $not If set to true, the 'like' condition will be set 
     * to 'not like'.
     * 
     * @since 1.0.1
     */
    public function addLike($colName, $val, $join = 'and', $not = false) {
        if ($not === true) {
            $expr = new Expression($colName." not like $val");
        } else {
            $expr = new Expression($colName." like $val");
        }

        if ($this->whereExp === null) {
            $this->whereExp = new WhereExpression('');
        }
        $this->getWhereExpr()->addCondition($expr, $join);
    }
    /**
     * Adds a 'right()' condition to the 'where' part of the select.
     * 
     * @param string $colName The name of the column that the condition will be 
     * based on as it appears in the database.
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
     * @param string $join An optional string which could be used to join 
     * more than one condition ('and' or 'or'). If not given, 'and' is used as 
     * default value.
     * 
     * @since 1.0.2
     */
    public function addRight($colName, $charsCount, $cond, $val, $join = 'and') {
        $this->addLeftOrRight($colName, $charsCount, $cond, $val, $join, false);
    }
    /**
     * Adds a condition to the 'where' part of the select.
     * 
     * @param type $leftOpOrExp
     * 
     * @param type $rightOp
     * 
     * @param type $cond
     * 
     * @param string $join
     * 
     * @since 1.0
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
            if ($rightOp === null) {
                $this->addWhereNull($leftOpOrExp, $join, $cond == '=' ? false : true);
            } else {
                if ($this->whereExp === null) {
                    $this->whereExp = new WhereExpression('');
                }
                $condition = new Condition($leftOpOrExp, $rightOp, $cond);
                $this->whereExp->addCondition($condition, $join);
            }
        }
    }
    /**
     * Adds a 'where between ' condition.
     * 
     * @param string $colName The name of the column that the condition will be 
     * based on as it appears in the database.
     * 
     * @param mixed $firstVal The left hand side operand of the between condition.
     * 
     * @param mixed $secVal The right hand side operand of the between condition.
     * 
     * @param string $join An optional string which could be used to join 
     * more than one condition ('and' or 'or'). If not given, 'and' is used as 
     * default value.
     * 
     * @param boolean $not If set to true, the 'between' condition will be set 
     * to 'not between'.
     * 
     * 
     * @since 1.0.1
     */
    public function addWhereBetween($colName, $firstVal, $secVal, $join = 'and', $not = false) {
        $cond = new Condition($firstVal, $secVal, 'and');

        if ($not === true) {
            $expr = new Expression('('.$colName.' not between '.$cond.')');
        } else {
            $expr = new Expression('('.$colName.' between '.$cond.')');
        }

        if ($this->whereExp === null) {
            $this->whereExp = new WhereExpression('');
        }
        $this->getWhereExpr()->addCondition($expr, $join);
    }
    /**
     * 
     * @param Condition $cond
     * @param type $join
     */
    public function addWhereCondition(Condition $cond, $join = 'and') {
        if ($this->whereExp === null) {
            $this->whereExp = new WhereExpression();
        }
        $this->whereExp->addCondition($cond, $join);
    }
    /**
     * Adds a 'where in()' condition.
     * 
     * @param string $colName The name of the column that the condition will be 
     * based on as it appears in the database.
     * 
     * @param array $vals An array that holds the values that will be checked.
     * 
     * @param string $join An optional string which could be used to join 
     * more than one condition ('and' or 'or'). If not given, 'and' is used as 
     * default value.
     * 
     * @param boolean $not If set to true, the 'in' condition will be set 
     * to 'not in'.
     * 
     * @since 1.0.1
     */
    public function addWhereIn($colName, array $vals, $join = 'and', $not = false) {
        $valsStr = implode(', ', $vals);

        if ($not === true) {
            $expr = new Expression($colName." not in($valsStr)");
        } else {
            $expr = new Expression($colName." in($valsStr)");
        }

        if ($this->whereExp === null) {
            $this->whereExp = new WhereExpression('');
        }
        $this->getWhereExpr()->addCondition($expr, $join);
    }
    /**
     * Adds 'where is null' condition.
     * 
     * @param string $colName The name of the column that the condition will be 
     * based on as it appears in the database.
     * 
     * @param string $join An optional string which could be used to join 
     * more than one condition ('and' or 'or'). If not given, 'and' is used as 
     * default value.
     * 
     * @param boolean $not If set to true, the 'in' condition will be set 
     * to 'is not null'.
     * 
     * @since 1.0.2
     */
    public function addWhereNull($colName, $join = 'and', $not = false) {
        if ($not === true) {
            $expr = new Expression($colName." is not null");
        } else {
            $expr = new Expression($colName." is null");
        }

        if ($this->whereExp === null) {
            $this->whereExp = new WhereExpression('');
        }
        $this->getWhereExpr()->addCondition($expr, $join);
    }
    /**
     * Removes all columns and expressions in the select.
     * 
     * @since 1.0
     */
    public function clear() {
        $this->selectCols = [];
        $this->groupByCols = [];
        $this->orderByCols = [];
        $this->whereExp = null;
    }
    /**
     * Returns number of columns and expressions in the select.
     * 
     * @return int Number of columns and expressions in the select.
     * 
     * @since 1.0
     */
    public function colsCount() {
        return count($this->getSelectCols());
    }
    /**
     * Returns an array that contains all columns keys which are in the select.
     * 
     * @return array An array that contains all columns keys which are in the select.
     * 
     * @since 1.0
     */
    public function getColsKeys() {
        return array_keys($this->getSelectCols());
    }
    /**
     * Returns a string that contains the columns at which that will be select.
     * 
     * @return string If the table has no columns to select, the method will 
     * return the value '*'. Other than that, the method will return a string that 
     * contains columns names.
     * 
     * @since 1.0
     */
    public function getColsStr() {
        if (count($this->selectCols) == 0) {
            $colsStr = '*';
        } else {
            $selectArr = [];
            $cols = $this->getSelectCols();
            $thisTable = $this->getTable();
            $isJoinTable = $thisTable instanceof JoinTable ? true : false;

            foreach ($cols as $colKey => $optArr) {
                $obj = $optArr['obj'];

                if ($obj instanceof Column) {
                    $obj->setWithTablePrefix(true);
                    $addCol = true;
                    $resetOwner = false;

                    if ($isJoinTable) {
                        if (!($thisTable->getLeft() instanceof JoinTable)) {
                            
                            $ownerName = $obj->getOwner()->getName();
                            $leftName = $thisTable->getLeft()->getName();
                            $rightName = $thisTable->getRight()->getName();
                            $tableName = $thisTable->getName();

                            if ($ownerName != $leftName && $ownerName != $rightName && $tableName != $ownerName) {
                                //$obj->setOwner($this->getTable());
                            }
                        } else {
                            //$obj->setOwner($this->getTable());
                        }
                    } else {
                        if ($obj->getPrevOwner() !== null) {
                            //$obj->setOwner($obj->getPrevOwner());
                            $resetOwner = true;
                        }
                    }

                    if ($addCol) {
                        $this->_addColToSelectArr($selectArr, $obj, $optArr);
                    }

                    if ($resetOwner) {
                        $obj->setOwner($obj->getPrevOwner());
                    }
                } else {
                    $selectArr[] = $obj->getValue();
                }
            }
            $colsStr = implode(', ', $selectArr);
        }

        return $colsStr;
    }
    /**
     * Returns a string that represents the group by part of the select.
     * 
     * @return string A string that represents the group by part of the select. If 
     * no columns exist in the group by part, the method will return empty 
     * string.
     * 
     * @since 1.0
     */
    public function getGroupBy() {
        $arrOfCols = [];

        foreach ($this->groupByCols as $colObj) {
            $colObj->setWithTablePrefix(true);
            $arrOfCols[] = $colObj->getName();
        }

        if (count($arrOfCols) != 0) {
            return ' group by '.implode(', ', $arrOfCols);
        }

        return '';
    }
    /**
     * Returns a string that represents the order by part of the select.
     * 
     * @return string A string that represents the order by part of the select. If 
     * no columns exist in the order by part, the method will return empty 
     * string.
     * 
     * @since 1.0
     */
    public function getOrderBy() {
        $arrOfCols = [];

        foreach ($this->orderByCols as $subArr) {
            $subArr['col']->setWithTablePrefix(true);
            $order = $subArr['col']->getName();
            $orderType = isset($subArr['order']) ? $subArr['order'] : '';

            if (strlen($orderType) != 0) {
                $order .= ' '.$orderType;
            }
            $arrOfCols[] = $order;
        }

        if (count($arrOfCols) != 0) {
            return ' order by '.implode(', ', $arrOfCols);
        }

        return '';
    }
    /**
     * Returns an associative array of the columns that holds all select expression 
     * columns.
     * 
     * @return array An associative array of the columns that holds all select expression 
     * columns. The indices will be columns keys and the values are objects of 
     * type 'Column' or 'Expression'.
     * 
     * @since 1.0
     */
    public function getSelectCols() {
        return $this->selectCols;
    }
    /**
     * Returns the table which is associated with the select expression.
     * 
     * @return Table The table which is associated with the select expression.
     * 
     * @since 1.0
     */
    public function getTable() {
        return $this->table;
    }
    /**
     * Returns the value of the select expression.
     * 
     * @return string The value of select expression.
     * 
     * @since 1.0
     */
    public function getValue() {
        $colsStr = $this->getColsStr();
        $table = $this->getTable();
        
        if ($table instanceof JoinTable) {
            $joinWhere = $this->_getJoinWhere($table);
            $joinCols = $this->_getJoinCols($table);
            
            if (strlen($colsStr) == 0) {
                return "select * from ($joinCols".$table->getJoin()."$joinWhere) as ".$table->getName();
            }

            return "select $colsStr from ($joinCols".$table->getJoin()."$joinWhere) as ".$table->getName();
        } else {
            if (strlen($colsStr) == 0) {
                return "select * from ".$table->getName();
            }

            return "select $colsStr from ".$table->getName();
        }
        
    }
    private function _getJoinCols(JoinTable $joinTable) {
        $leftTable = $joinTable->getLeft();
        
        if ($leftTable instanceof JoinTable) {
            return $leftTable->getSelect()->getValue();
        } 
        
        $leftCols = $joinTable->getLeft()->getSelect()->getColsStr();
        $rightCols = $joinTable->getRight()->getSelect()->getColsStr();
        
        if ($leftCols == '*' && $rightCols == '*') {
            return "select * from ";
        } else if ($leftCols != '*' && $rightCols == '*') {
            return "select $leftCols, ".$joinTable->getRight()->getName().".* from ";
        } else if ($leftCols == '*' && $rightCols != '*') {
            return "select ".$joinTable->getLeft()->getName().".*, $rightCols from ";
        } else {
            return "select * from ";
        }
    }
    private function _getJoinWhere(JoinTable $joinTable) {
        // remove the string ' where '
        $leftWhere = substr($joinTable->getLeft()->getSelect()->getWhereStr(true, false), 7);
        $rightWhere = substr($joinTable->getRight()->getSelect()->getWhereStr(true, false), 7);

        if (strlen($leftWhere) != 0 && strlen($rightWhere) != 0) {
            return " where $leftWhere and $rightWhere";
        } else if (strlen($leftWhere) != 0 && strlen($rightWhere) == 0) {
            return " where $leftWhere";
        } else if (strlen($leftWhere) == 0 && strlen($rightWhere) != 0) {
            return " where $rightWhere";
        } else {
            return '';
        }
    }

    /**
     * 
     * @return WhereExpression|null
     */
    public function getWhereExpr() {
        return $this->whereExp;
    }
    /**
     * Returns a string that represents the 'where' part of the select in addition 
     * to the 'order by' and 'group by'.
     * 
     * @param boolean $withOrderBy If set to true, the 'order by' part of the 
     * 'where' will be included. Default is 'true'.
     * 
     * @param boolean $withGroupBy If set to true, the 'order by' part of the 
     * 'where' will be included. Default is 'true'.
     * 
     * @return string
     * 
     * @since 1.0
     */
    public function getWhereStr($withGroupBy = true, $withOrderBy = true) {
        $thisTable = $this->getTable();
        $retVal = '';
        $orderBy = '';
        $groupBy = '';

        
        if ($this->whereExp !== null) {
            $retVal = $this->whereExp->getValue();
        }

        if ($withGroupBy) {
            $groupBy = $this->getGroupBy();
        }

        if ($withOrderBy) {
            $orderBy = $this->getOrderBy();
        }

        if (strlen($retVal) != 0) {
            return ' where '.$retVal.$groupBy.$orderBy;
        }

        return $groupBy.$orderBy;
    }
    /**
     * Adds a column to the set of columns at which the table records will 
     * be grouped by.
     * 
     * @param string $colKey The key of the column as specified when adding the 
     * column to the table.
     * 
     * @throws DatabaseException If column does not exist in the table that the 
     * select is based on.
     * 
     * @since 1.0
     */
    public function groupBy($colKey) {
        $colObj = $this->getTable()->getColByKey($colKey);

        if ($colObj === null) {
            $tblName = $this->getTable()->getName();
            throw new DatabaseException("The table $tblName has no column with key '$colKey'.");
        }
        $this->groupByCols[$colKey] = $colObj;
    }
    /**
     * Checks if a column exist in the select expression or not.
     * 
     * @param string $colKey The key of the column. For expressions, this can be 
     * sha256 hash of expression value.
     * 
     * @return boolean If the column exist in the select, the method will return 
     * true. Other than that, the method will return false.
     * 
     * @since 1.0
     */
    public function hasCol($colKey) {
        $trimmed = trim($colKey);

        return isset($this->getSelectCols()[$trimmed]);
    }
    /**
     * Adds a column to the set of columns at which the table records will 
     * be ordered by.
     * 
     * @param string $colKey The key of the column as specified when adding the 
     * column to the table.
     * 
     * @param string $orderType Order type of the column. Can be 'a' for 
     * ascending or 'd' for descending.
     * @throws DatabaseException
     */
    public function orderBy($colKey, $orderType = null) {
        $colObj = $this->getTable()->getColByKey($colKey);

        if ($colObj === null) {
            $tblName = $this->getTable()->getName();
            throw new DatabaseException("The table $tblName has no column with key '$colKey'.");
        }
        $colArr = [
            'col' => $colObj
        ];

        if ($orderType !== null) {
            $orderType = strtolower($orderType[0]);

            if ($orderType == 'd') {
                $colArr['order'] = 'desc';
            } else if ($orderType == 'a') {
                $colArr['order'] = 'asc';
            }
        }
        $this->orderByCols[$colKey] = $colArr;
    }
    /**
     * Removes a column from the set of columns that will be selected.
     * 
     * @param string $colKey The key of the column.
     * 
     * @since 1.0
     */
    public function removeCol($colKey) {
        unset($this->selectCols[$colKey]);
    }
    /**
     * Adds a set of columns or expressions to the select.
     * 
     * @param array $colsOrExprs An array that contains columns and expressions. 
     * The array can be associative. If so, the indices must be columns names 
     * and the values must me sub arrays that holds column options. Each sub 
     * array can have the following indices: 
     * <ul>
     * <li>'obj': An object of type column or an expression.</li>
     * <li>'alias': An optional string which can act as an alias.</li>
     * <li>'aggregate': Aggregate function to use in the column such as 
     * 'avg' or 'max'.</li>
     * </ul>
     * 
     * @throws DatabaseException If column does not exist in the table that the 
     * select is based on.
     * 
     * @since 1.0
     */
    public function select(array $colsOrExprs) {
        try {
            foreach ($colsOrExprs as $index => $colArrOrExpr) {
                if ($colArrOrExpr instanceof Expression) {
                    $this->addExpression($colArrOrExpr);
                } else {
                    if (gettype($index) == 'integer') {
                        $this->addColumn($colArrOrExpr);
                    } else {
                        $this->addColumn($index, $colArrOrExpr);
                    }
                }
            }
        } catch (DatabaseException $ex) {
            throw new DatabaseException($ex->getMessage());
        }
    }
    private function _addColToSelectArr(&$arr, $colObj, $selectArr) {
        $alias = $colObj->getAlias();
        $colName = $colObj->getName();

        if (isset($selectArr['aggregate'])) {
            $selectColStr = $selectArr['aggregate']."($colName)";
        } else {
            $selectColStr = $colName;
        }


        if ($alias !== null) {
            $selectColStr .= ' as '.$alias;
        }
        $arr[] = $selectColStr;
    }
    private function _setAlias($colObj, $options, &$opArr) {
        if (isset($options['alias'])) {
            $alias = trim($options['alias']);
            $colObj->setAlias($alias);
            $opArr['alias'] = $alias;
        } else if (isset($options['as'])) {
            $alias = trim($options['as']);
            $colObj->setAlias($alias);
            $opArr['as'] = $alias;
        }
    }
    private function addLeftOrRight($colName, $charsCount, $cond, $val, $join = 'and', $left = true) {
        $xCond = in_array($cond, ['=', '!=', 'in', 'not in']) ? $cond : '=';
        $func = $left === true ? 'left' : 'right';

        if (gettype($val) == 'array' && ($xCond == '=' || $xCond == '!=')) {
            throw new InvalidArgumentException('The value must be of type string since the condition is \''.$xCond.'\'.');
        }

        if ($xCond == 'in' || $xCond == 'not in') {
            if (gettype($val) == 'array') {
                $expr = new Expression($func.'('.$colName.', '.$charsCount.') '.$xCond."(".implode(", ", $val).")");
            } else {
                $expr = new Expression($func.'('.$colName.', '.$charsCount.') '.$xCond."(".$val.")");
            }
        } else {
            $expr = new Expression($func.'('.$colName.', '.$charsCount.') '.$xCond.' '.$val);
        }

        if ($this->whereExp === null) {
            $this->whereExp = new WhereExpression('');
        }
        $this->getWhereExpr()->addCondition($expr, $join);
    }
}
