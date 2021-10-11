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
    //put your code here
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
