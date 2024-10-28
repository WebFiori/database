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

/**
 * A class that represents a binary conditional statement.
 * 
 * A binary conditional statement is a statement that has two operands 
 * combined using an operator like the equal.
 *
 * @author Ibrahim
 * 
 * @since 1.0.2
 */
class Condition {
    /**
     * The condition which is used to combine the two sides of the condition.
     * 
     * @var string
     * 
     * @since 1.0 
     */
    private $condition;
    /**
     * The left hand side operand of the condition.
     * 
     * @var mixed
     * 
     * @since 1.0 
     */
    private $leftOperand;
    /**
     * The right hand side operand of the condition.
     * 
     * @var mixed
     * 
     * @since 1.0 
     */
    private $rightOperand;
    /**
     * Creates new instance of the class.
     * 
     * @param string|Expression|Condition $leftOperand The left hand side 
     * operand of the condition.
     * 
     * @param string|Expression|Condition  $rightOperand The right hand side 
     * operand of the condition.
     * 
     * @param string $condition A string which is used to join the two sides 
     * (such as '=', '!=', 'and', 'or', etc...)
     * 
     * @since 1.0
     */
    public function __construct($leftOperand, $rightOperand, string $condition = null) {
        $this->setLeftOperand($leftOperand);
        $this->setRightOperand($rightOperand);
        $this->condition = '';
        $this->setCondition($condition);
    }
    /**
     * Creates and returns a string that represents the condition.
     * 
     * @return string A string which looks like 'A = B' where 'A' is the left 
     * hand side operand and 'B' is right hand side operand and the '=' is the 
     * condition. Note that if left side operand is not null and right operand is 
     * null, the method will return the left operand without a condition and vise versa. If the 
     * two operands are null, the method will return empty string.
     * 
     * @since 1.0
     */
    public function __toString() {
        $right = $this->getRightOperand();
        $left = $this->getLeftOperand();

        if ($right !== null && $left !== null) {
            return $left.' '.$this->getCondition().' '.$right;
        } else if ($left !== null) {
            return $left.'';
        } else if ($right !== null) {
            return $right.'';
        } else {
            return '';
        }
    }
    /**
     * Checks if two conditions represent same condition.
     * 
     * Two conditions are equal if they have the same string representation.
     * 
     * @param Condition $cond The condition that will be checked with.
     * 
     * @return bool If the two are equals, the method will return true. 
     * False otherwise.
     * 
     * @since 1.0
     */
    public function equals(Condition $cond) : bool {
        return $this.'' == $cond.'';
    }
    /**
     * Returns the condition which is used to combine the two operands of the condition.
     * 
     * @return string A string which is used to join the two sides 
     * (such as '=', '!=', 'and', 'or', etc...)
     * 
     * @since 1.0
     */
    public function getCondition() : string {
        return $this->condition;
    }
    /**
     * Returns the left hand side operand of the condition.
     * 
     * @return string|Expression|Condition The left hand side operand of the condition.
     * 
     * @since 1.0
     */
    public function getLeftOperand() {
        return $this->leftOperand;
    }
    /**
     * Returns the right hand side operand of the condition.
     * 
     * @return string|Expression|Condition The right hand side operand of the condition.
     * 
     * @since 1.0
     */
    public function getRightOperand() {
        return $this->rightOperand;
    }
    /**
     * Sets the value of the condition which is used to join left side operand 
     * and right side operand.
     * 
     * @param string $cond A string such as '=', '!=', '&&' or any such value.
     * 
     * @since 1.0.2
     */
    public function setCondition(string $cond = null) {
        $conditionT = trim($cond.'');

        if (strlen($conditionT) != 0) {
            $this->condition = $conditionT;
        }
    }
    /**
     * Sets the left hand side operand of the condition.
     * 
     * @param string|Expression|Condition $op The left hand side operand 
     * of the condition.
     * 
     * @since 1.0.1
     */
    public function setLeftOperand($op) {
        $this->leftOperand = $op;
    }
    /**
     * Sets the right hand side operand of the condition.
     * 
     * @param string|Expression|Condition $op The right hand side operand 
     * of the condition.
     * 
     * @since 1.0.1
     */
    public function setRightOperand($op) {
        $this->rightOperand = $op;
    }
}
