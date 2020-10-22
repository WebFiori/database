<?php

namespace webfiori\database\tests;
use webfiori\database\Condition;
use PHPUnit\Framework\TestCase;
/**
 * Description of ConditionTest
 *
 * @author Ibrahim
 */
class ConditionTest extends TestCase{
    /**
     * @test
     */
    public function test00() {
        $condition = new Condition('A', 'B', '=');
        $this->assertEquals('A = B', $condition.'');
    }
    /**
     * @test
     */
    public function testEquals00() {
        $condition0 = new Condition('A', 'B', '=');
        $condition1 = new Condition('A', 'B', '=');
        $this->assertTrue($condition1->equals($condition0));
    }
    /**
     * @test
     */
    public function testEquals01() {
        $condition0 = new Condition('A ', 'B', '=');
        $condition1 = new Condition('A', 'B', '=');
        $this->assertFalse($condition1->equals($condition0));
    }
    /**
     * @test
     */
    public function testEquals02() {
        $condition0 = new Condition('B', 'A', '=');
        $condition1 = new Condition('A', 'B', '=');
        $this->assertFalse($condition1->equals($condition0));
    }
}
