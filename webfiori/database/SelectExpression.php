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
     * @param array $options An array that holds options to customize the
     * column. Supported options are:
     * <ul>
     * <li>alias: Give the column an alias.</li>
     * <li>as: Same as 'alias'.</li>
     * <li>aggregate: The name of SQL aggregate function such as 'max' or 'avg'. </li>
     * </ul>
     *
     * 
     * @since 1.0
     */
    public function addColumn(string $colKey, ?array $options = null) {
        if ($colKey != '*') {
            $colKey = str_replace('_', '-', $colKey);
            $colObj = $this->getTable()->getColByKey($colKey);

            if ($colObj === null) {
                $this->getTable()->addColumns([
                    $colKey => []
                ]);
                $colObj = $this->getTable()->getColByKey($colKey);
            }
            $opArr = [
                'obj' => $colObj,
            ];
            $this->setAliasHelper($colObj, $options, $opArr);

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
    public function addLeft(string $colName, int $charsCount, string $cond, $val, string $join = 'and') {
        $this->addLeftOrRight($colName, $charsCount, $cond, $val, $join);
    }
    /**
     * Adds a 'like' condition to the 'where' part of the select.
     * 
     * @param string $colName The name of the column that the condition will be 
     * based on as it appears in the database.
     * 
     * 
     * @param string $join An optional string which could be used to join 
     * more than one condition ('and' or 'or'). If not given, 'and' is used as 
     * default value.
     * 
     * @param bool $not If set to true, the 'like' condition will be set 
     * to 'not like'.
     * 
     * @since 1.0.1
     */
    public function addLike(string $colName, string $join = 'and', bool $not = false) {
        if ($not === true) {
            $expr = new Expression($colName." not like ?");
        } else {
            $expr = new Expression($colName." like ?");
        }

        if ($this->whereExp === null) {
            $this->whereExp = new WhereExpression();
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
    public function addRight(string $colName, int $charsCount, string $cond, $val, string $join = 'and') {
        $this->addLeftOrRight($colName, $charsCount, $cond, $val, $join, false);
    }
    /**
     * Adds a condition to the 'where' part of the select.
     * 
     * @param mixed $leftOpOrExp The left hand side operand of the condition.
     * This can be a simple string, an object of type
     * AbstractQuery which represents a sub-query or an expression.
     * 
     * @param mixed $rightOp The right hand side operand of the condition. If null
     * is given, the where expression will add 'is null' or 'is not null'
     * condition
     * 
     * @param string|null $cond The condition which is used to join left operand
     * and right operand.
     * 
     * @param string $join If the where expression already has conditions,
     * this one is used to join the newly added one with existing one. This
     * one can have only two values, 'and' or 'or'. Default is 'and'.
     * 
     * @since 1.0
     */
    public function addWhere($leftOpOrExp, mixed $rightOp = null, ?string $cond = null, string $join = 'and') {
        if (!in_array($join, ['and', 'or'])) {
            $join = 'and';
        }

        if ($leftOpOrExp instanceof AbstractQuery) {
            $parentWhere = new WhereExpression();
            $this->whereExp->setJoinCondition($join);
            $this->whereExp->setParent($parentWhere);

            $this->whereExp = $parentWhere;
        } else {
            if ($leftOpOrExp instanceof Expression && $rightOp === null) {
                $this->whereExp->addCondition($leftOpOrExp, $join);
            } else if ($rightOp === null) {
                $this->addWhereNull($leftOpOrExp, $join, !($cond == '='));
            } else {
                if ($this->whereExp === null) {
                    $this->whereExp = new WhereExpression();
                }
                $condition = new Condition($leftOpOrExp, '?', $cond);
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
     * @param string $join An optional string which could be used to join 
     * more than one condition ('and' or 'or'). If not given, 'and' is used as 
     * default value.
     * 
     * @param bool $not If set to true, the 'between' condition will be set 
     * to 'not between'.
     * 
     * 
     * @since 1.0.1
     */
    public function addWhereBetween(string $colName, string $join = 'and', bool $not = false) {
        $cond = new Condition('?', '?', 'and');

        if ($not === true) {
            $expr = new Expression('('.$colName.' not between '.$cond.')');
        } else {
            $expr = new Expression('('.$colName.' between '.$cond.')');
        }

        if ($this->whereExp === null) {
            $this->whereExp = new WhereExpression();
        }
        $this->getWhereExpr()->addCondition($expr, $join);
    }
    /**
     * Adds a 'where in()' condition.
     * 
     * @param string $colName The name of the column that the condition will be 
     * based on as it appears in the database.
     * 
     * @param array $values An array that holds the values that will be checked.
     * 
     * @param string $join An optional string which could be used to join 
     * more than one condition ('and' or 'or'). If not given, 'and' is used as 
     * default value.
     * 
     * @param bool $not If set to true, the 'in' condition will be set 
     * to 'not in'.
     * 
     * @since 1.0.1
     */
    public function addWhereIn(string $colName, array $values, string $join = 'and', bool $not = false) {
        
        $placeholders = trim(str_repeat('?, ', count($values)), ', ');
        if ($not === true) {
            $expr = new Expression($colName." not in($placeholders)");
        } else {
            $expr = new Expression($colName." in($placeholders)");
        }

        if ($this->whereExp === null) {
            $this->whereExp = new WhereExpression();
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
     * @param bool $not If set to true, the 'in' condition will be set 
     * to 'is not null'.
     * 
     * @since 1.0.2
     */
    public function addWhereNull(string $colName, string $join = 'and', bool $not = false) {
        if ($not === true) {
            $expr = new Expression($colName." is not null");
        } else {
            $expr = new Expression($colName." is null");
        }

        if ($this->whereExp === null) {
            $this->whereExp = new WhereExpression();
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
    public function getColsCount() : int {
        return count($this->getSelectCols());
    }
    /**
     * Returns an array that contains all columns keys which are in the select.
     * 
     * @return array An array that contains all columns keys which are in the select.
     * 
     * @since 1.0
     */
    public function getColsKeys() : array {
        return array_keys($this->getSelectCols());
    }
    /**
     * Returns a string that contains the columns at which that will be selected.
     * 
     * @return string If the table has no columns to select, the method will 
     * return the value '*'. Other than that, the method will return a string that 
     * contains columns names.
     * 
     * @since 1.0
     */
    public function getColsStr() : string {
        if ($this->getColsCount() == 0) {
            $colsStr = '*';
        } else {
            $selectArr = [];
            $cols = $this->getSelectCols();
            $thisTable = $this->getTable();

            foreach ($cols as $optionsArr) {
                $obj = $optionsArr['obj'];

                if ($obj instanceof Column) {
                    $obj->setWithTablePrefix(true);
                    $resetOwner = false;

                    if ($thisTable instanceof JoinTable) {
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
                    $this->addColToSelectArr($selectArr, $obj, $optionsArr);

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
    public function getGroupBy() : string {
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
    public function getOrderBy() : string {
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
     * The returned array will hold the columns that will be included in the
     * select expression alongside the options for each column. The indices of
     * the returned array are columns keys and the values are sub-associative
     * arrays with column options.
     * 
     * @return array An associative array of the columns that holds all select expression 
     * columns.
     * 
     * @since 1.0
     */
    public function getSelectCols() : array {
        return $this->selectCols;
    }
    /**
     * Returns the table which is associated with the select expression.
     * 
     * @return Table The table which is associated with the select expression.
     * 
     * @since 1.0
     */
    public function getTable() : Table {
        return $this->table;
    }
    /**
     * Returns the value of the select expression.
     * 
     * @return string The value of select expression.
     * 
     * @since 1.0
     */
    public function getValue() : string {
        $colsStr = $this->getColsStr();
        $table = $this->getTable();

        if ($table instanceof JoinTable) {
            $joinWhere = $this->getJoinWhere($table);
            $joinCols = $this->getJoinCols($table);

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

    /**
     * Returns the where expression which is associated with the select.
     * 
     * @return WhereExpression|null If the select has where part, it will
     * be returned as an object. Other than that, null is returned.
     */
    public function getWhereExpr() {
        return $this->whereExp;
    }
    /**
     * Returns a string that represents the 'where' part of the select in addition 
     * to the 'order by' and 'group by'.
     * 
     * @param bool $withGroupBy If set to true, the 'order by' part of the 
     * 'where' will be included. Default is 'true'.
     * 
     * @param bool $withOrderBy If set to true, the 'order by' part of the 
     * 'where' will be included. Default is 'true'.
     * 
     * @return string
     * 
     * @since 1.0
     */
    public function getWhereStr(bool $withGroupBy = true, bool $withOrderBy = true) : string {
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
     * 
     * @since 1.0
     */
    public function groupBy(string $colKey) {
        $colObj = $this->getTable()->getColByKey($colKey);

        if ($colObj === null) {
            $this->getTable()->addColumns([
                $colKey => []
            ]);
            $colObj = $this->getTable()->getColByKey($colKey);
        }
        $this->groupByCols[$colKey] = $colObj;
    }
    /**
     * Checks if a column exist in the select expression or not.
     * 
     * @param string $colKey The key of the column. For expressions, this can be 
     * sha256 hash of expression value.
     * 
     * @return bool If the column exist in the select, the method will return 
     * true. Other than that, the method will return false.
     * 
     * @since 1.0
     */
    public function isInSelect(string $colKey) : bool {
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
     */
    public function orderBy(string $colKey, ?string $orderType = null) {
        $colObj = $this->getTable()->getColByKey($colKey);

        if ($colObj === null) {
            $this->getTable()->addColumns([
                $colKey => []
            ]);
            $colObj = $this->getTable()->getColByKey($colKey);
        }
        $colArr = [
            'col' => $colObj
        ];

        if ($orderType !== null) {
            $orderType = strtolower($orderType[0]);

            if ($orderType == 'd') {
                $colArr['order'] = 'desc';
            } else {
                if ($orderType == 'a') {
                    $colArr['order'] = 'asc';
                }
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
    public function removeCol(string $colKey) {
        unset($this->selectCols[$colKey]);
    }
    /**
     * Adds a set of columns or expressions to the select.
     * 
     * @param array $colsOrExpressions An array that contains columns and expressions.
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
     * 
     * @since 1.0
     */
    public function select(array $colsOrExpressions) {
        foreach ($colsOrExpressions as $index => $colArrOrExpr) {
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
    }
    private function addColToSelectArr(&$arr, $colObj, $selectArr) {
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
    private function addLeftOrRight($colName, $charsCount, $cond, $val, $join = 'and', $left = true) {
        $xCond = in_array($cond, ['=', '!=', 'in', 'not in']) ? $cond : '=';
        $func = $left === true ? 'left' : 'right';

        if (gettype($val) == 'array' && ($xCond == '=' || $xCond == '!=')) {
            throw new InvalidArgumentException('The value must be of type string since the condition is \''.$xCond.'\'.');
        }

        if ($xCond == 'in' || $xCond == 'not in') {
            if (gettype($val) == 'array') {
                $placeholder = trim(str_repeat('?, ', count($val)), ', ');
                $expr = new Expression($func.'('.$colName.', '.$charsCount.') '.$xCond."($placeholder)");
            } else {
                $expr = new Expression($func.'('.$colName.', '.$charsCount.') '.$xCond."(?)");
            }
        } else {
            $expr = new Expression($func.'('.$colName.', '.$charsCount.') '.$xCond.' ?');
        }

        if ($this->whereExp === null) {
            $this->whereExp = new WhereExpression();
        }
        $this->getWhereExpr()->addCondition($expr, $join);
    }
    private function getJoinCols(JoinTable $joinTable) {
        $leftTable = $joinTable->getLeft();

        if ($leftTable instanceof JoinTable) {
            return $leftTable->getSelect()->getValue();
        } 

        $leftCols = $joinTable->getLeft()->getSelect()->getColsStr();
        $rightCols = $joinTable->getRight()->getSelect()->getColsStr();

        if ($leftCols == '*' && $rightCols == '*') {
            return "select * from ";
        } else {
            if ($leftCols != '*' && $rightCols == '*') {
                return "select $leftCols, ".$joinTable->getRight()->getName().".* from ";
            } else {
                if ($leftCols == '*' && $rightCols != '*') {
                    return "select ".$joinTable->getLeft()->getName().".*, $rightCols from ";
                } else {
                    return "select * from ";
                }
            }
        }
    }
    private function getJoinWhere(JoinTable $joinTable) : string {
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
    private function setAliasHelper($colObj, $options, &$opArr) {
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
}
