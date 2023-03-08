<?php
/**
 * This file is licensed under MIT License.
 * 
 * Copyright (c) 2019 Ibrahim BinAlshikh
 * 
 * For more information on the license, please visit: 
 * https://github.com/WebFiori/.github/blob/main/LICENSE
 * 
 */
namespace webfiori\database;

use Exception;
use Throwable;
/**
 * An exception which is thrown to indicate that an error which is related to 
 * database occurred.
 *
 * @author Ibrahim
 * 
 * @since 1.0
 */
class DatabaseException extends Exception {
    private $sqlQuery;
    public function __construct($message = "", $code = 0, string $sql = '', Throwable $previous = null) {
        parent::__construct($message, $code, $previous);
        $this->sqlQuery = $sql;
    }

    /**
     * Returns the query that caused the exception to be thrown.
     *
     * If no query caused the exception, empty string is returned.
     *
     * @return string The query that caused the exception to be thrown.
     */
    public function getSQLQuery() : string {
        return $this->sqlQuery;
    }
}
