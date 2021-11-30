<?php

/**
 * MIT License
 *
 * Copyright (c) 2019, WebFiori Framework.
 * 
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
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
     * @param string $colObjKey The key of the column taken from the table.
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
    public function addCol($colObjKey, $location = null) {
        $tblName = $this->getTable()->getName();
        $colToAdd = $this->getTable()->getColByKey($colObjKey);

        if (!($colToAdd instanceof Column)) {
            throw new DatabaseException("The table '$tblName' has no column with key '$colObjKey'.");
        } 
        $this->_alterColStm('add', $colToAdd, $location, $tblName);

        return $this;
    }
    /**
     * Constructs a query that can be used to add a primary key to the active table.
     * 
     * @return MySQLQuery The method will return the same instance at which the 
     * method is called on.
     * 
     * @since 1.0
     */
    public function addPrimaryKey($pkName, array $pkCols) {
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
     * If empty string is given, the method will return null.
     * 
     * @since 1.0
     */
    public static function backtick($str) {
        $trimmed = trim($str);

        if (strlen($trimmed) != 0) {
            $exp = explode('.', $trimmed);

            $arr = [];

            foreach ($exp as $xStr) {
                $arr[] = '`'.trim($xStr,'`').'`';
            }

            return implode('.', $arr);
        }
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
    public function dropPrimaryKey($pkName = null) {
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
    /**
     * Constructs a query which can be used to add new record.
     * 
     * @param array $colsAndVals An associative array. The indices are columns 
     * keys and the value of each index is the value of the column. This also
     * can be one big indexed array of sub associative arrays. This approach can 
     * be used to build multiple insert queries.
     * 
     * @return MySQLQuery The method will return the same instance at which the 
     * method is called on.
     * 
     * @since 1.0
     */
    public function insert(array $colsAndVals) {
        $this->insertHelper($colsAndVals);

        return $this;
    }
    private function insertHelper(array $colsAndVals, $isReplace = false) {
        if (isset($colsAndVals['cols']) && isset($colsAndVals['values'])) {
            $colsArr = [];
            $tblName = $this->getTable()->getName();

            foreach ($colsAndVals['cols'] as $colKey) {
                $colObj = $this->getTable()->getColByKey($colKey);

                if (!($colObj instanceof MySQLColumn)) {
                    throw new DatabaseException("The table $tblName has no column with key '$colKey'.");
                }
                $colObj->setWithTablePrefix(false);
                $colsArr[] = $colObj->getName();
            }
            $colsStr = '('.implode(', ', $colsArr).')';
            $suberValsArr = [];

            foreach ($colsAndVals['values'] as $valsArr) {
                $suberValsArr[] = '('.$this->_insertHelper($colsAndVals['cols'], $valsArr)['vals'].')';
            }
            $valsStr = implode(",\n", $suberValsArr);
            if ($isReplace) {
                $this->setQuery("replace into $tblName\n$colsStr\nvalues\n$valsStr;");
            } else {
                $this->setQuery("insert into $tblName\n$colsStr\nvalues\n$valsStr;");
            }
        } else {
            $this->setQuery($this->_createInsertStm($colsAndVals, $isReplace));
        }
    }
    /**
     * Checks if the query represents a blob insert or update.
     * 
     * The aim of this method is to fix an issue with setting the collation 
     * of the connection while executing a query.
     * 
     * @return boolean The method will return true if the query represents an 
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
    public function modifyCol($colKey, $location = null) {
        $tblName = $this->getTable()->getName();
        $colObj = $this->getTable()->getColByKey($colKey);

        if (!($colObj instanceof MySQLColumn)) {
            throw new DatabaseException("The table '$tblName' has no column with key '$colKey'.");
        }

        $this->_alterColStm('modify', $colObj, $location, $tblName);

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
        $this->insertHelper($colsAndVals, true);

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

        foreach ($newColsVals as $colKey => $newVal) {
            $colObj = $this->getTable()->getColByKey($colKey);

            if (!$colObj instanceof MySQLColumn) {
                throw new DatabaseException("The table '$tblName' has no column with key '$colKey'.");
            }
            $colName = $colObj->getName();

            if ($newVal === null) {
                $updateArr[] = "$colName = null";
            } else {
                $valClean = $colObj->cleanValue($newVal);
                $updateArr[] = "$colName = $valClean";
            }
            $colsWithVals[] = $colKey;
        }

        foreach ($this->getTable()->getColsKeys() as $key) {
            if (!in_array($key, $colsWithVals)) {
                $colObj = $this->getTable()->getColByKey($key);

                if (($colObj->getDatatype() == 'datetime' || $colObj->getDatatype() == 'timestamp') && $colObj->isAutoUpdate()) {
                    $updateArr[] = $colObj->getName()." = ".$colObj->cleanValue(date('Y-m-d H:i:s'));
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
    private function _alterColStm($alterOpType, $colToAdd, $location, $tblName) {
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
    private function _createInsertStm($colsAndVals, $replace = false) {
        $tblName = $this->getTable()->getName();

        $data = $this->_insertHelper(array_keys($colsAndVals), $colsAndVals);

        
        $cols = '('.$data['cols'].')';
        $vals = '('.$data['vals'].')';

        if ($replace === true) {
            return "replace into $tblName $cols values $vals;";
        } else {
            return "insert into $tblName $cols values $vals;";
        }
    }
    /**
     * Build a string that holds the values that will be inserted.
     * 
     * @param array $colsKeysArr An array that holds the keys of the 
     * columns of the record that will be inserted.
     * 
     * @param array $valuesToInsert An array that holds the values that will be 
     * inserted.
     * 
     * @return array The method will return an associative array with two indices. 
     * The index 'vals' will contain a string which represents the 
     * 'values' part of the query and 'cols' which holds the names of columns 
     * names as string.
     * 
     * @throws DatabaseException
     */
    private function _insertHelper(array $colsKeysArr, array $valuesToInsert) {
        $valsArr = [];
        $columnsWithVals = [];
        $colsNamesArr = [];
        $valIndex = 0;

        foreach ($colsKeysArr as $colKey) {
            $column = $this->getTable()->getColByKey($colKey);

            if ($column instanceof MySQLColumn) {
                $columnsWithVals[] = $colKey;
                $colsNamesArr[] = $column->getName();
                $type = $column->getDatatype();
                
                if (isset($valuesToInsert[$colKey])) {
                    $val = $valuesToInsert[$colKey];
                } else if (isset ($valuesToInsert[$valIndex])) {
                    $val = $valuesToInsert[$valIndex];
                } else {
                    $val = null;
                }

                if ($val !== null) {
                    $cleanedVal = $column->cleanValue($val);

                    if ($type == 'tinyblob' || $type == 'mediumblob' || $type == 'longblob') {
                        //chr(0) to remove null bytes in path.
                        $fixedPath = str_replace('\\', '/', str_replace(chr(0), '', $val));
                        set_error_handler(null);
                        $this->setIsBlobInsertOrUpdate(true);

                        if (strlen($fixedPath) != 0 && file_exists($fixedPath)) {
                            $file = fopen($fixedPath, 'r');
                            $data = '';

                            if ($file !== false) {
                                $fileContent = fread($file, filesize($fixedPath));

                                if ($fileContent !== false) {
                                    $data = '\''.addslashes($fileContent).'\'';
                                    $valsArr[] = $data;
                                } else {
                                    $valsArr[] = 'null';
                                }
                                fclose($file);
                            } else {
                                $data = '\''.addslashes($val).'\'';
                                $valsArr[] = $data;
                            }
                        } else {
                            $data = '\''.addslashes($cleanedVal).'\'';
                            $valsArr[] = $data;
                        }
                        restore_error_handler();
                    } else {
                        $valsArr[] = $cleanedVal;
                    }
                } else {
                    $valsArr[] = 'null';
                }
            } else {
                $tblName = $this->getTable()->getName();
                throw new DatabaseException("The table '$tblName' has no column with key '$colKey'.");
            }
            $valIndex++;
        }

        foreach ($this->getTable()->getColsKeys() as $key) {
            if (!in_array($key, $columnsWithVals)) {
                $colObj = $this->getTable()->getColByKey($key);
                $defaultVal = $colObj->getDefault();

                if ($defaultVal !== null) {
                    $colsNamesArr[] = $colObj->getName();
                    $type = $colObj->getDatatype();
                    if ($type == 'boolean' || $type == 'bool') {
                        $valsArr[] = $colObj->cleanValue($defaultVal);
                    } else if (($type == 'datetime' || $type == 'timestamp') && ($defaultVal == 'now()' || $defaultVal == 'current_timestamp')) {
                        $valsArr[] = "'".date('Y-m-d H:i:s')."'";
                    } else {
                        $valsArr[] = $colObj->cleanValue($defaultVal);
                    }
                }
            }
        }

        return [
            'cols' => implode(', ', $colsNamesArr),
            'vals' => implode(', ', $valsArr)
        ];
    }
}
