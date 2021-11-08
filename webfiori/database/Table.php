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
namespace webfiori\database;

use webfiori\database\mssql\MSSQLColumn;

/**
 * A class that can be used to represents database tables.
 *
 * @author Ibrahim
 * 
 * @since 1.0.2
 */
abstract class Table {
    /**
     *
     * @var array
     * 
     * @since 1.0
     */
    private $colsArr;
    /**
     *
     * @var string|null
     * 
     * @since 1.0
     */
    private $comment;
    /**
     * An array that contains all table foreign keys.
     * 
     * @var array 
     * 
     * @since 1.0
     */
    private $foreignKeys = [];
    /**
     *
     * @var EntityMapper
     * 
     * @since 1.0 
     */
    private $mapper;
    /**
     *
     * @var string
     * 
     * @since 1.0
     */
    private $name;
    /**
     *
     * @var string|null 
     * 
     * @since 1.0.1 
     */
    private $oldName;
    /**
     *
     * @var Database|null
     * 
     * @since 1.0
     */
    private $ownerSchema;
    /**
     *
     * @var type
     * 
     * @since 1.0
     */
    private $selectExpr;
    /**
     *
     * @var boolean
     * 
     * @since 1.0
     */
    private $withDbPrefix;
    /**
     * Creates a new instance of the class.
     * 
     * @param string $name The name of the table. If empty string is given, 
     * the value 'new_table' will be used as default.
     * 
     * @since 1.0
     */
    public function __construct($name = 'new_table') {
        if (!$this->setName($name)) {
            $this->name = 'new_table';
        }
        $this->colsArr = [];
    }
    /**
     * 
     * @param string $key
     * @param Column $colObj
     * @return boolean
     */
    public function addColumn($key, Column $colObj) {
        $trimmidKey = trim($key);
        $colName = $colObj->getNormalName();

        if (!$this->hasColumn($colName) && !$this->hasColumnWithKey($trimmidKey) && $this->_isKeyNameValid($trimmidKey)) {
            $colObj->setOwner($this);
            $this->colsArr[$trimmidKey] = $colObj;

            return true;
        }

        return false;
    }
    /**
     * Adds a set of columns as one patch.
     * 
     * @param array $cols An array that holds the columns as an associative array. 
     * The indices should represent columns keys.
     * 
     * @since 1.0
     */
    public function addColumns(array $cols) {
        foreach ($cols as $colKey => $colObj) {
            $this->addColumn($colKey, $colObj);
        }
    }
    /**
     * Adds a foreign key to the table.
     * 
     * @param Table|AbstractQuery|string $refTable The referenced table. It is the table that 
     * will contain original values. This value can be an object of type 
     * 'Table', an object of type 'AbstractQuery' or the namespace of a class which is a sub-class of 
     * the class 'AbstractQuery'.
     * 
     * @param array $cols An associative array that contains key columns. 
     * The indices must be names of columns which exist in 'this' table and 
     * the values must be columns from referenced table. It is possible to 
     * provide an indexed array. If an indexed array is given, the method will 
     * assume that the two tables have same column key. 
     * 
     * @param string $keyname The name of the key.
     * 
     * @param string $onupdate The 'on update' condition for the key. it can be one 
     * of the following: 
     * <ul>
     * <li>set null</li>
     * <li>cascade</li>
     * <li>restrict</li>
     * <li>set default</li>
     * <li>no action</li>
     * </ul>
     * Default value is 'set null'.
     * 
     * @param string $ondelete The 'on delete' condition for the key. it can be one 
     * of the following: 
     * <ul>
     * <li>set null</li>
     * <li>cascade</li>
     * <li>restrict</li>
     * <li>set default</li>
     * <li>no action</li>
     * </ul>
     * Default value is 'set null'.
     * 
     * 
     * @since 1.0
     */
    public function addReference($refTable, array $cols, $keyname, $onupdate = 'set null', $ondelete = 'set null') {
        if (!($refTable instanceof Table)) {
            if ($refTable instanceof AbstractQuery) {
                $refTable = $refTable->getTable();
            } else if (class_exists($refTable)) {
                $q = new $refTable();

                if ($q instanceof AbstractQuery) {
                    $refTable = $q->getTable();
                }
            }
        }

        $this->_createFk($refTable, $cols, $keyname, $onupdate, $ondelete);
    }
    /**
     * Returns a column given its index.
     * 
     * @param int $index The index of the column.
     * 
     * @return Column|null If a column was found which has the specified index, 
     * it is returned. Other than that, The method will return null.
     * 
     * @since 1.0
     */
    public function getColByIndex($index) {
        foreach ($this->colsArr as $col) {
            $colIndex = $col->getIndex();

            if ($colIndex == $index) {
                return $col;
            }
        }

        return null;
    }
    /**
     * Returns a column given its key name.
     * 
     * @param string $key The name of column key.
     * 
     * @return Column|null If a column which has the given key exist on the table, 
     * the method will return it as an object. Other than that, the method will return 
     * null.
     * 
     * @since 1.0
     */
    public function getColByKey($key) {
        $trimmed = trim($key);

        if (isset($this->colsArr[$trimmed])) {
            return $this->colsArr[$trimmed];
        }
    }
    /**
     * Returns a column given its actual name.
     * 
     * @param string $key The name of column as it appears in the database.
     * 
     * @return Column|null If a column which has the given name exist on the table, 
     * the method will return it as an object. Other than that, the method will return 
     * null.
     * 
     * @since 1.0
     */
    public function getColByName($name) {
        $trimmed = trim($name);

        foreach ($this->getCols() as $colObj) {
            if ($colObj->getName() == $trimmed) {
                return $colObj;
            }
        }
    }
    /**
     * Returns an associative array that holds all table columns.
     * 
     * @return array An associative array. The indices of the array are column 
     * keys and the values are objects of type 'Column'.
     * 
     * @since 1.0
     */
    public function getCols() {
        return $this->colsArr;
    }
    /**
     * Returns the number of columns which are in the table.
     * 
     * @return int The number of columns in the table.
     * 
     * @since 1.0
     */
    public function getColsCount() {
        return count($this->colsArr);
    }
    /**
     * Returns an array that contains data types of table columns.
     * 
     * @return array An indexed array that contains columns data types. Each 
     * index will corresponds to the index of the column in the table.
     * 
     * @since 1.0
     */
    public function getColsDatatypes() {
        $retVal = [];

        foreach ($this->getCols() as $colObj) {
            $retVal[] = $colObj->getDatatype();
        }

        return $retVal;
    }
    /**
     * Returns an indexed array that contains the names of columns keys.
     * 
     * @return array An indexed array that contains the names of columns keys.
     * 
     * @since 1.0
     */
    public function getColsKeys() {
        return array_keys($this->colsArr);
    }
    /**
     * Returns an array that contains all columns names as they will appear in 
     * the database.
     * 
     * @return array An array that contains all columns names as they will appear in 
     * the database.
     * 
     * @since 1.0
     */
    public function getColsNames() {
        $columns = $this->getCols();
        $retVal = [];

        foreach ($columns as $colObj) {
            $retVal[] = $colObj->getName();
        }

        return $retVal;
    }
    /**
     * Returns a string that represents a comment which was added with the table.
     * 
     * @return string|null Comment text. If it is not set, the method will return 
     * null.
     * 
     * @since 1.0
     */
    public function getComment() {
        return $this->comment;
    }
    /**
     * Returns an instance of the class 'EntityMapper' which can be used to map the 
     * table to an entity class.
     * 
     * Note that the developer can modify the name of the entity and the namespace 
     * that it belongs to in addition to the path that the class will be created on.
     * 
     * @return EntityMapper An instance of the class 'EntityMapper'
     * 
     * @since 1.0
     */
    public function getEntityMapper() {
        if ($this->mapper === null) {
            $this->mapper = new EntityMapper($this, 'C');
        }

        return $this->mapper;
    }
    /**
     * Returns a foreign key given its name.
     * 
     * @param string $keyName The name of the foreign key as specified when it 
     * was added to the table.
     * 
     * @return ForeignKey|null If a key with the given name exist, the method 
     * will return an object that represent it. Other than that, the method will 
     * return null.
     * 
     * @since 1.0.1
     */
    public function getForeignKey($keyName) {
        foreach ($this->getForignKeys() as $keyObj) {
            if ($keyObj->getKeyName() == $keyName) {
                return $keyObj;
            }
        }
    }
    /**
     * Returns an array that contains all table foreign keys.
     * 
     * @return array An array of FKs.
     * 
     * @since 1.0
     */
    public function getForignKeys() {
        return $this->foreignKeys;
    }
    /**
     * Returns the number of foreign keys added to the table.
     * 
     * @return int an integer that represents the count of FKs.
     * 
     * @since 1.0
     */
    public function getForignKeysCount() {
        return count($this->foreignKeys);
    }
    /**
     * Returns the name of the table.
     * 
     * @return string The name of the table. Default return value is 'new_table'.
     * 
     * @since 1.0
     */
    public function getName() {
        $owner = $this->getOwner();

        if ($owner !== null && $this->isNameWithDbPrefix()) {
            return $owner->getName().'.'.$this->name;
        }

        return $this->name;
    }
    /**
     * Returns the name of the table.
     * 
     * @return string The name of the table. Default return value is 'new_table'.
     * 
     * @since 1.0
     */
    public final function getNormalName() {
        $owner = $this->getOwner();

        if ($owner !== null && $this->isNameWithDbPrefix()) {
            return $owner->getName().'.'.$this->name;
        }

        return $this->name;
    }
    /**
     * Returns the old name of the column.
     * 
     * Note that the old name will be set only if the method 
     * Table::setName() is called more than once in the same instance.
     * 
     * @return string|null The method will return a string that represents the 
     * old name if it is set. Null if not.
     * 
     * @since 1.0.1
     */
    public function getOldName() {
        return $this->oldName;
    }
    /**
     * Returns the database which owns the table.
     * 
     * @return null|Database If the owner is set, the method will return it as an 
     * object. If not set, the method will return null.
     * 
     * @since 1.0
     */
    public function getOwner() {
        return $this->ownerSchema;
    }
    /**
     * Returns the number of columns that will act as one primary key.
     * 
     * @return int The number of columns that will act as one primary key. If 
     * the table has no primary key, the method will return 0. If one column 
     * is used as primary, the method will return 1. If two, the method 
     * will return 2 and so on.
     * 
     * @since 1.0
     */
    public function getPrimaryKeyColsCount() {
        $count = 0;

        foreach ($this->getCols() as $col) {
            if ($col->isPrimary()) {
                $count++;
            }
        }

        return $count;
    }
    /**
     * Returns an array that contains the keys of the columns which are primary.
     * 
     * @return array An array that contains the keys of the columns which are primary.
     * 
     * @since 1.0
     */
    public function getPrimaryKeyColsKeys() {
        $arr = [];

        foreach ($this->getCols() as $colkey => $col) {
            if ($col->isPrimary()) {
                $arr[] = $colkey;
            }
        }

        return $arr;
    }
    /**
     * Returns the name of table primary key.
     * 
     * @return string The returned value will be the name of the table added 
     * to it the suffix '_pk'.
     * 
     * @since 1.0
     */
    public function getPrimaryKeyName() {
        $val = $this->isNameWithDbPrefix();
        $this->setWithDbPrefix(false);
        $keyName = $this->getNormalName();
        $this->setWithDbPrefix($val);

        return $keyName.'_pk';
    }
    /**
     * 
     * @return SelectExpression
     */
    public function getSelect() {
        if ($this->selectExpr === null) {
            $this->selectExpr = new SelectExpression($this);
        }

        return $this->selectExpr;
    }
    /**
     * Returns an array that holds all the columns which are set to be unique.
     * 
     * @return array An array that holds objects of type 'MSSQLColumn'.
     * 
     * @since 1.0.2
     */
    public function getUniqueCols() {
        $retVal = [];

        foreach ($this->getCols() as $colObj) {
            if ($colObj->isUnique()) {
                $retVal[] = $colObj;
            }
        }

        return $retVal;
    }
    /**
     * Checks if the table has a column which has specific name.
     * 
     * @param string $colName The name of the column as it appears in database.
     * 
     * @return boolean If the table has such column, the method will return true. 
     * other than that, the method will return false.
     */
    public function hasColumn($colName) {
        $normalColName = '';

        foreach ($this->colsArr as $colObj) {
            $normalColName = $colObj->getNormalName();

            if ($normalColName == $colName) {
                return true;
            }
        }

        return false;
    }
    /**
     * Checks if the table has a column with a given key.
     * 
     * @param string $keyName The name of the key.
     * 
     * @return boolean If a column with the given key exist, the method will return 
     * true. Other than that, the method will return false.
     * 
     * @since 1.0
     */
    public function hasColumnWithKey($keyName) {
        $trimmed = trim($keyName);

        return isset($this->colsArr[$trimmed]);
    }
    /**
     * Checks if table name will be prefixed with database name or not.
     * 
     * @return boolean True if it will be prefixed. False if not.
     * 
     * @since 1.0
     */
    public function isNameWithDbPrefix() {
        return $this->withDbPrefix;
    }
    /**
     * Removes a column from the table given its key.
     * 
     * @param string $colKey Key name of the column.
     * 
     * @return Column|null If the column is removed, an object that represent it 
     * is returned. Other than that, the method will return null.
     * 
     * @since 1.0
     */
    public function removeColByKey($colKey) {
        $colObj = $this->getColByKey($colKey);

        if ($colObj !== null) {
            unset($this->colsArr[trim($colKey)]);
            $colObj->setOwner(null);
        }

        return $colObj;
    }
    /**
     * Removes a foreign key given its name.
     * 
     * @param string $keyName The name of the foreign key.
     * 
     * @return ForeignKey|null If the key was removed, the method will return the 
     * removed key as an object. If nothing changed, the method will return null.
     * 
     * @since 1.0
     */
    public function removeReference($keyName) {
        $trimmed = trim($keyName);
        $newKeysArr = [];
        $removedKeyObj = null;

        foreach ($this->foreignKeys as $key) {
            if (!($key->getKeyName() == $trimmed)) {
                $newKeysArr[] = $key;
            } else {
                $removedKeyObj = $key;
            }
        }
        $this->foreignKeys = $newKeysArr;

        return $removedKeyObj;
    }
    /**
     * Sets a comment which will appear with the table.
     * 
     * @param string|null $comment Comment text. It must be non-empty string 
     * in order to set. If null is passed, the comment will be removed.
     * 
     * @since 1.0
     */
    public function setComment($comment) {
        if ($comment == null || strlen($comment) != 0) {
            $this->comment = $comment;
        }
    }
    /**
     * Sets the name of the table.
     * 
     * @param string $name The name of the table. Must be non-empty string in order 
     * to set.
     * 
     * @return boolean If the name is set, the method will return true. Other than 
     * that, the method will return false.
     * 
     * @since 1.0
     */
    public function setName($name) {
        $trimmed = trim($name);

        if (strlen($trimmed) > 0) {
            $this->oldName = $this->getName();
            $this->name = $trimmed;

            return true;
        }

        return false;
    }
    /**
     * Sets or removes the database which owns the table.
     * 
     * @param Database|null $db The owner database. If null is passed, the owner 
     * will be unset.
     * 
     * @since 1.0
     */
    public function setOwner($db) {
        if ($db instanceof Database) {
            $this->ownerSchema = $db;
        } else if ($db === null) {
            $this->ownerSchema = null;
        }
    }
    /**
     * Sets the value of the attributes which determine if table name will be 
     * prefixed with database name or not.
     * 
     * Note that table name will be prefixed with database name only if owner 
     * schema is set.
     * 
     * @param boolean $withDbPrefix True to prefix table name with database name. 
     * false to not prefix table name with database name.
     * 
     * @since 1.0
     */
    public function setWithDbPrefix($withDbPrefix) {
        $this->withDbPrefix = $withDbPrefix === true;
    }
    public abstract function toSQL();
    private function _createFk($refTable, $cols, $keyname, $onupdate, $ondelete) {
        if ($refTable instanceof Table) {
            $fk = new ForeignKey();
            $fk->setOwner($this);
            $fk->setSource($refTable);

            if ($fk->setKeyName($keyname) === true) {
                foreach ($cols as $target => $source) {
                    if (gettype($target) == 'integer') {
                        //indexed array. 
                        //It means source and target columns have same name.
                        $fk->addReference($source, $source);
                    } else {
                        //Associative. Probably two columns with different names.
                        $fk->addReference($target, $source);
                    }
                }

                if (count($fk->getSourceCols()) != 0) {
                    $fk->setOnUpdate($onupdate);
                    $fk->setOnDelete($ondelete);
                    $this->foreignKeys[] = $fk;

                    return true;
                }
            } else {
                throw new DatabaseException('Invalid FK name: \''.$keyname.'\'.');
            }
        } else {
            throw new DatabaseException('Referenced table is not an instance of the class \'Table\'.');
        }
    }
    /**
     * 
     * @param type $key
     * @return boolean
     * @since 1.6.1
     */
    private function _isKeyNameValid($key) {
        $keyLen = strlen($key);

        if ($keyLen == 0) {
            return false;
        }
        $actualKeyLen = $keyLen;

        for ($x = 0 ; $x < $keyLen ; $x++) {
            $ch = $key[$x];

            if ($ch == '-' || ($ch >= 'a' && $ch <= 'z') || ($ch >= 'A' && $ch <= 'Z') || ($ch >= '0' && $ch <= '9')) {
                if ($ch == '-') {
                    $actualKeyLen--;
                }
            } else {
                return false;
            }
        }

        return $actualKeyLen != 0;
    }
}
