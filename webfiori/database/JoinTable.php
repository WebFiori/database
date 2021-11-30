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
        
        $this->_addCols(true);
        $this->_addCols(false);
        $this->setOwner($this->getLeft()->getOwner());
    }
    public function getName() {
        if ($this->getLeft() instanceof mysql\MySQLTable) {
            return mysql\MySQLQuery::backtick($this->getNormalName());
        } else if ($this->getLeft() instanceof mssql\MSSQLTable) {
            return mssql\MSSQLQuery::squareBr($this->getNormalName());
        }
        return parent::getName();
    }
    private function _addCols($left = true) {
        $prefix = $left === true ? 'left' : 'right';
        
        if ($left) {
            $cols = $this->getLeft()->getCols();
        } else {
            $cols = $this->getRight()->getCols();
        }
        
        foreach ($cols as $colKey => $colObj) {
            if ($this->hasColumnWithKey($colKey)) {
                $colKey = $prefix.'-'.$colKey;
            }
            $colObj->setWithTablePrefix(false);
            if ($this->hasColumn($colObj->getNormalName())) {
                $colObj->setName($prefix.'_'.$colObj->getNormalName());
            }
            $this->addColumn($colKey, $colObj);
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
     * Returns a string which represents the join condition of the two tables.
     * 
     * The format of the string will be similar to the following: 
     * "`left_table` join_type `right_table` [on(join_cond)]".
     * The join condition will be included only if specified.
     * 
     * @return string
     * 
     * @since 1.0
     */
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

    public function toSQL() {
        
    }

}
