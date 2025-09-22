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
namespace WebFiori\Database;

/**
 * A class which is used to build 'where' expressions.
 *
 * @author Ibrahim
 * 
 */
class WhereExpression extends Expression {
    private $isCompiled;
    /**
     * An array that contains sub-where expressions.
     * 
     * @var array
     * 
     */
    private $children;
    /**
     * Returns the condition at which the statement represents.
     * 
     * @var Condition 
     * 
     */
    private $conditionsChain;
    /**
     * Number of conditions in the expression.
     * 
     * @var int
     * 
     */
    private $conditionsCount;
    /**
     * The condition at which will be used to join the expression 
     * with the parent.
     * 
     * @var string
     * 
     */
    private $joinCond;
    /**
     * The parent where expression.
     * 
     * @var WhereExpression
     * 
     */
    private $parentWhere;
    /**
     * Creates new instance of the class.
     * 
     */
    public function __construct() {
        parent::__construct('');
        $this->children = [];
        $this->joinCond = '';
        $this->conditionsCount = 0;
        $this->isCompiled = false;
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
     * 
     */
    public function addCondition($condition, string $joinOp) {
        if ($this->getCondition() !== null) {
            $cond = new Condition($this->getCondition(), $condition, $joinOp);
            $this->conditionsChain = $cond;
        } else if ($condition instanceof Condition) {
            $this->conditionsChain = $condition;

            if (count($this->children) != 0) {
                $this->setJoinCondition($joinOp);
            }
        } else if ($condition instanceof Expression) {
            $this->conditionsChain = new Condition($condition, null, $joinOp);
        }
        $this->isCompiled = false;
        $this->conditionsCount++;
    }
    /**
     * Returns the condition at which the statement represents.
     * 
     * @return Condition|null The condition at which the statement represents. If 
     * the statement has no conditions, the method will return null.
     * 
     */
    public function getCondition() {
        return $this->conditionsChain;
    }
    /**
     * Returns the condition at which the expression will use to combine with children 
     * expressions.
     * 
     * @return string  A string such as 'and' or 'or'. Default return value is
     * empty string.
     * 
     */
    public function getJoinCondition() : string {
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
     */
    public function getValue() : string {
        if ($this->isCompiled) {
            return parent::getValue();
        }
        $val = '';

        foreach ($this->children as $chWhere) {
            if ($chWhere->conditionsCount <= 1) {
                $val .= ''.$chWhere.'';
            } else {
                $val .= '('.trim(trim($chWhere, 'or '), 'and ').')';
            }
        }

        if ($this->getCondition() !== null) {
            if ($this->conditionsCount == 1) {
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

        parent::setValue(trim($val));
        $this->isCompiled = true;
        return parent::getValue();
    }
    /**
     * Sets the condition at which the expression will use to combine with children 
     * expressions.
     * 
     * @param string $cond A string such as 'and' or 'or'.
     * 
     */
    public function setJoinCondition(string $cond) {
        $this->joinCond = $cond;
        $this->isCompiled = false;
    }
    /**
     * Sets the parent of the expression.
     * 
     * This one is used to make the expression as a sub where condition.
     * 
     * @param WhereExpression $whereExpr The parent expression.
     * 
     */
    public function setParent(WhereExpression $whereExpr) {
        $this->parentWhere = $whereExpr;
        $whereExpr->children[] = $this;
    }
}
