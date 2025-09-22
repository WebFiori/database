<?php

namespace WebFiori\Database\Performance;

/**
 * Configuration constants for query performance monitoring.
 * 
 * This class provides type-safe configuration options for the performance
 * monitoring system, following the same pattern as ColOption for consistency
 * with the existing WebFiori Database architecture.
 * 
 * @author Ibrahim
 */
class PerformanceOption {
    /**
     * Enable or disable performance monitoring.
     * 
     * @var string Value should be boolean (true/false)
     */
    const ENABLED = 'enabled';
    
    /**
     * Threshold in milliseconds for identifying slow queries.
     * 
     * @var string Value should be positive integer
     */
    const SLOW_QUERY_THRESHOLD = 'slow_query_threshold';
    
    /**
     * Warning threshold in milliseconds for query performance.
     * 
     * @var string Value should be positive integer
     */
    const WARNING_THRESHOLD = 'warning_threshold';
    
    /**
     * Sampling rate for query monitoring (0.0 to 1.0).
     * 
     * @var string Value should be float between 0.0 and 1.0
     */
    const SAMPLING_RATE = 'sampling_rate';
    
    /**
     * Maximum number of query metrics to store.
     * 
     * @var string Value should be positive integer
     */
    const MAX_SAMPLES = 'max_samples';
    
    /**
     * Storage type for performance metrics.
     * 
     * @var string Value should be STORAGE_MEMORY or STORAGE_DATABASE
     */
    const STORAGE_TYPE = 'storage_type';
    
    /**
     * Memory storage type - stores metrics in PHP memory.
     * 
     * @var string
     */
    const STORAGE_MEMORY = 'memory';
    
    /**
     * Database storage type - stores metrics in database table.
     * 
     * @var string
     */
    const STORAGE_DATABASE = 'database';
    
    /**
     * Data retention period in hours.
     * 
     * @var string Value should be positive integer
     */
    const RETENTION_HOURS = 'retention_hours';
    
    /**
     * Enable automatic cleanup of old metrics.
     * 
     * @var string Value should be boolean (true/false)
     */
    const AUTO_CLEANUP = 'auto_cleanup';
    
    /**
     * Memory limit in megabytes for in-memory storage.
     * 
     * @var string Value should be positive integer
     */
    const MEMORY_LIMIT_MB = 'memory_limit_mb';
    
    /**
     * Track SELECT query performance.
     * 
     * @var string Value should be boolean (true/false)
     */
    const TRACK_SELECT = 'track_select';
    
    /**
     * Track INSERT query performance.
     * 
     * @var string Value should be boolean (true/false)
     */
    const TRACK_INSERT = 'track_insert';
    
    /**
     * Track UPDATE query performance.
     * 
     * @var string Value should be boolean (true/false)
     */
    const TRACK_UPDATE = 'track_update';
    
    /**
     * Track DELETE query performance.
     * 
     * @var string Value should be boolean (true/false)
     */
    const TRACK_DELETE = 'track_delete';
}
