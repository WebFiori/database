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
        $data = $set->map(function ($data) {
            $retVal = [];
            foreach ($data as $record) {
                if ($record['col_2'] == 'ok') {
                    $retVal[] = $record['col_4'];
                }
            }
            return $retVal;
        });
        $this->assertEquals(7, $set->getRowsCount());
        $this->assertEquals(4, $set->getMappedRowsCount());
        $this->assertEquals([1, 2, 4, 5], $data);
        
        $count = 0;
        foreach ($set as $record) {
            $count++;
        }
        $this->assertEquals(4, $count);
        
        $data2 = $set->map(function ($data) {
            $retVal = [];
            foreach ($data as $record) {
                if ($record['col_3'] == 'Cool') {
                    $retVal[] = $record['col_4'];
                }
            }
            return $retVal;
        });
        $this->assertEquals(7, $set->getRowsCount());
        $this->assertEquals(5, $set->getMappedRowsCount());
        $this->assertEquals([0,2,3,5,6], $data2);
    }
    /**
     * @test
     */
    public function test03() {
        $this->expectException(\webfiori\database\DatabaseException::class);
        $this->expectExceptionMessage('Map function is expected to return an array. integer is returned.');
        $set = new ResultSet([
            ['col_1' => 'Super', 'col_2' => 'not_ok', 'col_3' => 'Cool', 'col_4' => 0],
            ['col_1' => 'Super-2', 'col_2' => 'ok', 'col_3' => 'Not Cool', 'col_4' => 1],
        ]);
        $set->map(function () {
            return 33;
        });
    }
}
