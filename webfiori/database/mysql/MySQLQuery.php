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
namespace webfiori\database\mysql;

use webfiori\database\AbstractQuery;
use webfiori\database\Column;
use webfiori\database\DatabaseException;

/**
 * A class which is used to build MySQL queries.
 *
 * @author Ibrahim
 * 
 * @version 1.0.2
 */
class MySQLQuery extends AbstractQuery {
    /**
     * An attribute that is set to true if the query is an update or insert of 
     * blob datatype.
     * 
     * @var boolean 
     * 
     * @since 1.0
     */
    private $isFileInsert;
    /**
     * Build a query which can be used to add a column to associated table.
     * 
     * @param string $colKey The key of the column taken from the table.
     * 
     * @param string $location The location at which the column will be added to. 
     * It can be the word 'first' or the key of the column at which the new column 
     * will be added after.
     * 
     * @throws DatabaseException If no column which has the given key, the method 
     * will throw an exception.
     * 
     * @return MySQLQuery The method will return the same instance at which the 
     * method is called on.
     * 
     * @since 1.0
     */
    public function addCol(string $colKey, ?string $location) {
        $tblName = $this->getTable()->getName();
        $colToAdd = $this->getTable()->getColByKey($colKey);

        if (!($colToAdd instanceof Column)) {
            throw new DatabaseException("The table '$tblName' has no column with key '$colKey'.");
        } 
        $this->alterColStm('add', $colToAdd, $location, $tblName);

        return $this;
    }
    public function __construct() {
        parent::__construct();
        $this->bindings = [
            'bind' => '',
            'values' => []
        ];
    }
    /**
     * Constructs a query that can be used to add a primary key to the active table.
     * 
     * @return MySQLQuery The method will return the same instance at which the 
     * method is called on.
     * 
     * @since 1.0
     */
    public function addPrimaryKey(string $pkName, array $pkCols) {
        $trimmedPkName = self::backtick(trim($pkName));
        $tableObj = $this->getTable();
        $keyCols = [];

        foreach ($pkCols as $colKey) {
            $col = $tableObj->getColByKey($colKey);

            if ($col instanceof MySQLColumn) {
                $keyCols[] = $col->getName();
            }
        }
        $stm = 'alter table '.$tableObj->getName().' add constraint '.$trimmedPkName.' '
                .'primary key ('.implode(', ', $keyCols).');';
        $this->setQuery($stm);

        return $this;
    }
    /**
     * Adds a backtick character around a string.
     * 
     * @param string $str This can be the name of a column in a table or the name 
     * of a table.
     * 
     * @return string|null The method will return a string surounded by backticks. 
     * If empty string is given, the method will return empty string.
     * 
     * @since 1.0
     */
    public static function backtick($str) {
        $trimmed = trim($str.'');

        if (strlen($trimmed) != 0) {
            $exp = explode('.', $trimmed);

            $arr = [];

            foreach ($exp as $xStr) {
                $arr[] = '`'.trim($xStr,'`').'`';
            }

            return implode('.', $arr);
        }

        return '';
    }

    /**
     * Creates and returns a copy of the builder.
     * 
     * The information that will be copied includes:
     * <ul>
     * <li>Limit.</li>
     * <li>Offset.</li>
     * <li>Linked table.</li>
     * <li>Linked schema.</li>
     * </ul>
     * 
     * @return AbstractQuery
     * 
     * @since 1.0
     */
    public function copyQuery(): AbstractQuery {
        $copy = new MySQLQuery();
        $copy->limit($this->getLimit());
        $copy->offset($this->getOffset());
        $copy->setTable($this->getTable(), false);
        $copy->setSchema($this->getSchema());
        $copy->setBindings($this->getBindings());
        return $copy;
    }
    /**
     * Constructs a query which can be used to remove a record from the associated 
     * table.
     * 
     * @return MySQLQuery The method will return the same instance at which the 
     * method is called on.
     * 
     * @since 1.0
     */
    public function delete() {
        $tblName = $this->getTable()->getName();
        $this->setQuery("delete from $tblName");

        return $this;
    }
    /**
     * Constructs a query that can be used to drop foreign key constraint.
     * 
     * @param string $keyName The name of the key.
     * 
     * @return MySQLQuery The method should return the same instance at which 
     * the method is called on.
     * 
     * @since 1.0.1
     */
    public function dropForeignKey($keyName) {
        $trimmed = trim($keyName);

        if (strlen($trimmed) != 0) {
            $tblName = $this->getTable()->getName();
            $query = "alter table $tblName drop foreign key $trimmed;";
            $this->setQuery($query);
        }

        return $this;
    }
    /**
     * Build a query which is used to drop primary key of linked table.
     * 
     * @param null $pkName Not used.
     * 
     * @return MySQLQuery The method will return the same instance at which the 
     * method is called on.
     * 
     * @since 1.0
     */
    public function dropPrimaryKey(?string $pkName = null) {
        $this->setQuery('alter table '.$this->getTable()->getName().' drop primary key;');

        return $this;
    }
    /**
     * Returns the generated SQL query.
     * 
     * @return string Returns the generated query as string.
     * 
     * @since 1.0
     */
    public function getQuery() {
        $query = parent::getQuery();

        if ($this->getLastQueryType() == 'select' && $this->getLimit() > 0) {
            $query .= ' limit '.$this->getLimit();

            if ($this->getOffset() > 0) {
                $query .= ' offset '.$this->getOffset();
            }
        }

        return $query;
    }
    public function insert(array $colsAndVals): AbstractQuery {
        $this->setInsertBuilder(new MySQLInsertBuilder($this->getTable(), $colsAndVals));

        return $this;
    }
    /**
     * Checks if the query represents a blob insert or update.
     * 
     * The aim of this method is to fix an issue with setting the collation 
     * of the connection while executing a query.
     * 
     * @return bool The method will return true if the query represents an 
     * insert or an update of blob datatype. false if not.
     * 
     * @since 1.0
     */
    public function isBlobInsertOrUpdate() {
        return $this->isFileInsert;
    }
    /**
     * Build a query which can be used to modify a column in associated table.
     * 
     * @param string $colKey The key of the column taken from the table.
     * 
     * @param string $location The location at which the column will be moved to (optional). 
     * It can be the word 'first' or the key of the column at which the column 
     * will be added after.
     * 
     * @throws DatabaseException If no column which has the given key, the method 
     * will throw an exception.
     * 
     * @return MySQLQuery The method will return the same instance at which the 
     * method is called on.
     * 
     * @since 1.0
     */
    public function modifyCol($colKey, ?string $location = null) {
        $tblName = $this->getTable()->getName();
        $colObj = $this->getTable()->getColByKey($colKey);

        if (!($colObj instanceof MySQLColumn)) {
            throw new DatabaseException("The table '$tblName' has no column with key '$colKey'.");
        }

        $this->alterColStm('modify', $colObj, $location, $tblName);

        return $this;
    }
    /**
     * Constructs a query which can be used to modify the name of 
     * a column.
     * 
     * @param string $colKey Column key.
     * 
     * @return MySQLQuery The method will return the same instance at which the 
     * method is called on.
     * 
     * @throws DatabaseException The method will throw an exception if 
     * the table has no column with given key or the name of the 
     * specified column was not changed.
     * 
     * @since 1.0.1
     */
    public function renameCol($colKey) {
        $colObj = $this->getTable()->getColByKey($colKey);
        $tblName = $this->getTable()->getName();

        if (!$colObj instanceof MySQLColumn) {
            throw new DatabaseException("The table $tblName has no column with key '$colKey'.");
        }

        $split = explode('.', $colObj->getMySQLVersion());
        $oldName = $colObj->getOldName();
        $newName = $colObj->getName();

        if (isset($split[0]) && intval($split[0]) >= 8) {
            //8.0 support new syntax
            $query = "alter table $tblName rename column $oldName to $newName;";
        } else {
            $colDef = substr($colObj->asString(), strlen($newName));
            $query = "alter table $tblName change column $oldName $newName $colDef;";
        }
        $this->setQuery($query);

        return $this;
    }
    /**
     * Constructs a query which can be used to replace a record (insert or update if 
     * exist).
     * 
     * @param array $colsAndVals An associative array. The indices are columns 
     * keys and the value of each index is the value of the column. This also
     * can be one big indexed array of sub associative arrays. This approach can 
     * be used to build multiple replace queries.
     * 
     * @return MySQLQuery The method will return the same instance at which the 
     * method is called on.
     * 
     * @since 1.0.2
     */
    public function replace(array $colsAndVals) {
        $this->insert($colsAndVals);
        $query = $this->getInsertBuilder()->getQuery();
        $this->setQuery(str_replace('insert', 'replace', $query));

        return $this;
    }
    /**
     * Sets the property that is used to check if the query represents an insert 
     * or an update of a blob datatype.
     * 
     * The attribute is used to fix an issue with setting the collation 
     * of the connection while executing a query.
     * 
     * @param boolean $boolean true if the query represents an insert or an update 
     * of a blob datatype. false if not.
     * 
     * @since 1.0
     */
    public function setIsBlobInsertOrUpdate($boolean) {
        $this->isFileInsert = $boolean === true ? true : false;
    }
    /**
     * Constructs an update query.
     * 
     * @param array $newColsVals An associative array. The indices of the array 
     * are columns keys and the values are the new values for the columns.
     * 
     * @return MySQLQuery The method will return the same instance at which the 
     * method is called on.
     * 
     * @throws DatabaseException If one of the columns does not exist, the method 
     * will throw an exception.
     * 
     * @since 1.0
     */
    public function update(array $newColsVals) {
        $updateArr = [];
        $colsWithVals = [];
        $tblName = $this->getTable()->getName();
        $table = $this->getTable();

        foreach ($newColsVals as $colKey => $newVal) {
            $colObj = $table->getColByKey($colKey);

            if (!$colObj instanceof MySQLColumn) {
                $table->addColumns([
                    $colKey => ['type' => $this->getColType($newVal)]
                ]);
                $colObj = $table->getColByKey($colKey);
            }
            $colName = $colObj->getName();

            if ($newVal === null) {
                $updateArr[] = "$colName = null";
            } else {
                $this->addBinding($colObj, $newVal);
                $updateArr[] = "$colName = ?";
            }
            $colsWithVals[] = $colKey;
        }

        foreach ($this->getTable()->getColsKeys() as $key) {
            if (!in_array($key, $colsWithVals)) {
                $colObj = $this->getTable()->getColByKey($key);

                if (($colObj->getDatatype() == 'datetime' || $colObj->getDatatype() == 'timestamp') && $colObj->isAutoUpdate()) {
                    $updateArr[] = $colObj->getName()." = ?";
                    $this->addBinding($colObj, date('Y-m-d H:i:s'));
                }
            }
        }
        $query = "update $tblName set ".implode(', ', $updateArr);
        $this->setQuery($query);

        return $this;
    }
    /**
     * 
     * @param type $alterOpType
     * @param Column $colToAdd
     * @param type $location
     * @param type $tblName
     * @throws DatabaseException
     */
    private function alterColStm($alterOpType, $colToAdd, $location, $tblName) {
        $colObjAsStr = $colToAdd->asString();

        if ($alterOpType == 'modify') {
            $stm = "alter table $tblName change column ".MySQLQuery::backtick($colToAdd->getName())." $colObjAsStr";
        } else {
            $stm = "alter table $tblName add $colObjAsStr";
        }

        if ($location !== null) {
            $lower = trim(strtolower($location));
            $colObj = $this->getTable()->getColByKey($location);

            if ($lower == 'first') {
                $stm .= ' first';
            } else {
                if ($colObj instanceof MySQLColumn) {
                    $colIndex = $colObj->getIndex();

                    if ($colIndex == 0) {
                        $stm .= " first";
                    } else {
                        $colName = $colObj->getName();
                        $stm .= " after $colName";
                    }
                } else {
                    throw new DatabaseException("The table '$tblName' has no column with key '$location'.");
                }
            }
        }
        $this->setQuery($stm.';');
    }
    private $bindings;
    public function addBinding(Column $col, $value) {
        $colType = $col->getDatatype();
        
        $this->bindings['values'][] = $value;

        if ($colType == 'int' || $colType == 'bit' || in_array($colType, Column::BOOL_TYPES)) {
            $this->bindings['bind'] .= 'i';
        } else if ($colType == 'decimal' || $colType == 'float') {
            $this->bindings['bind'] .= 'd';
        } else {
            $this->bindings['bind'] .= 's';
        }
    }
    public function resetBinding() {
        $this->bindings = [
            'bind' => '',
            'values' => []
        ];
    }
    public function setBindings(array $bindings, string $merge = 'none') {
        $currentBinding = $this->bindings['bind'];
        $values = $this->bindings['values'];
        
        if ($merge == 'first') {
            $this->bindings = [
                'bind' => $bindings['bind'].$currentBinding,
                'values' => array_merge($bindings['values'], $values)
            ];
        } else if ($merge == 'end') {
            $this->bindings = [
                'bind' => $currentBinding.$bindings['bind'],
                'values' => array_merge($values, $bindings['values'])
            ];
        } else {
            $this->bindings = $bindings;
        }
    }
    public function getBindings(): array {
        return $this->bindings;
    }
}
