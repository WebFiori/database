<?php

namespace webfiori\database\tests\common;

use webfiori\database\ResultSet;
use PHPUnit\Framework\TestCase;
/**
 * Description of ResultSetTest
 *
 * @author Ibrahim
 */
class ResultSetTest extends TestCase {
    /**
     * @test
     */
    public function test00() {
        $set = new ResultSet([
            ['col_1' => 'Super', 'col_2' => 'not_ok', 'col_3' => 'Cool', 'col_4' => 0],
            ['col_1' => 'Super-2', 'col_2' => 'ok', 'col_3' => 'Not Cool', 'col_4' => 1],
            ['col_1' => 'Super-3', 'col_2' => 'ok', 'col_3' => 'Cool', 'col_4' => 2],
            ['col_1' => 'Super-4', 'col_2' => 'not_ok', 'col_3' => 'Cool', 'col_4' => 3],
            ['col_1' => 'Super-5', 'col_2' => 'ok', 'col_3' => 'Not Cool', 'col_4' => 4],
            ['col_1' => 'Super-6', 'col_2' => 'ok', 'col_3' => 'Cool', 'col_4' => 5],
            ['col_1' => 'Super-7', 'col_2' => 'not_ok', 'col_3' => 'Cool', 'col_4' => 6]
        ]);
        $this->assertEquals(7, $set->getRowsCount());
        $this->assertEquals(7, $set->getMappedRowsCount());
        $this->assertEquals([
            ['col_1' => 'Super', 'col_2' => 'not_ok', 'col_3' => 'Cool', 'col_4' => 0],
            ['col_1' => 'Super-2', 'col_2' => 'ok', 'col_3' => 'Not Cool', 'col_4' => 1],
            ['col_1' => 'Super-3', 'col_2' => 'ok', 'col_3' => 'Cool', 'col_4' => 2],
            ['col_1' => 'Super-4', 'col_2' => 'not_ok', 'col_3' => 'Cool', 'col_4' => 3],
            ['col_1' => 'Super-5', 'col_2' => 'ok', 'col_3' => 'Not Cool', 'col_4' => 4],
            ['col_1' => 'Super-6', 'col_2' => 'ok', 'col_3' => 'Cool', 'col_4' => 5],
            ['col_1' => 'Super-7', 'col_2' => 'not_ok', 'col_3' => 'Cool', 'col_4' => 6]
        ], $set->getRows());
        $this->assertEquals([
            ['col_1' => 'Super', 'col_2' => 'not_ok', 'col_3' => 'Cool', 'col_4' => 0],
            ['col_1' => 'Super-2', 'col_2' => 'ok', 'col_3' => 'Not Cool', 'col_4' => 1],
            ['col_1' => 'Super-3', 'col_2' => 'ok', 'col_3' => 'Cool', 'col_4' => 2],
            ['col_1' => 'Super-4', 'col_2' => 'not_ok', 'col_3' => 'Cool', 'col_4' => 3],
            ['col_1' => 'Super-5', 'col_2' => 'ok', 'col_3' => 'Not Cool', 'col_4' => 4],
            ['col_1' => 'Super-6', 'col_2' => 'ok', 'col_3' => 'Cool', 'col_4' => 5],
            ['col_1' => 'Super-7', 'col_2' => 'not_ok', 'col_3' => 'Cool', 'col_4' => 6]
        ], $set->getMappedRows());
        
        $index = 0;
        
        foreach ($set as $record) {
            $this->assertEquals($index, $record['col_4']);
            $index++;
        }
        $set->clearSet();
        
        $this->assertEquals(0, $set->getRowsCount());
        $this->assertEquals(0, $set->getMappedRowsCount());
        $this->assertEquals([], $set->getRows());
        $this->assertEquals([], $set->getMappedRows());
    }
    
    /**
     * @test
     */
    public function test01() {
        $set = new ResultSet();
        $this->assertEquals(0, $set->getRowsCount());
        $this->assertEquals(0, $set->getMappedRowsCount());
        $this->assertEquals([], $set->getRows());
        $this->assertEquals([], $set->getMappedRows());
        
        $set->setData([
            ['col_1' => 'Super', 'col_2' => 'not_ok', 'col_3' => 'Cool', 'col_4' => 0],
            ['col_1' => 'Super-2', 'col_2' => 'ok', 'col_3' => 'Not Cool', 'col_4' => 1],
            ['col_1' => 'Super-3', 'col_2' => 'ok', 'col_3' => 'Cool', 'col_4' => 2],
            ['col_1' => 'Super-4', 'col_2' => 'not_ok', 'col_3' => 'Cool', 'col_4' => 3],
            ['col_1' => 'Super-5', 'col_2' => 'ok', 'col_3' => 'Not Cool', 'col_4' => 4],
            ['col_1' => 'Super-6', 'col_2' => 'ok', 'col_3' => 'Cool', 'col_4' => 5],
            ['col_1' => 'Super-7', 'col_2' => 'not_ok', 'col_3' => 'Cool', 'col_4' => 6]
        ]);
        
        $this->assertEquals(7, $set->getRowsCount());
        $this->assertEquals(7, $set->getMappedRowsCount());
        $data = $set->map(function ($record) {
            if ($record['col_2'] == 'ok') {
                return $record['col_4'];
            }
        });
        $this->assertEquals(7, $set->getRowsCount());
        $this->assertEquals(7, $set->getMappedRowsCount());
        $this->assertEquals([null, 1, 2,null, 4, 5, null], $data);
        
        
        $data2 = $set->map(function ($record) {
            if ($record['col_3'] == 'Cool') {
                return $record['col_4'];
            }
        });
        $this->assertEquals(7, $set->getRowsCount());
        $this->assertEquals(7, $set->getMappedRowsCount());
        $this->assertEquals([0,null,2,3,null,5,6], $data2);
    }
    /**
     * @test
     */
    public function test03() {
        $set = new ResultSet([
            ['col_1' => 'Super', 'col_2' => 'not_ok', 'col_3' => 'Cool', 'col_4' => 0],
            ['col_1' => 'Super-2', 'col_2' => 'ok', 'col_3' => 'Not Cool', 'col_4' => 1],
        ]);
        $result = $set->map(function () {
            return 33;
        });
        $this->assertEquals([33, 33], $result);
    }
}
