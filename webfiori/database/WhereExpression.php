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
 * A class which is used to build 'where' expressions.
 *
 * @author Ibrahim
 * 
 * @version 1.0
 */
class WhereExpression extends Expression {
    /**
     * An array that contains sub-where expressions.
     * 
     * @var array
     * 
     * @since 1.0 
     */
    private $children;
    /**
     * Returns the condition at which the statement represents.
     * 
     * @var Condition 
     * 
     * @since 1.0
     */
    private $condsChain;
    /**
     * Number of conditions in the expression.
     * 
     * @var int
     * 
     * @since 1.0 
     */
    private $condsCount;
    /**
     * The condition at which will be used to join the expression 
     * with the parent.
     * 
     * @var string
     * 
     * @since 1.0 
     */
    private $joinCond;
    /**
     * The parent where expression.
     * 
     * @var WhereExpression
     * 
     * @since 1.0 
     */
    private $parentWhere;
    /**
     * Creates new instance of the class.
     * 
     * @since 1.0
     */
    public function __construct() {
        parent::__construct('');
        $this->children = [];
        $this->joinCond = '';
        $this->condsCount = 0;
    }
    /**
     * Adds a condition to the expression and chain it with existing conditions. 
     * 
     * @param Condition|Expression $condition The condition as an object
     *
     * @param string $joinOp A string such as 'and' or 'or' which will be used 
     * to chain the condition with the previously added one. If the expression 
     * has children and the chain of conditions is empty, the value will 
     * be set as a join condition between the children and the expression.
     * 
     * @since 1.0
     * 
     */
    public function addCondition($condition, $joinOp) {
        if ($this->getCondition() !== null) {
            $cond = new Condition($this->getCondition(), $condition, $joinOp);
            $this->condsChain = $cond;
        } else if ($condition instanceof Condition) {
            $this->condsChain = $condition;

            if (count($this->children) != 0) {
                $this->setJoinCondition($joinOp);
            }
        } else if ($condition instanceof Expression) {
            $this->condsChain = new Condition($condition, null, $joinOp);
        }
        $this->condsCount++;
    }
    /**
     * Returns the condition at which the statement represents.
     * 
     * @return Condition|null The condition at which the statement represents. If 
     * the statement has no conditions, the method will return null.
     * 
     * @since 1.0
     */
    public function getCondition() {
        return $this->condsChain;
    }
    /**
     * Returns the condition at which the expression will use to combine with children 
     * expressions.
     * 
     * @return string  A string such as 'and' or 'or'. Default return value is ''.
     * 
     * @since 1.0
     */
    public function getJoinCondition() {
        return $this->joinCond;
    }
    /**
     * Returns the parent where expression.
     * 
     * 
     * @return WhereExpression|null If the expression has a parent, the method 
     * will return it as an object of type 'WhereExpression'. If the expression 
     * has no parent, the method will return null.
     * 
     * @since 1.0
     */
    public function getParent() {
        return $this->parentWhere;
    }
    /**
     * Returns the value of the expression.
     * 
     * @return string The method will return a string that represents the value 
     * of the expression.
     * 
     * @since 1.0
     */
    public function getValue() {
        $val = '';

        foreach ($this->children as $chWhere) {
            if ($chWhere->condsCount <= 1) {
                $val .= ''.$chWhere.'';
            } else {
                $val .= '('.trim(trim($chWhere, 'or '), 'and ').')';
            }
        }

        if ($this->getCondition() !== null) {
            if ($this->condsCount == 1) {
                if (strlen($val) != 0) {
                    $val .= ' '.$this->getJoinCondition().' '.$this->getCondition().'';
                } else {
                    $val .= $this->getCondition().'';
                }
            } else if (count($this->children) != 0 && $this->getParent() !== null && $this->getCondition() !== null) {
                $val .= ' '.$this->getJoinCondition().' ('.$this->getCondition().')';
            } else {
                $val .= ' '.$this->getJoinCondition().' '.$this->getCondition().'';
            }
        }

        return trim($val);
    }
    /**
     * Sets the condition at which the expression will use to combine with children 
     * expressions.
     * 
     * @param string $cond A string such as 'and' or 'or'.
     * 
     * @since 1.0
     */
    public function setJoinCondition($cond) {
        $this->joinCond = $cond;
    }
    /**
     * Sets the parent of the expression.
     * 
     * This one is used to make the expression as a sub where condition.
     * 
     * @param WhereExpression $whereExpr The parent expression.
     * 
     * @since 1.0
     */
    public function setParent(WhereExpression $whereExpr) {
        $this->parentWhere = $whereExpr;
        $whereExpr->children[] = $this;
    }
}
