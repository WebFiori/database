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
     * @var Table
     * 
     * @since 1.0 
     */
    private $table;
    /**
     *
     * @var array
     * 
     * @since 1.0 
     */
    private $selectCols;
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
        try{
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
    /**
     * Removes all columns and expressions in the select.
     * 
     * @since 1.0
     */
    public function clear() {
        $this->selectCols = [];
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
        return "select $colsStr from ".$this->getTable()->getName();
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
            $table = $this->getTable();
            $isJoinTable = $table instanceof JoinTable ? true : false;
            foreach ($cols as $colKey => $colObjOrExpr) {
                if ($colObjOrExpr instanceof Column) {
                    $colObjOrExpr->setWithTablePrefix(true);
                    $addCol = true;
                    if ($isJoinTable) {
                        
                        $existInLeft = $table->getLeft()->getSelect()->hasCol($colKey);
                        $existInRight = $table->getRight()->getSelect()->hasCol($colKey);
                        $addCol = !$existInLeft && !$existInRight;
                        
                        if (!$existInLeft && !$existInRight) {
                            $ownerName = $colObjOrExpr->getOwner()->getName();
                            $leftName = $table->getLeft()->getName();
                            $rightName = $table->getRight()->getName();
                            $tableName = $table->getName();
                            if ($ownerName != $leftName && $ownerName != $rightName && $tableName != $ownerName) {
                                $colObjOrExpr->setOwner($this->getTable());
                            }
                        }
                        
                    } else {
                        if ($colObjOrExpr->getPrevOwner() !== null) {
                            $colObjOrExpr->setOwner($colObjOrExpr->getPrevOwner());
                        }
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
                } else {
                    $selectArr[] = $colObjOrExpr->getValue();
                }
            }
            $colsStr = implode(', ', $selectArr);
        }
        return $colsStr;
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
}
