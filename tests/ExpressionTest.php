<?php
namespace webfiori\database\tests;
use PHPUnit\Framework\TestCase;
use webfiori\database\Expression;

/**
 * Description of ExpressionTest
 *
 * @author Ibrahim
 */
class ExpressionTest extends TestCase {
    /**
     * @test
     */
    public function test00() {
        $exp = new Expression('x = y');
        $this->assertEquals('x = y', $exp->getValue());
        $exp->setVal('z != 9');
        $this->assertEquals('z != 9', $exp->getValue());
    }
    /**
     * @test
     */
    public function testEquals00() {
        $exp0 = new Expression('x = y');
        $exp1 = new Expression('x = y');
        $this->assertTrue($exp0->equals($exp1));
        $exp0->setVal('z != 9');
        $this->assertFalse($exp1->equals($exp0));
    }
}
