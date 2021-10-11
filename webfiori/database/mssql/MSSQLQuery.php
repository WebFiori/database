<?php
namespace webfiori\database\mssql;

use webfiori\database\AbstractQuery;
/**
 * A class which is used to build MSSQL queries.
 *
 * @author Ibrahim
 * 
 * @version 1.0
 */
class MSSQLQuery extends AbstractQuery {
    /**
     * Adds a square brackets around a string.
     * 
     * @param string $str This can be the name of a column in a table or the name 
     * of a table.
     * 
     * @return string|null The method will return a string surrounded by square 
     * brackets. 
     * If empty string is given, the method will return null.
     * 
     * @since 1.0
     */
    public static function squareBr($str) {
        $trimmed = trim($str);

        if (strlen($trimmed) != 0) {
            $exp = explode('.', $trimmed);

            $arr = [];

            foreach ($exp as $xStr) {
                $arr[] = '['.trim(trim($xStr, '['),']').']';
            }

            return implode('.', $arr);
        }
    }
    public function addCol($colKey, $location = null) {
        
        return $this;
    }

    public function addPrimaryKey($pkName, array $pkCols) {
        return $this;
    }

    public function delete() {
        return $this;
    }

    public function dropCol($colKey) {
        return $this;
    }

    public function dropPrimaryKey($pkName = null) {
        return $this;
    }

    public function insert(array $colsAndVals) {
        return $this;
    }

    public function modifyCol($colKey, $location = null) {
        return $this;
    }

    public function renameCol($colKey) {
        return $this;
    }

    public function update(array $newColsVals) {
        return $this;
    }

}
