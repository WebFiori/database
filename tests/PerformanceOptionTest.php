<?php

namespace WebFiori\Database\Tests;

use PHPUnit\Framework\TestCase;
use WebFiori\Database\Performance\PerformanceOption;

/**
 * Test cases for PerformanceOption constants.
 */
class PerformanceOptionTest extends TestCase {
    
    public function testAllConstantsAreDefined() {
        $this->assertTrue(defined('WebFiori\Database\Performance\PerformanceOption::ENABLED'));
        $this->assertTrue(defined('WebFiori\Database\Performance\PerformanceOption::SLOW_QUERY_THRESHOLD'));
        $this->assertTrue(defined('WebFiori\Database\Performance\PerformanceOption::WARNING_THRESHOLD'));
        $this->assertTrue(defined('WebFiori\Database\Performance\PerformanceOption::SAMPLING_RATE'));
        $this->assertTrue(defined('WebFiori\Database\Performance\PerformanceOption::MAX_SAMPLES'));
        $this->assertTrue(defined('WebFiori\Database\Performance\PerformanceOption::STORAGE_TYPE'));
        $this->assertTrue(defined('WebFiori\Database\Performance\PerformanceOption::STORAGE_MEMORY'));
        $this->assertTrue(defined('WebFiori\Database\Performance\PerformanceOption::STORAGE_DATABASE'));
        $this->assertTrue(defined('WebFiori\Database\Performance\PerformanceOption::RETENTION_HOURS'));
        $this->assertTrue(defined('WebFiori\Database\Performance\PerformanceOption::AUTO_CLEANUP'));
        $this->assertTrue(defined('WebFiori\Database\Performance\PerformanceOption::MEMORY_LIMIT_MB'));
        $this->assertTrue(defined('WebFiori\Database\Performance\PerformanceOption::TRACK_SELECT'));
        $this->assertTrue(defined('WebFiori\Database\Performance\PerformanceOption::TRACK_INSERT'));
        $this->assertTrue(defined('WebFiori\Database\Performance\PerformanceOption::TRACK_UPDATE'));
        $this->assertTrue(defined('WebFiori\Database\Performance\PerformanceOption::TRACK_DELETE'));
    }
    
    public function testConstantValues() {
        $this->assertEquals('enabled', PerformanceOption::ENABLED);
        $this->assertEquals('slow_query_threshold', PerformanceOption::SLOW_QUERY_THRESHOLD);
        $this->assertEquals('warning_threshold', PerformanceOption::WARNING_THRESHOLD);
        $this->assertEquals('sampling_rate', PerformanceOption::SAMPLING_RATE);
        $this->assertEquals('max_samples', PerformanceOption::MAX_SAMPLES);
        $this->assertEquals('storage_type', PerformanceOption::STORAGE_TYPE);
        $this->assertEquals('memory', PerformanceOption::STORAGE_MEMORY);
        $this->assertEquals('database', PerformanceOption::STORAGE_DATABASE);
        $this->assertEquals('retention_hours', PerformanceOption::RETENTION_HOURS);
        $this->assertEquals('auto_cleanup', PerformanceOption::AUTO_CLEANUP);
        $this->assertEquals('memory_limit_mb', PerformanceOption::MEMORY_LIMIT_MB);
        $this->assertEquals('track_select', PerformanceOption::TRACK_SELECT);
        $this->assertEquals('track_insert', PerformanceOption::TRACK_INSERT);
        $this->assertEquals('track_update', PerformanceOption::TRACK_UPDATE);
        $this->assertEquals('track_delete', PerformanceOption::TRACK_DELETE);
    }
    
    public function testStorageTypeConstants() {
        $this->assertEquals('memory', PerformanceOption::STORAGE_MEMORY);
        $this->assertEquals('database', PerformanceOption::STORAGE_DATABASE);
        
        // Ensure storage types are different
        $this->assertNotEquals(PerformanceOption::STORAGE_MEMORY, PerformanceOption::STORAGE_DATABASE);
    }
    
    public function testConstantsAreStrings() {
        $this->assertIsString(PerformanceOption::ENABLED);
        $this->assertIsString(PerformanceOption::SLOW_QUERY_THRESHOLD);
        $this->assertIsString(PerformanceOption::STORAGE_TYPE);
        $this->assertIsString(PerformanceOption::STORAGE_MEMORY);
        $this->assertIsString(PerformanceOption::STORAGE_DATABASE);
    }
    
    public function testUsageInArrayConfiguration() {
        $config = [
            PerformanceOption::ENABLED => true,
            PerformanceOption::SLOW_QUERY_THRESHOLD => 1000,
            PerformanceOption::STORAGE_TYPE => PerformanceOption::STORAGE_MEMORY,
            PerformanceOption::SAMPLING_RATE => 0.5
        ];
        
        $this->assertTrue($config[PerformanceOption::ENABLED]);
        $this->assertEquals(1000, $config[PerformanceOption::SLOW_QUERY_THRESHOLD]);
        $this->assertEquals('memory', $config[PerformanceOption::STORAGE_TYPE]);
        $this->assertEquals(0.5, $config[PerformanceOption::SAMPLING_RATE]);
    }
}
