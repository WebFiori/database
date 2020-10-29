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

/**
 * A class which is used to build the select expression of a select query.
 *
 * @author Ibrahim
 * 
 * @version 1.0
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
     * @param string|null $alias An optional alias for the column.
     * 
     * @throws DatabaseException If column does not exist in the table that the 
     * select is based on.
     * 
     * @since 1.0
     */
    public function addColumn($colKey, $alias = null) {
        if ($colKey != '*') {
            $colObj = $this->getTable()->getColByKey($colKey);

            if ($colObj === null) {
                $tblName = $this->getTable()->getName();
                throw new DatabaseException("The table $tblName has no column with key '$colKey'.");
            }
            $colObj->setAlias($alias);
            $this->selectCols[$colKey] = $colObj;
        }
    }
    public function addExpression(Expression $expr) {
        $this->selectCols[hash('sha256', $expr->getValue())] = $expr;
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
            if ($this->whereExp === null) {
                $this->whereExp = new WhereExpression('');
            }
            $condition = new Condition($leftOpOrExp, $rightOp, $cond);
            $this->whereExp->addCondition($condition, $join);
        }
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

            foreach ($cols as $colKey => $colObjOrExpr) {
                if ($colObjOrExpr instanceof Column) {
                    $colObjOrExpr->setWithTablePrefix(true);
                    $addCol = true;
                    $resetOwner = false;

                    if ($isJoinTable) {
                        if (!$thisTable->getLeft() instanceof JoinTable) {
                            $existInLeft = $thisTable->getLeft()->getSelect()->hasCol($colKey);
                            $existInRight = $thisTable->getRight()->getSelect()->hasCol($colKey);

                            $addCol = !$existInLeft && !$existInRight;

                            if (!$existInLeft && !$existInRight) {
                                $ownerName = $colObjOrExpr->getOwner()->getName();
                                $leftName = $thisTable->getLeft()->getName();
                                $rightName = $thisTable->getRight()->getName();
                                $tableName = $thisTable->getName();

                                if ($ownerName != $leftName && $ownerName != $rightName && $tableName != $ownerName) {
                                    $colObjOrExpr->setOwner($this->getTable());
                                }
                            }
                        } else {
                            $colObjOrExpr->setOwner($this->getTable());
                        }
                    } else if ($colObjOrExpr->getPrevOwner() !== null) {
                        $colObjOrExpr->setOwner($colObjOrExpr->getPrevOwner());
                        $resetOwner = true;
                    }

                    if ($addCol) {
                        $alias = $colObjOrExpr->getAlias();
                        $colName = $colObjOrExpr->getName();

                        if ($alias !== null) {
                            $selectArr[] = $colName.' as '.$alias;
                        } else {
                            $selectArr[] = $colName;
                        }
                    }

                    if ($resetOwner) {
                        $colObjOrExpr->setOwner($colObjOrExpr->getPrevOwner());
                    }
                } else {
                    $selectArr[] = $colObjOrExpr->getValue();
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

        if (strlen($colsStr) == 0) {
            return "select * from ".$this->getTable()->getName();
        }

        return "select $colsStr from ".$this->getTable()->getName();
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

        if ($thisTable instanceof JoinTable) {
            $leftWhere = $thisTable->getLeft()->getSelect()->getWhereExpr();
            $rightWhere = $thisTable->getRight()->getSelect()->getWhereExpr();

            if ($leftWhere !== null) {
                if ($rightWhere !== null) {
                    $leftWhere->addCondition($leftWhere->getCondition(), 'and');
                }

                if ($this->whereExp !== null) {
                    $leftWhere->addCondition($this->whereExp->getCondition(), 'and');
                }
                $retVal = $leftWhere->getValue();
            } else if ($rightWhere !== null) {
                if ($this->whereExp !== null) {
                    $rightWhere->addCondition($this->whereExp->getCondition(), 'and');
                }
                $retVal = $rightWhere->getValue();
            } else if ($this->whereExp !== null) {
                $retVal = $this->whereExp->getValue();
            }
        } else if ($this->whereExp !== null) {
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
     * 
     * @throws DatabaseException If column does not exist in the table that the 
     * select is based on.
     * 
     * @since 1.0
     */
    public function select(array $colsOrExprs) {
        try {
            foreach ($colsOrExprs as $index => $colOrExprOrAlias) {
                if ($colOrExprOrAlias instanceof Expression) {
                    $this->addExpression($colOrExprOrAlias);
                } else if (gettype($index) == 'string') {
                    $this->addColumn($index, $colOrExprOrAlias);
                } else {
                    $this->addColumn($colOrExprOrAlias);
                }
            }
        } catch (DatabaseException $ex) {
            throw new DatabaseException($ex->getMessage());
        }
    }
}
