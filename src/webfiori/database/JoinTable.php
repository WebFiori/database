<?php
/**
 * MIT License
 *
 * Copyright (c) 2019,WebFiori framework.
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
 * A class that represents two joined tables.
 *
 * @author Ibrahim
 * 
 * @version 1.0
 */
class JoinTable extends Table {
    /**
     * Join conditions.
     * 
     * @var Condition 
     * 
     * @since 1.0
     */
    private $joinConds;
    /**
     * The type of the join.
     * 
     * @var string
     * 
     * @since 1.0 
     */
    private $joinType;
    /**
     * The left table of the join.
     * 
     * @var Table
     * 
     * @since 1.0 
     */
    private $left;
    /**
     * The right table of the join.
     * 
     * @var Table
     * 
     * @since 1.0 
     */
    private $right;
    /**
     * Creates new instance of the class.
     * 
     * @param Table $left The left table of the join.
     * 
     * @param Table $right The right table of the join.
     * 
     * @param string $joinType A string that represents join type such as 'left' or 
     * 'right'.
     * 
     * @param string $alias An optional alias for the table. It is simply will 
     * be set as the name of the table.
     * 
     * @since 1.0
     */
    public function __construct(Table $left, Table $right, $joinType = 'join', $alias = 'new_table') {
        parent::__construct($alias);
        $this->joinType = $joinType;
        $this->left = $left;
        $this->right = $right;
        $leftWheres = $left->getSelect()->getWhereExpr();

        if ($leftWheres !== null) {
            $this->getSelect()->addWhereCondition($leftWheres->getCondition());
        }
        $rightWheres = $right->getSelect()->getWhereExpr();

        if ($rightWheres !== null) {
            $this->getSelect()->addWhereCondition($rightWheres->getCondition());
        }
    }
    /**
     * Adds a condition which could be used to join the two tables.
     * 
     * @param Condition $cond The condition.
     * 
     * @param string $joinOp This one is used to chain multiple conditions 
     * with each other. This one can have values such as 'and' or 'or'.
     * 
     * @since 1.0
     */
    public function addJoinCondition(Condition $cond, $joinOp = 'and') {
        if ($this->joinConds === null) {
            $this->joinConds = $cond;
        } else {
            $newCond = new Condition($this->joinConds, $cond, $joinOp);
            $this->joinConds = $newCond;
        }
    }
    /**
     * Returns a column given its key name.
     * 
     * The method will first check for such column in the left table. If 
     * not found in the left, the method will check the right table.
     * 
     * @param string $key The name of column key.
     * 
     * @return Column|null If a column which has the given key exist on the table, 
     * the method will return it as an object. Other than that, the method will return 
     * null.
     * 
     * @since 1.0
     */
    public function getColByKey($key) {
        $colObj = $this->getLeft()->getColByKey($key);

        if ($colObj === null) {
            $colObj = $this->getRight()->getColByKey($key);
        }

        if ($colObj !== null) {
            $colObj->setWithTablePrefix(true);
        }

        return $colObj;
    }
    /**
     * Returns a column given its actual name.
     * 
     * The method will first check for such column in the left table. If 
     * not found in the left, the method will check the right table.
     * 
     * @param string $key The name of column as it appears in the database.
     * 
     * @return Column|null If a column which has the given name exist on the table, 
     * the method will return it as an object. Other than that, the method will return 
     * null.
     * 
     * @since 1.0
     */
    public function getColByName($name) {
        $colObj = $this->getLeft()->getColByName($name);

        if ($colObj === null) {
            $colObj = $this->getRight()->getColByName($name);
        }

        if ($colObj !== null) {
            $colObj->setWithTablePrefix(true);
        }

        return $colObj;
    }
    public function getCols() {
        return array_merge($this->getLeft()->getCols(), $this->getRight()->getCols());
    }
    /**
     * Returns the number of columns in the combined table.
     * 
     * @return int Number of columns in the combined table.
     * 
     * @since 1.0
     */
    public function getColsCount() {
        return count($this->getColsNames());
    }
    /**
     * Returns an array that contains data types of table columns.
     * 
     * @return array An indexed array that contains columns data types. Each 
     * index will corresponds to the index of the column in the table.
     * 
     * @since 1.0
     */
    public function getColsDatatypes() {
        return array_merge($this->getLeft()->getColsDatatypes(), $this->getRight()->getColsDatatypes());
    }
    /**
     * Returns an indexed array that contains the names of columns keys.
     * 
     * @return array An indexed array that contains the names of columns keys.
     * 
     * @since 1.0
     */
    public function getColsKeys() {
        return array_merge($this->getLeft()->getColsKeys(), $this->getRight()->getColsKeys());
    }
    /**
     * Returns an array that contains all columns names as they will appear in 
     * the database.
     * 
     * @return array An array that contains all columns names as they will appear in 
     * the database.
     * 
     * @since 1.0
     */
    public function getColsNames() {
        return array_merge($this->getLeft()->getColsNames(), $this->getRight()->getColsNames());
    }
    public function getJoin() {
        $retVal = $this->getLeft()->getName()
                .' '.$this->getJoinType()
                .' '.$this->getRight()->getName();

        if ($this->getJoinCondition() !== null) {
            $retVal .= ' on('.$this->getJoinCondition().')';
        }

        return $retVal;
    }
    /**
     * Returns the condition at which the two tables joined based on.
     * 
     * @return Condition The condition at which the two tables joined based on. 
     * This also can be a chain of conditions.
     * 
     * @since 1.0
     */
    public function getJoinCondition() {
        return $this->joinConds;
    }
    /**
     * Returns a string that represents join type.
     * 
     * @return string A string such as 'left' or 'right'.
     * 
     * @since 1.0
     */
    public function getJoinType() {
        return $this->joinType;
    }
    /**
     * Returns the left table of the join.
     * 
     * @return Table left table of the join.
     * 
     * @since 1.0
     */
    public function getLeft() {
        return $this->left;
    }
    /**
     * Returns the right table of the join.
     * 
     * @return Table right table of the join.
     * 
     * @since 1.0
     */
    public function getRight() {
        return $this->right;
    }
    public function toSQL($firstCall = false) {
        $leftTbl = $this->getLeft();
        $rightTbl = $this->getRight();
        $where = $this->getSelect()->getWhereStr();
        $retVal = '';

        $rightSelectCols = $rightTbl->getSelect()->getColsStr();

        if ($rightSelectCols == '*') {
            $rightSelectCols = '';
        }
        $leftSelectCols = $leftTbl->getSelect()->getColsStr();

        if ($leftSelectCols == '*') {
            $leftSelectCols = '';
        }

        if (strlen($rightSelectCols) != 0 && strlen($leftSelectCols) != 0) {
            $colsToSelect = "$leftSelectCols, $rightSelectCols";
        } else if (strlen($leftSelectCols) != 0) {
            $colsToSelect = $leftSelectCols;
        } else if (strlen($rightSelectCols) != 0) {
            $colsToSelect = $rightSelectCols;
        } else {
            $colsToSelect = "*";
        }

        //select * from (select * from `users_privileges`.`can_edit_price`, 
        //`users_privileges`.`can_change_username``users` join `users_privileges` on(`users`.`id` = `users_privileges`.`id`)) as T0 join `users_tasks` on(`T0`.`id` = `users_tasks`.`user_id`)

        if ($leftTbl instanceof JoinTable) {
            $retVal = $this->_toSQLHelper($leftTbl, $rightTbl, $leftSelectCols, $rightSelectCols, $where);
        } else if ($firstCall) {
            $retVal = $this->getJoin();
        } else {
            $retVal = 'select '.$colsToSelect.' from '.$this->getJoin();
        }

        return $retVal;
    }
    private function _getColsToSelect($leftTbl, $leftSelectCols, $rightSelectCols, $where) {
        $xleftTbl = $leftTbl->getLeft();
        $xrightTbl = $leftTbl->getRight();
        $xwhere = $leftTbl->getSelect()->getWhereStr();

        $xrightSelectCols = $xrightTbl->getSelect()->getColsStr();

        if ($xrightSelectCols != '*') {
            $rightSelectCols = $xrightSelectCols;
        }

        if (!($xleftTbl instanceof JoinTable)) {
            $xleftSelectCols = $xleftTbl->getSelect()->getColsStr();

            if ($xleftSelectCols != '*' && strlen($leftSelectCols) == 0) {
                $leftSelectCols = $xleftSelectCols;
            }
        }
 
        if (strlen($xwhere) != 0) {
            $where = $xwhere;
        }

        if (strlen($rightSelectCols) != 0 && strlen($leftSelectCols) != 0) {
            $colsToSelect = "$leftSelectCols, $rightSelectCols";
        } else if (strlen($leftSelectCols) != 0) {
            $colsToSelect = $leftSelectCols;
        } else if (strlen($rightSelectCols) != 0) {
            $colsToSelect = $rightSelectCols;
        } else {
            $colsToSelect = "*";
        }

        return $colsToSelect;
    }
    private function _toSQLHelper($leftTbl, $rightTbl, $leftSelectCols, $rightSelectCols, $where) {
        $colsToSelect = $this->_getColsToSelect($leftTbl, $leftSelectCols, $rightSelectCols, $where);
        $leftAsSQL = $leftTbl->toSQL();
        $xleftTbl = $leftTbl->getLeft();
        $retVal = '';

        if ($colsToSelect == '*') {
            if ($xleftTbl instanceof JoinTable) {
                $retVal .= '(select * from '.$leftAsSQL."$where) as ".$this->getName().' '.$this->getJoinType().' '.$rightTbl->getName();
            } else {
                $retVal .= '('.$leftAsSQL."$where) as ".$this->getName().' '.$this->getJoinType().' '.$rightTbl->getName();
            }

            if ($this->getJoinCondition() !== null) {
                $retVal .= ' on('.$this->getJoinCondition().')';
            }
        } else if ($xleftTbl instanceof JoinTable) {
            $retVal .= "(select $colsToSelect from $leftAsSQL$where) as ".$this->getName().' '.$this->getJoinType().' '.$rightTbl->getName();

            if ($this->getJoinCondition() !== null) {
                $retVal .= ' on('.$this->getJoinCondition().')';
            }
        } else {
            $retVal .= "(select $colsToSelect from ".$leftTbl->getJoin()."$where) as ".$this->getName().' '.$this->getJoinType().' '.$rightTbl->getName();

            if ($this->getJoinCondition() !== null) {
                $retVal .= ' on('.$this->getJoinCondition().')';
            }
        }

        return $retVal;
    }
}
