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
 * A class that can be used to represent any SQL expression.
 *
 * @author Ibrahim
 * 
 */
class Expression {
    /**
     * The expression as string.
     * 
     * @var string 
     * 
     */
    private $expr;
    /**
     * Creates new expression.
     * 
     * @param string $val a string that represents the value of the expression.
     * 
     */
    public function __construct(string $val) {
        $this->expr = $val.'';
    }
    /**
     * Returns the value of the expression.
     * 
     * Similar to calling Expression::getValue()
     * 
     * @return string The method will return a string that represents the value 
     * of the expression.
     * 
     */
    public function __toString() {
        return $this->getValue();
    }
    /**
     * Checks if two expressions represent same expression.
     * 
     * @param Expression $exp The expression that will be checked with.
     * 
     * @return bool If the two are equals, the method will return true. 
     * False otherwise.
     * 
     */
    public function equals(Expression $exp) : bool {
        return $this.'' == $exp.'';
    }
    /**
     * Returns the value of the expression.
     * 
     * @return string The method will return a string that represents the value 
     * of the expression.
     * 
     */
    public function getValue() : string {
        return $this->expr;
    }
    /**
     * Sets the value of the expression.
     * 
     * @param string $val A string that represents the value of the expression.
     * 
     */
    public function setValue(string $val) {
        $this->expr = $val;
    }
}
