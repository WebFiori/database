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
    /**
     * Constructs a query which can be used to remove a record from the associated 
     * table.
     * 
     * @return MSSQLQuery The method will return the same instance at which the 
     * method is called on.
     * 
     * @since 1.0
     */
    public function delete() {
        $tblName = $this->getTable()->getName();
        $this->setQuery("delete from $tblName");
        
        return $this;
    }

    public function dropCol($colKey) {
        return $this;
    }

    public function dropPrimaryKey($pkName = null) {
        return $this;
    }

    /**
     * Constructs a query which can be used to add new record.
     * 
     * @param array $colsAndVals An associative array. The indices are columns 
     * keys and the value of each index is the value of the column. This also
     * can be one big indexed array of sub associative arrays. This approach can 
     * be used to build multiple insert queries.
     * 
     * @return MSSQLQuery The method will return the same instance at which the 
     * method is called on.
     * 
     * @since 1.0
     */
    public function insert(array $colsAndVals) {
        if (isset($colsAndVals['cols']) && isset($colsAndVals['values'])) {
            $colsArr = [];
            $tblName = $this->getTable()->getName();

            foreach ($colsAndVals['cols'] as $colKey) {
                $colObj = $this->getTable()->getColByKey($colKey);

                if (!($colObj instanceof MSSQLColumn)) {
                    throw new DatabaseException("The table $tblName has no column with key '$colKey'.");
                }
                $colObj->setWithTablePrefix(false);
                $colsArr[] = $colObj->getName();
            }
            $colsStr = '('.implode(', ', $colsArr).')';
            $suberValsArr = [];

            foreach ($colsAndVals['values'] as $valsArr) {
                $suberValsArr[] = $this->_insertHelper($colsAndVals['cols'], $valsArr);
            }
            $valsStr = implode(",\n", $suberValsArr);
            $this->setQuery("insert into $tblName\n$colsStr\nvalues\n$valsStr;");
        } else {
            $this->setQuery($this->_createInsertStm($colsAndVals));
        }

        return $this;
    }
    private function _createInsertStm($colsAndVals) {
        $tblName = $this->getTable()->getName();

        $colsArr = [];
        $valsArr = [];
        $columnsWithVals = [];

        foreach ($colsAndVals as $colIndex => $val) {
            $column = $this->getTable()->getColByKey($colIndex);

            if ($column instanceof MSSQLColumn) {
                $columnsWithVals[] = $colIndex;
                $colsArr[] = $column->getName();
                $type = $column->getDatatype();

                if ($val !== null) {
                    $cleanedVal = $column->cleanValue($val);

                    if ($type == 'binary') {
                        $fixedPath = str_replace('\\', '/', $val);
                        set_error_handler(function ()
                        {
                        });
                        $this->setIsBlobInsertOrUpdate(true);

                        if (file_exists($fixedPath)) {
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
                throw new DatabaseException("The table '$tblName' has no column with name '$colIndex'.");
            }
        }

        foreach ($this->getTable()->getColsKeys() as $key) {
            if (!in_array($key, $columnsWithVals)) {
                $colObj = $this->getTable()->getColByKey($key);
                $defaultVal = $colObj->getDefault();

                if ($defaultVal !== null) {
                    $colsArr[] = $colObj->getName();
                    $type = $colObj->getDatatype();

                    if (($type == 'datetime' || $type == 'timestamp') && ($defaultVal == 'now()' || $defaultVal == 'current_timestamp')) {
                        $valsArr[] = "'".date('Y-m-d H:i:s')."'";
                    } else {
                        $valsArr[] = $colObj->cleanValue($defaultVal);
                    }
                }
            }
        }
        $cols = '('.implode(', ', $colsArr).')';
        $vals = '('.implode(', ', $valsArr).')';
        
        return "insert into $tblName $cols values $vals;";
    }
    /**
     * Build a string that holds the values that will be inserted.
     * 
     * @param array $colsKeysArr
     * @param array $valuesToInsert
     * @return type
     * @throws DatabaseException
     */
    private function _insertHelper(array $colsKeysArr, array $valuesToInsert) {
        $valsArr = [];
        $columnsWithVals = [];
        $valIndex = 0;

        foreach ($colsKeysArr as $colKey) {
            $column = $this->getTable()->getColByKey($colKey);

            if ($column instanceof MSSQLColumn) {
                $columnsWithVals[] = $colKey;
                $type = $column->getDatatype();
                $val = isset($valuesToInsert[$valIndex]) ? $valuesToInsert[$valIndex] : null;

                if ($val !== null) {
                    $cleanedVal = $column->cleanValue($val);

                    if ($type == 'binary') {
                        $fixedPath = str_replace('\\', '/', $val);
                        set_error_handler(function ()
                        {
                        });
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
                    $type = $colObj->getDatatype();

                    if (($type == 'datetime') && ($defaultVal == 'now' || $defaultVal == 'current_timestamp')) {
                        $valsArr[] = "'".date('Y-m-d H:i:s')."'";
                    } else {
                        $valsArr[] = $colObj->cleanValue($defaultVal);
                    }
                }
            }
        }

        return '('.implode(', ', $valsArr).')';
    }
    public function modifyCol($colKey, $location = null) {
        return $this;
    }

    public function renameCol($colKey) {
        return $this;
    }

    /**
     * Constructs an update query.
     * 
     * @param array $newColsVals An associative array. The indices of the array 
     * are columns keys and the values are the new values for the columns.
     * 
     * @return MSSQLQuery The method will return the same instance at which the 
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

            if (!$colObj instanceof MSSQLColumn) {
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

                if (($colObj->getDatatype() == 'datetime2') && $colObj->isAutoUpdate()) {
                    $updateArr[] = $colObj->getName()." = ".$colObj->cleanValue(date('Y-m-d H:i:s'));
                }
            }
        }
        $query = "update $tblName set ".implode(', ', $updateArr);
        $this->setQuery($query);

        return $this;
    }

}
