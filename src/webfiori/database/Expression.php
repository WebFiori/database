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
 * A class that can be used to represent any SQL expression.
 *
 * @author Ibrahim
 * 
 * @version 1.0
 */
class Expression {
    /**
     * The expression as string.
     * 
     * @var string 
     * 
     * @since 1.0
     */
    private $expr;
    /**
     * Creates new expression.
     * 
     * @param string $val a string that represents the value of the expression.
     * 
     * @since 1.0
     */
    public function __construct($val) {
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
     * @since 1.0
     */
    public function __toString() {
        return $this->getValue();
    }
    /**
     * Checks if two expressions represent same expression.
     * 
     * @param Expression $exp The expression that will be checked with.
     * 
     * @return boolean If the two are equals, the method will return true. 
     * False otherwise.
     * 
     * @since 1.0
     */
    public function equals(Expression $exp) {
        return $this.'' == $exp.'';
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
        return $this->expr;
    }
    /**
     * Sets the value of the expression.
     * 
     * @param string $val A string that represents the value of the expression.
     * 
     * @since 1.0
     */
    public function setVal($val) {
        $this->expr = $val;
    }
}
