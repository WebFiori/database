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
namespace WebFiori\Database;

use WebFiori\Database\MsSql\MSSQLTable;
use WebFiori\Database\MySql\MySQLTable;

/**
 * A class that can be used to represent database tables.
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
     * @var SelectExpression
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
    public function __construct(string $name = 'new_table') {
        $this->name = '';
        $this->setWithDbPrefix(false);

        if (!$this->setName($name)) {
            $this->name = 'new_table';
        }
        $this->colsArr = [];
    }
    /**
     * Adds new column to the table.
     * 
     * @param string $key Key name of the column. A valid key must follow following
     * conditions: Contains letters A-Z, a-z, numbers 0-9 and a dash only.
     * Note that if key contains underscores they will be replaced by dashes.
     * 
     * @param Column $colObj An object that holds the information of the column.
     * 
     * @return bool If added, the method will return true. False otherwise.
     */
    public function addColumn(string $key, Column $colObj) : bool {
        $fixedKey = str_replace('_', '-', trim($key));
        $colName = $colObj->getNormalName();

        if (!$this->hasColumn($colName) && !$this->hasColumnWithKey($fixedKey) && $this->isKeyNameValid($fixedKey)) {
            $colObj->setOwner($this);
            $this->colsArr[$fixedKey] = $colObj;

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
     * @return Table The method will return the instance at which the method
     * is called on.
     * 
     * @since 1.0
     */
    public function addColumns(array $cols) : Table {
        foreach ($cols as $colKey => $colObj) {
            $this->addColumn($colKey, $colObj);
        }

        return $this;
    }
    /**
     * Adds a single-column foreign key to the table.
     * 
     * @param string $colName The name of the column that will reference the other table.
     * 
     * @param array $keyProps An array that will hold key properties. The array should
     * have following indices: 
     * <ul>
     * <li><b>table</b>: It is the table that
     * will contain original values. This value can be an object of type
     * 'Table', an object of type 'AbstractQuery' or the namespace of a class which is a subclass of
     * the class 'AbstractQuery' or the class 'Table'.</li>
     * <li><b>col</b>: The name of the column that will be referenced.</li>
     * <li><b>name</b>: The name of the key.</li>
     * <li><b>on-update</b> [Optional] The 'on update' condition for the key.
     * Default value is 'set null'.</li>
     * <li><b>on-delete</b> [Optional] The 'on delete' condition for the key.
     * Default value is 'set null'.</li>
     * @return Table The method will return the instance at which the method
     * is called on.
     * 
     * @throws DatabaseException
     */
    public function addReferenceFromArray(string $colName, array $keyProps) : Table {
        if (!isset($keyProps[ColOption::FK_TABLE])) {
            return $this;
        }
        $table = $this->getRefTable($keyProps[ColOption::FK_TABLE]);
        $keyName = $keyProps[ColOption::FK_NAME] ?? '';
        $col = $keyProps[ColOption::FK_COL] ?? '';
        $onUpdate = $keyProps[ColOption::FK_ON_UPDATE] ?? FK::SET_NULL;
        $onDelete = $keyProps[ColOption::FK_ON_DELETE] ?? FK::SET_NULL;
        
        return $this->addReference($table, [$colName => $col], $keyName, $onUpdate, $onDelete);
    }
    /**
     * Adds a foreign key to the table.
     *
     * @param Table|AbstractQuery|string $refTable The referenced table. It is the table that
     * will contain original values. This value can be an object of type
     * 'Table', an object of type 'AbstractQuery' or the namespace of a class which is a subclass of
     * the class 'AbstractQuery'.
     *
     * @param array $cols An associative array that contains key columns.
     * The indices must be names of columns which exist in 'this' table and
     * the values must be columns from referenced table. It is possible to
     * provide an indexed array. If an indexed array is given, the method will
     * assume that the two tables have same column key.
     *
     * @param string $keyName The name of the key.
     *
     * @param string $onUpdate The 'on update' condition for the key. it can be one
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
     * @param string $onDelete The 'on delete' condition for the key. it can be one
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
     * @return Table The method will return the instance at which the method
     * is called on.
     * 
     * @throws DatabaseException
     * @since 1.0
     */
    public function addReference($refTable, array $cols, string $keyName, string $onUpdate = FK::SET_NULL, string $onDelete = FK::SET_NULL) : Table {
        
        $this->createFk($this->getRefTable($refTable), $cols, $keyName, $onUpdate, $onDelete);

        return $this;
    }
    private function getRefTable($refTable) {
        if (!($refTable instanceof Table)) {
            if ($refTable instanceof AbstractQuery) {
                return $refTable->getTable();
            } else if (class_exists($refTable)) {
                $q = new $refTable();

                if ($q instanceof AbstractQuery) {
                    return $q->getTable();
                } else if ($q instanceof Table) {
                    return $q;
                }
            } else {
                $owner = $this->getOwner();

                if ($owner !== null) {
                    return $owner->getTable($refTable);
                }
            }
        }
        return $refTable;
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
    public function getColByIndex(int $index) {
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
    public function getColByKey(string $key) {
        $trimmed = trim(str_replace('_', '-', $key));

        if (isset($this->colsArr[$trimmed])) {
            return $this->colsArr[$trimmed];
        }

        return null;
    }
    /**
     * Returns a column given its actual name.
     * 
     * @param string $name The name of column as it appears in the database.
     * 
     * @return Column|null If a column which has the given name exist on the table, 
     * the method will return it as an object. Other than that, the method will return 
     * null.
     * 
     * @since 1.0
     */
    public function getColByName(string $name) {
        $trimmed = trim($name);

        foreach ($this->getCols() as $colObj) {
            if ($colObj->getNormalName() == $trimmed) {
                return $colObj;
            }
        }

        return null;
    }
    /**
     * Returns an associative array that holds all table columns.
     * 
     * @return array An associative array. The indices of the array are column 
     * keys and the values are objects of type 'Column'.
     * 
     * @since 1.0
     */
    public function getCols() : array {
        return $this->colsArr;
    }
    /**
     * Returns the number of columns which are in the table.
     * 
     * @return int The number of columns in the table.
     * 
     * @since 1.0
     */
    public function getColsCount() : int {
        return count($this->colsArr);
    }
    /**
     * Returns an array that contains data types of table columns.
     * 
     * @return array An associative array that contains columns data types. Each 
     * index will be column key.
     * 
     * @since 1.0
     */
    public function getColsDataTypes() : array {
        $retVal = [];

        foreach ($this->getCols() as $idx => $colObj) {
            $retVal[$idx] = $colObj->getDatatype();
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
    public function getColsKeys() : array {
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
    public function getColsNames() : array {
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
    public function getEntityMapper() : EntityMapper {
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
     * @return FK|null If a key with the given name exist, the method 
     * will return an object that represent it. Other than that, the method will 
     * return null.
     * 
     * @since 1.0.1
     */
    public function getForeignKey(string $keyName) {
        foreach ($this->getForeignKeys() as $keyObj) {
            if ($keyObj->getKeyName() == $keyName) {
                return $keyObj;
            }
        }

        return null;
    }
    /**
     * Returns an array that contains all table foreign keys.
     * 
     * @return array An array of FKs.
     * 
     * @since 1.0
     */
    public function getForeignKeys() : array {
        return $this->foreignKeys;
    }
    /**
     * Returns the number of foreign keys added to the table.
     * 
     * @return int an integer that represents the count of FKs.
     * 
     * @since 1.0
     */
    public function getForeignKeysCount() : int {
        return count($this->foreignKeys);
    }
    /**
     * Returns the name of the table.
     *
     * @return string The name of the table. Default return value is 'new_table'.
     * 
     * @since 1.0
     */
    public function getName() : string {
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
    public final function getNormalName() : string {
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
    public function getPrimaryKeyColsCount() : int {
        return count($this->getPrimaryKeyColsKeys());
    }
    /**
     * Returns an array that contains the keys of the columns which are primary.
     * 
     * @return array An array that contains the keys of the columns which are primary.
     * 
     * @since 1.0
     */
    public function getPrimaryKeyColsKeys() : array {
        return $this->getColsKeysHelper('isPrimary');
    }

    /**
     * Returns the name of table primary key.
     * 
     * @return string The returned value will be the name of the table added 
     * to it the suffix '_pk'.
     * 
     * @since 1.0
     */
    public function getPrimaryKeyName() : string {
        $val = $this->isNameWithDbPrefix();
        $this->setWithDbPrefix(false);
        $keyName = $this->getNormalName();
        $this->setWithDbPrefix($val);

        return $keyName.'_pk';
    }
    /**
     * Returns select statement which was associated with the table.
     * 
     * @return SelectExpression
     */
    public function getSelect() : SelectExpression {
        if ($this->selectExpr === null) {
            $this->selectExpr = new SelectExpression($this);
        }

        return $this->selectExpr;
    }
    /**
     * Returns an array that holds all the columns which are set to be unique.
     * 
     * @return array An array that holds objects of type 'Column'.
     * 
     * @since 1.0.2
     */
    public function getUniqueCols() : array {
        return $this->getColsHelper('isUnique');
    }
    /**
     * Returns the number of columns that are marked as unique.
     * 
     * @return int The number of columns that are marked as unique. If 
     * the table has no unique columns, the method will return 0.
     * 
     */
    public function getUniqueColsCount() : int {
        return count($this->getUniqueColsKeys());
    }
    /**
     * Returns an array that contains the keys of the columns which are unique.
     * 
     * @return array An array that contains the keys of the columns which are unique.
     * 
     */
    public function getUniqueColsKeys() : array {
        return $this->getColsKeysHelper('isUnique');
    }
    /**
     * Checks if the table has a column which has specific name.
     * 
     * @param string $colName The name of the column as it appears in database.
     * 
     * @return bool If the table has such column, the method will return true. 
     * other than that, the method will return false.
     */
    public function hasColumn(string $colName) : bool {
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
     * @return bool If a column with the given key exist, the method will return 
     * true. Other than that, the method will return false.
     * 
     * @since 1.0
     */
    public function hasColumnWithKey(string $keyName) : bool {
        $trimmed = trim($keyName);

        return isset($this->colsArr[$trimmed]);
    }
    /**
     * Checks if table name will be prefixed with database name or not.
     * 
     * @return bool True if it will be prefixed. False if not.
     * 
     * @since 1.0
     */
    public function isNameWithDbPrefix() : bool {
        return $this->withDbPrefix;
    }
    /**
     * Maps a table instance to another DBMS.
     * 
     * @param string $to The name of the DBMS at which the table will be mapped
     * to.
     * 
     * @param Table $table The instance that will be mapped.
     * 
     * @return Table The method will return new instance which will be
     * compatible with the new DBMS.
     */
    public static function map(string $to, Table $table) : Table {
        if ($to == 'mysql') {
            $newTable = new MySQLTable($table->getName());
        } else if ($to == 'mssql') {
            $newTable = new MSSQLTable($table->getName());
        }
        $newTable->setComment($table->getComment());

        foreach ($table->getCols() as $key => $colObj) {
            $newTable->addColumn($key, ColumnFactory::map($to, $colObj));
        }

        foreach ($table->getForeignKeys() as $fk) {
            $sourceTbl = self::map($to, $fk->getSource());
            $newTable->addReference($sourceTbl, $fk->getColumnsMap(), $fk->getKeyName(), $fk->getOnUpdate(), $fk->getOnDelete());
        }

        return $newTable;
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
    public function removeColByKey(string $colKey) {
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
     * @return FK|null If the key was removed, the method will return the 
     * removed key as an object. If nothing changed, the method will return null.
     * 
     * @since 1.0
     */
    public function removeReference(string $keyName) {
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
    public function setComment(?string $comment = null) {
        if ($comment === null || strlen($comment) != 0) {
            $this->comment = $comment;
        }
    }
    /**
     * Sets the name of the table.
     * 
     * @param string $name The name of the table. Must be non-empty string in order 
     * to set.
     * 
     * @return bool If the name is set, the method will return true. Other than 
     * that, the method will return false.
     * 
     * @since 1.0
     */
    public function setName(string $name) : bool {
        $trimmed = trim($name);

        if (strlen($trimmed) > 0) {
            $this->oldName = $this->getName();

            if (strlen($this->oldName) == 0) {
                $this->oldName = null;
            }
            $this->name = Column::fixName($trimmed);

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
    public function setOwner(?Database $db = null) {
        if ($db instanceof Database) {
            $this->ownerSchema = $db;
        } else {
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
     * @param bool $withDbPrefix True to prefix table name with database name. 
     * false to not prefix table name with database name.
     * 
     * @since 1.0
     */
    public function setWithDbPrefix(bool $withDbPrefix) {
        $this->withDbPrefix = $withDbPrefix;
    }
    public abstract function toSQL();

    /**
     * 
     * @param \WebFiori\Database\Table $refTable
     * @param type $cols
     * @param type $keyName
     * @param type $onUpdate
     * @param type $onDelete
     * @throws DatabaseException
     */
    private function createFk(Table $refTable, $cols, $keyName, $onUpdate, $onDelete) {
        $fk = new FK();
        $fk->setOwner($this);
        $fk->setSource($refTable);

        if ($fk->setKeyName($keyName) === true) {
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
                $fk->setOnUpdate($onUpdate);
                $fk->setOnDelete($onDelete);
                $this->foreignKeys[] = $fk;
            }
        } else {
            throw new DatabaseException('Invalid Foreign Key name: \''.$keyName.'\'.');
        }
    }

    /**
     * Returns an array that contains columns with specific condition.
     * 
     * @param string $method The name of column method such as 'isUnique' or 'isPrimary'.
     * ,
     * @return array An array that contains objects of type 'Column'.
     */
    private function getColsHelper(string $method) : array {
        $arr = [];

        foreach ($this->getCols() as $col) {
            if ($col->$method()) {
                $arr[] = $col;
            }
        }

        return $arr;
    }
    private function getColsKeysHelper($method) : array {
        $arr = [];

        foreach ($this->getCols() as $columnKey => $col) {
            if ($col->$method()) {
                $arr[] = $columnKey;
            }
        }

        return $arr;
    }
    /**
     * 
     * @param string $key
     * @return bool
     * @since 1.6.1
     */
    private function isKeyNameValid(string $key) : bool {
        $keyLen = strlen($key);

        if ($keyLen == 0) {
            return false;
        }
        $actualKeyLen = $keyLen;

        for ($x = 0 ; $x < $keyLen ; $x++) {
            $ch = $key[$x];

            if (in_array($ch, ['-', ' ']) || ($ch >= 'a' && $ch <= 'z') || ($ch >= 'A' && $ch <= 'Z') || ($ch >= '0' && $ch <= '9')) {
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
