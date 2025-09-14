<?php

namespace WebFiori\Tests\Database\Common;
use WebFiori\Database\Condition;
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
    /**
     * @test
     */
    public function testToString00() {
        $condition0 = new Condition('B', 'A', '=');
        $this->assertEquals('B = A', $condition0.'');
    }
    /**
     * @test
     */
    public function testToString01() {
        $condition0 = new Condition(null, null, '=');
        $this->assertEquals('', $condition0.'');
    }
    /**
     * @test
     */
    public function testToString02() {
        $condition0 = new Condition('A', null, '=');
        $this->assertEquals('A', $condition0.'');
    }
    /**
     * @test
     */
    public function testToString03() {
        $condition0 = new Condition(null, 'B', '=');
        $this->assertEquals('B', $condition0.'');
    }
}
