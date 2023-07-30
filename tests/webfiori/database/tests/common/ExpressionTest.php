<?php
namespace webfiori\database\tests\common;
use PHPUnit\Framework\TestCase;
use webfiori\database\Expression;
use webfiori\database\WhereExpression;
use webfiori\database\Condition;
use webfiori\database\SelectExpression;
use webfiori\database\mysql\MySQLTable;
use webfiori\database\mssql\MSSQLTable;
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
    /**
     * @test
     */
    public function testWhereExpression00() {
        $expression = new WhereExpression();
        $this->assertNull($expression->getCondition());
        $this->assertEquals('', $expression->getJoinCondition());
        $this->assertNull($expression->getParent());
        $this->assertEquals('', $expression->getValue());
    }
    /**
     * @test
     */
    public function testWhereExpression01() {
        $expression = new WhereExpression();
        $expression->addCondition(new Condition('A', 'B', '='), 'ok');
        $this->assertNotNull($expression->getCondition());
        $this->assertEquals('', $expression->getJoinCondition());
        $this->assertNull($expression->getParent());
        $this->assertEquals('A = B', $expression->getValue());
        
        $expression->addCondition(new Condition('C', 'D', '!='), 'and');
        $this->assertEquals('A = B and C != D', $expression->getValue());
        
        $expression->addCondition(new Expression('count(*) = 100'), 'and');
        $this->assertEquals('A = B and C != D and count(*) = 100', $expression->getValue());
        return $expression;
    }
    /**
     * @test
     */
    public function testSelectExpression00() {
        $t = new MySQLTable();
        $t->addColumns([
            'col-0' => [], 'col-1' => [], 'col-2' => [], 'col-3' => []
        ]);
        $expression = new SelectExpression($t);
        $this->assertFalse($expression->isInSelect('col-0'));
        $this->assertEquals(0, $expression->getColsCount());
        $this->assertEquals([], $expression->getColsKeys());
        $this->assertEquals('*', $expression->getColsStr());
        $this->assertEquals('', $expression->getGroupBy());
        $this->assertEquals('', $expression->getOrderBy());
        $this->assertEquals([], $expression->getSelectCols());
        $this->assertEquals('select * from `new_table`', $expression->getValue());
        
        $expression->addColumn('col-0');
        $this->assertTrue($expression->isInSelect('col-0'));
        $this->assertEquals(1, $expression->getColsCount());
        $this->assertEquals(['col-0'], $expression->getColsKeys());
        $this->assertEquals('`new_table`.`col_0`', $expression->getColsStr());
        $this->assertEquals('', $expression->getGroupBy());
        $this->assertEquals('', $expression->getOrderBy());
        $this->assertEquals([
            'col-0' => [
                'obj' => $t->getColByKey('col-0')
            ]
        ], $expression->getSelectCols());
        $this->assertEquals('select `new_table`.`col_0` from `new_table`', $expression->getValue());
        
        $expression->addColumn('col-1', [
            'as' => 'super_col',
            'aggregate' => 'max'
        ]);
        $this->assertEquals(2, $expression->getColsCount());
        $this->assertEquals(['col-0', 'col-1'], $expression->getColsKeys());
        $this->assertEquals('`new_table`.`col_0`, max(`new_table`.`col_1`) as `super_col`', $expression->getColsStr());
        $this->assertEquals('', $expression->getGroupBy());
        $this->assertEquals('', $expression->getOrderBy());
        $this->assertEquals([
            'col-0' => [
                'obj' => $t->getColByKey('col-0')
            ],
            'col-1' => [
                'obj' => $t->getColByKey('col-1'),
                'as' => 'super_col',
                'aggregate' => 'max'
            ]
        ], $expression->getSelectCols());
        $this->assertEquals('select `new_table`.`col_0`, max(`new_table`.`col_1`) as `super_col` from `new_table`', $expression->getValue());
        $expression->removeCol('col-0');
        $this->assertFalse($expression->isInSelect('col-0'));
        $this->assertEquals('select max(`new_table`.`col_1`) as `super_col` from `new_table`', $expression->getValue());
        $expression->groupBy('col-0');
        $this->assertEquals(' group by `new_table`.`col_0`', $expression->getGroupBy());
        $expression->groupBy('col-1');
        $this->assertEquals(' group by `new_table`.`col_0`, `new_table`.`col_1`', $expression->getGroupBy());
        
        $expression->orderBy('col-0');
        $this->assertEquals(' order by `new_table`.`col_0`', $expression->getOrderBy());
        $expression->orderBy('col-1', 'd');
        $this->assertEquals(' order by `new_table`.`col_0`, `new_table`.`col_1` desc', $expression->getOrderBy());
        $expression->orderBy('col-2', 'a');
        $this->assertEquals(' order by `new_table`.`col_0`, `new_table`.`col_1` desc, `new_table`.`col_2` asc', $expression->getOrderBy());
    }
    /**
     * @test
     */
    public function testSelectExpression01() {
        $t = new MySQLTable();
        $t->addColumns([
            'col-0' => [], 'col-1' => [], 'col-2' => [], 'col-3' => []
        ]);
        $expression = new SelectExpression($t);
        $expression->addColumn('not-exist');
        $this->assertEquals(1, $expression->getColsCount());
        $this->assertEquals('select `new_table`.`not_exist` from `new_table`', $expression->getValue());
    }
    /**
     * @test
     */
    public function testSelectExpression02() {
        $t = new MySQLTable();
        $t->addColumns([
            'col-0' => [], 'col-1' => [], 'col-2' => [], 'col-3' => []
        ]);
        $expression = new SelectExpression($t);
        $expression->orderBy('not-exist-1');
        $expression->groupBy('not-exist-2');
        $this->assertEquals(' order by `new_table`.`not_exist_1`', $expression->getOrderBy());
        $this->assertEquals(' group by `new_table`.`not_exist_2`', $expression->getGroupBy());
    }
    /**
     * @test
     */
    public function testSelectExpression04() {
        $t = new MySQLTable();
        $t->addColumns([
            'col-0' => [], 'col-1' => [], 'col-2' => [], 'col-3' => []
        ]);
        $expression = new SelectExpression($t);
        $expression->addWhere('col-1', null, '=');
        $this->assertEquals(' where col-1 is null', $expression->getWhereStr());
    }
    /**
     * @test
     */
    public function testSelectExpression05() {
        $t = new MySQLTable();
        $t->addColumns([
            'col-0' => [], 'col-1' => [], 'col-2' => [], 'col-3' => []
        ]);
        $expression = new SelectExpression($t);
        $expression->addWhere('col-1', null, '!=');
        $this->assertEquals(' where col-1 is not null', $expression->getWhereStr());
    }
}
