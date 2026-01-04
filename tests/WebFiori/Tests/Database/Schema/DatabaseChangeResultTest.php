<?php

namespace WebFiori\Tests\Database\Schema;

use PHPUnit\Framework\TestCase;
use WebFiori\Database\Schema\DatabaseChangeResult;

class DatabaseChangeResultTest extends TestCase {
    
    public function testEmptyResult() {
        $result = new DatabaseChangeResult();
        
        $this->assertEmpty($result->getApplied());
        $this->assertEmpty($result->getSkipped());
        $this->assertEmpty($result->getFailed());
        $this->assertEquals(0, $result->getTotalTime());
        $this->assertTrue($result->isSuccessful());
        $this->assertEquals(0, count($result));
    }
    
    public function testAddApplied() {
        $result = new DatabaseChangeResult();
        $change = new SuccessfulMigration();
        
        $result->addApplied($change);
        
        $this->assertCount(1, $result->getApplied());
        $this->assertSame($change, $result->getApplied()[0]);
        $this->assertEquals(1, count($result));
    }
    
    public function testAddSkipped() {
        $result = new DatabaseChangeResult();
        $change = new SuccessfulMigration();
        
        $result->addSkipped($change, 'Already applied');
        
        $this->assertCount(1, $result->getSkipped());
        $this->assertSame($change, $result->getSkipped()[0]['change']);
        $this->assertEquals('Already applied', $result->getSkipped()[0]['reason']);
    }
    
    public function testAddFailed() {
        $result = new DatabaseChangeResult();
        $change = new SuccessfulMigration();
        $error = new \Exception('Test error');
        
        $result->addFailed($change, $error);
        
        $this->assertCount(1, $result->getFailed());
        $this->assertSame($change, $result->getFailed()[0]['change']);
        $this->assertSame($error, $result->getFailed()[0]['error']);
        $this->assertFalse($result->isSuccessful());
    }
    
    public function testTotalTime() {
        $result = new DatabaseChangeResult();
        
        $result->setTotalTime(123.45);
        
        $this->assertEquals(123.45, $result->getTotalTime());
    }
    
    public function testCountableInterface() {
        $result = new DatabaseChangeResult();
        $result->addApplied(new SuccessfulMigration());
        $result->addApplied(new DevOnlySeeder());
        
        $this->assertEquals(2, count($result));
    }
    
    public function testIteratorInterface() {
        $result = new DatabaseChangeResult();
        $m1 = new SuccessfulMigration();
        $m2 = new DevOnlySeeder();
        $result->addApplied($m1);
        $result->addApplied($m2);
        
        $iterated = [];
        foreach ($result as $change) {
            $iterated[] = $change;
        }
        
        $this->assertCount(2, $iterated);
        $this->assertSame($m1, $iterated[0]);
        $this->assertSame($m2, $iterated[1]);
    }
    
    public function testIsSuccessfulWithMixedResults() {
        $result = new DatabaseChangeResult();
        $result->addApplied(new SuccessfulMigration());
        $result->addSkipped(new DevOnlySeeder(), 'Environment');
        
        $this->assertTrue($result->isSuccessful());
        
        $result->addFailed(new FailingMigration(), new \Exception('Error'));
        
        $this->assertFalse($result->isSuccessful());
    }
}
