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

use webfiori\database\Condition;
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
     * The type of the join.
     * 
     * @var string
     * 
     * @since 1.0 
     */
    private $joinType;
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
    public function getCols() {
        return array_merge($this->getLeft()->getCols(), $this->getRight()->getCols());
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
    public function toSQL() {
        $leftTbl = $this->getLeft();
        $rightTbl = $this->getRight();
        $retVal = '';
        if ($leftTbl instanceof JoinTable) {
            $rightSelectCols = $rightTbl->getSelect()->getColsStr();
            if ($rightSelectCols == '*') {
                $rightSelectCols = '';
            }
            $leftSelectCols = $leftTbl->getSelect()->getColsStr();
            if ($leftSelectCols == '*') {
                $leftSelectCols = '';
            }
            if (strlen($rightSelectCols) != 0 && strlen($leftSelectCols) != 0) {
                $select = "select $leftSelectCols, $rightSelectCols from";
            } else if (strlen($leftSelectCols) != 0) {
                $select = "select $leftSelectCols from";
            } else if (strlen($rightSelectCols) != 0) {
                $select = "select $rightSelectCols from";
            } else {
                $select = "select * from ";
            }
            
            $subQuery = $select.$leftTbl->toSQL();
            
            $retVal .= '('.$subQuery.') as '.$this->getName().' '.$this->getJoinType().' '.$rightTbl->getName();
        } else {
            $retVal = $this->getLeft()->getName().' '.$this->getJoinType().' '.$this->getRight()->getName();
        }
        if ($this->getJoinCondition() !== null) {
            $retVal .= ' on('.$this->getJoinCondition().')';
        }
        return $retVal;
    }

}
