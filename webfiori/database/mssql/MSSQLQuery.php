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
namespace webfiori\database\mssql;

use webfiori\database\AbstractQuery;
use webfiori\database\Column;
use webfiori\database\DatabaseException;
/**
 * A class which is used to build MSSQL queries.
 *
 * @author Ibrahim
 * 
 * @version 1.0
 */
class MSSQLQuery extends AbstractQuery {
    private $bindings;
    public function __construct() {
        parent::__construct();
        $this->bindings = [];
    }
    /**
     * Build a query which can be used to add a column to associated table.
     * 
     * @param string $colKey The key of the column taken from the table.
     * 
     * @param string $location [NOT USED]
     * 
     * @throws DatabaseException If no column which has the given key, the method 
     * will throw an exception.
     * 
     * @return MSSQLQuery The method will return the same instance at which the 
     * method is called on.
     * 
     * @since 1.0
     */
    public function addCol(string $colKey, ?string $location = null) {
        $tblName = $this->getTable()->getName();
        $colToAdd = $this->getTable()->getColByKey($colKey);

        if (!($colToAdd instanceof Column)) {
            throw new DatabaseException("The table '$tblName' has no column with key '$colKey'.");
        } 
        $this->setQuery('alter table '.$tblName.' add '.$colToAdd->asString());

        return $this;
    }
    /**
     * Constructs a query that can be used to add a primary key to the active table.
     * 
     * @return MSSQLQuery The method will return the same instance at which the 
     * method is called on.
     * 
     * @since 1.0
     */
    public function addPrimaryKey(string $pkName, array $pkCols) {
        $tableObj = $this->getTable();
        $trimmedPkName = trim($pkName);
        $keyCols = [];

        foreach ($pkCols as $colKey) {
            $col = $tableObj->getColByKey($colKey);

            if ($col instanceof MSSQLColumn) {
                $keyCols[] = $col->getName();
            }
        }
        $stm = 'alter table '.$tableObj->getName().' add constraint '.$trimmedPkName.' '
                .'primary key clustered ('.implode(', ', $keyCols).');';
        $this->setQuery($stm);

        return $this;
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
    public function copyQuery() : AbstractQuery {
        $copy = new MSSQLQuery();
        $copy->setTable($this->getTable(), false);
        $copy->setSchema($this->getSchema());

        return $copy;
    }
    /**
     * Build a query which is used to drop primary key of linked table.
     * 
     * @param string $pkName The name of the primary key.
     * 
     * @return MSSQLQuery The method will return the same instance at which the 
     * method is called on.
     * 
     * @since 1.0
     */
    public function dropPrimaryKey(?string $pkName = null) {
        $tableName = $this->getTable()->getName();
        $query = 'alter table '.$tableName.' drop constraint '.$pkName.'';
        $this->setQuery($query);

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
            $query .= ' offset '.($this->getOffset() == -1 ? 0 : $this->getOffset()).' rows';

            $query .= ' fetch next '.$this->getLimit().' rows only';
        }

        return $query;
    }

    public function insert(array $colsAndVals): AbstractQuery {
        $this->setInsertBuilder(new MSSQLInsertBuilder($this->getTable(), $colsAndVals));

        return $this;
    }
    /**
     * Build a query which can be used to modify a column in associated table.
     * 
     * @param string $colKey The key of the column taken from the table.
     * 
     * @param string $location [NOT USED]
     * 
     * @throws DatabaseException If no column which has the given key, the method 
     * will throw an exception.
     * 
     * @return MSSQLQuery The method will return the same instance at which the 
     * method is called on.
     * 
     * @since 1.0
     */
    public function modifyCol($colKey, ?string $location = null) {
        $tblName = $this->getTable()->getName();
        $colObj = $this->getTable()->getColByKey($colKey);

        if (!($colObj instanceof MSSQLColumn)) {
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
     * @return MSSQLQuery The method will return the same instance at which the 
     * method is called on.
     * 
     * @throws DatabaseException The method will throw an exception if 
     * the table has no column with given key or the name of the 
     * specified column was not changed.
     * 
     * @since 1.0
     */
    public function renameCol($colKey) {
        $colObj = $this->getTable()->getColByKey($colKey);
        $tblName = $this->getTable()->getNormalName();

        if (!$colObj instanceof Column) {
            throw new DatabaseException("The table $tblName has no column with key '$colKey'.");
        }

        if ($colObj->getOldName() == null) {
            throw new DatabaseException('Cannot build the query. Old column name is null.');
        }

        $oldName = $colObj->getOldName();
        $newName = $colObj->getNormalName();

        $this->setQuery("exec sp_rename '".$tblName.".".$oldName."', '".$newName."', 'COLUMN'");

        return $this;
    }
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
        $trimmed = trim($str.'');

        if (strlen($trimmed) != 0) {
            $exp = explode('.', $trimmed);

            $arr = [];

            foreach ($exp as $xStr) {
                $arr[] = '['.trim(trim($xStr, '['),']').']';
            }

            return implode('.', $arr);
        }

        return '';
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

            if ($colObj === null) {
                $this->getTable()->addColumns([
                    $colKey => []
                ]);
                $colObj = $this->getTable()->getColByKey($colKey);
            }
            $colName = $colObj->getName();

            if ($newVal === null) {
                $updateArr[] = "$colName = null";
            } else {
                $updateArr[] = "$colName = ?";
                $this->addBinding($colObj, $newVal);
            }
            $colsWithVals[] = $colKey;
        }

        foreach ($this->getTable()->getColsKeys() as $key) {
            if (!in_array($key, $colsWithVals)) {
                $colObj = $this->getTable()->getColByKey($key);

                if (($colObj->getDatatype() == 'datetime2') && $colObj->isAutoUpdate()) {
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
     * Build a string that holds the values that will be inserted.
     * 
     * @param array $colsKeysArr
     * @param array $valuesToInsert
     * @return type
     * @throws DatabaseException
     */
    private function insertHelper(array $colsKeysArr, array $valuesToInsert) {
        $valsArr = [];
        $columnsWithVals = [];
        $colsNamesArr = [];
        $valIndex = 0;

        foreach ($colsKeysArr as $colKey) {
            $column = $this->getTable()->getColByKey($colKey);

            if ($column === null) {
                $this->getTable()->addColumns([
                    $colKey => []
                ]);
                $column = $this->getTable()->getColByKey($colKey);
            }


            $columnsWithVals[] = $colKey;
            $colsNamesArr[] = $column->getName();
            $type = $column->getDatatype();

            if (isset($valuesToInsert[$colKey])) {
                $val = $valuesToInsert[$colKey];
            } else {
                if (isset($valuesToInsert[$valIndex])) {
                    $val = $valuesToInsert[$valIndex];
                } else {
                    $val = null;
                }
            }

            if ($val !== null) {
                $cleanedVal = $column->cleanValue($val);

                if ($type == 'binary' || $type == 'varbinary') {
                    //chr(0) to remove null bytes in path.
                    $fixedPath = str_replace('\\', '/', str_replace(chr(0), '', $val));
                    set_error_handler(function (int $no, string $message)
                    {
                        throw new DatabaseException($message, $no);
                    });

                    if (strlen($fixedPath) != 0 && file_exists($fixedPath)) {
                        $file = fopen($fixedPath, 'r');
                        $data = '';

                        if ($file !== false) {
                            $fileContent = fread($file, filesize($fixedPath));

                            if ($fileContent !== false) {
                                $data = '0x'.bin2hex($fileContent);
                                $valsArr[] = $data;
                            } else {
                                $valsArr[] = 'null';
                            }
                            fclose($file);
                        } else {
                            $data = '0x'.bin2hex($val);
                            $valsArr[] = $data;
                        }
                    } else {
                        $data = '0x'.bin2hex($cleanedVal).'';
                        $valsArr[] = $data;
                    }
                    restore_error_handler();
                } else {
                    $valsArr[] = $cleanedVal;
                }
            } else {
                $valsArr[] = 'null';
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

                    if (in_array($type, Column::BOOL_TYPES)) {
                        $valsArr[] = $colObj->cleanValue($defaultVal);
                    } else if ($defaultVal == 'now' || $defaultVal == 'current_timestamp' || $defaultVal == 'now()') {
                        if ($type == 'datetime2') {
                            $valsArr[] = "'".date('Y-m-d H:i:s')."'";
                        } else if ($type == 'time') {
                            $valsArr[] = "'".date('H:i:s')."'";
                        } else if ($type == 'date') {
                            $valsArr[] = "'".date('Y-m-d')."'";
                        }
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

    public function addBinding(Column $col, $value) {
        $this->bindings[] = $value;
    }

    public function getBindings(): array {
        return $this->bindings;
    }

    public function resetBinding() {
        $this->bindings = [];
    }

    public function setBindings(array $binding, string $merge = 'none') {
        
        if ($merge == 'first') {
            $this->bindings = array_merge($binding, $this->bindings);
        } else if ($merge == 'end') {
            $this->bindings = array_merge($this->bindings, $binding);
        } else {
            $this->bindings = $binding;
        }
    }
}
