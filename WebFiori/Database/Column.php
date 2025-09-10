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

/**
 * A class which represents a column in a database table.
 *
 * The developer must extend this class to implement different columns for 
 * different relational databases.
 * 
 * @author Ibrahim
 * 
 * @since 1.0.1
 */
abstract class Column {
    /**
     * An array that holds names of values which represents boolean type.
     */
    const BOOL_TYPES = ['bool', 'boolean'];
    /**
     *
     * @var string|null
     * 
     * @since 1.0 
     */
    private $alias;
    /**
     *
     * @var Closure 
     * 
     * @since 1.0
     */
    private $cleanupFunc;
    /**
     * The index of the column in owner table.
     * 
     * @var int
     * 
     * @since 1.0 
     */
    private $columnIndex;
    /**
     *
     * @var string 
     * 
     * @since 1.0
     */
    private $comment;
    /**
     * The datatype of the column.
     * 
     * @var string
     * 
     * @since 1.0 
     */
    private $datatype;
    /**
     *
     * @var mixed 
     * 
     * @since 1.0
     */
    private $default;
    /**
     * A boolean value. Set to true if column allow null values. Default 
     * is false.
     * 
     * @var boolean 
     * 
     * @since 1.0
     */
    private $isNull;
    /**
     * A boolean value. Set to true if the column is a primary key. Default 
     * is false.
     * 
     * @var boolean 
     * 
     * @since 1.0
     */
    private $isPrimary;

    /**
     * A boolean value. Set to true if column is unique.
     * 
     * @var boolean
     * 
     * @since 1.0 
     */
    private $isUnique;
    /**
     * The name of the column as it appears in the database.
     * 
     * @var string
     * 
     * @since 1.0 
     */
    private $name;
    /**
     *
     * @var string|null 
     */
    private $oldName;
    /**
     *
     * @var Table|null 
     * 
     * @since 1.0
     */
    private $owner;
    /**
     *
     * @var Table|null 
     * 
     * @since 1.0
     */
    private $prevOwner;
    /**
     * The number of numbers that will appear after the decimal point.
     * 
     * @var int 
     * 
     * @since 1.0
     */
    private $scale;
    /**
     * The size of the data in the column. It must be 
     * a positive value.
     * @var int 
     * @since 1.0
     */
    private $size;
    /**
     * An array which holds all supported data types of the column.
     * 
     * @var array
     * 
     * @since 1.0 
     */
    private $supportedTypes;
    private $withTablePrefix;

    /**
     * Creates new instance of the class.
     *
     * By default, the instance will have one data type which is 'mixed'.
     * This type is used as placeholder for dynamically created columns
     * when running SQL queries on the database.
     *
     * @param string $name The name of the column as it appears in the database.
     *
     * @throws DatabaseException
     * @since 1.0
     */
    public function __construct(string $name) {
        $this->name = '';
        $this->isNull = false;
        $this->isPrimary = false;
        $this->isUnique = false;
        $this->size = 1;
        $this->scale = 0;
        $this->supportedTypes = ['mixed'];
        $this->cleanupFunc = function ($val, $cleanedVal)
        {
            return $cleanedVal;
        };
        $this->setDatatype('mixed');
        $this->setWithTablePrefix(false);
        $this->setName($name);


        $this->columnIndex = -1;
    }
    /**
     * Returns a string that represents the column.
     * 
     * The developer should implement this method in a way that it returns a 
     * string that can be used to add the column to a new table or to alter 
     * a column in an existing table.
     * 
     * @return string A string that represents the column.
     * 
     * @since 1.0
     */
    public abstract function asString() : string ;
    /**
     * Filters and cleans column value before using it in a query.
     * 
     * @return mixed The value after cleanup.
     * 
     * @since 1.0
     */
    public abstract function cleanValue($val);
    /**
     * Removes '`', '[' and ']' from name of a column or table.
     * 
     * @param string $name
     * 
     * @return string
     */
    public static function fixName(string $name) : string {
        while ($name[0] == '`' || $name[0] == '[') {
            $name = substr($name, 1);
        }
        $len = strlen($name);

        while ($name[$len - 1] == '`' || $name[$len - 1] == ']') {
            $name = substr($name, 0, $len - 1);
            $len = strlen($name);
        }

        return $name;
    }
    /**
     * Returns column alias.
     * 
     * @return string|null Name alias.
     * 
     * @since 1.0
     */
    public function getAlias() {
        return $this->alias;
    }
    /**
     * Returns a string that represents a comment which was added with the column.
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
     * Returns the function which is used to filter the value of the column.
     * 
     * @return callable The function which is used to filter the value of the column.
     * 
     * @since 1.0
     */
    public function getCustomCleaner() {
        return $this->cleanupFunc;
    }
    /**
     * Returns the type of column data (such as 'varchar').
     * 
     * @return string The type of column data. Default return value is 'char'.
     * 
     * @since 1.0
     */
    public function getDatatype() : string {
        return $this->datatype;
    }
    /**
     * Returns the default value of the column.
     * 
     * @return mixed The default value of the column.
     * 
     * @since 1.0
     */
    public function getDefault() {
        return $this->default;
    }
    /**
     * Returns the index of the column in its parent table.
     * 
     * @return int The index of the column in its parent table starting from 0. 
     * If the column has no parent table, the method will return -1.
     * 
     * @since 1.0
     */
    public function getIndex() : int {
        return $this->columnIndex;
    }
    /**
     * 
     * Returns the name of the column.
     * 
     * @return string The name of the column.
     * 
     * @since 1.0
     */
    public function getName() : string {
        $ownerTable = $this->getOwner();

        if ($ownerTable !== null && $this->isNameWithTablePrefix()) {
            return $ownerTable->getName().'.'.$this->name;
        }

        return $this->name !== null ? $this->name : '';
    }
    /**
     * 
     * Returns the name of the column.
     * 
     * @return string The name of the column.
     * 
     * @since 1.0
     */
    public final function getNormalName() : string {
        $ownerTable = $this->getOwner();

        if ($ownerTable !== null && $this->isNameWithTablePrefix()) {
            return $ownerTable->getNormalName().'.'.$this->name;
        }

        return $this->name;
    }
    /**
     * Returns the old name of the column.
     * 
     * Note that the old name will be set only if the method 
     * Column::setName() is called more than once in the same instance.
     * If no old name is set, the method will return current name.
     * 
     * @return string|null The method will return a string that represents the 
     * old name if it is set. Null if not.
     * 
     * @since 1.0.1
     */
    public function getOldName() {
        if ($this->oldName === null) {
            return $this->getName();
        }

        return $this->oldName;
    }
    /**
     * Returns the table who owns the column.
     * 
     * @return Table|null If the owner is set, the method will return an 
     * object of type 'Table' that represent it. Other than that, the method 
     * will return null.
     * 
     * @since 1.0
     */
    public function getOwner() {
        return $this->owner;
    }
    /**
     * Returns a string that represents the datatype as one of PHP data types.
     * 
     * The main aim of this method is to produce correct type hinting when mapping 
     * the column to an entity class. For example, the 'varchar' in MySQL is 
     * a 'string' in PHP.
     * 
     * @return string A string that represents column datatype in PHP.
     * 
     * @since 1.0
     */
    public function getPHPType() : string {
        if ($this->getDatatype() == 'char') {
            return 'string';
        }

        return 'mixed';
    }
    /**
     * Returns the previous table which was owns the column.
     * 
     * @return Table|null If the owner of the table was set then updated, the 
     * method will return the old owner value.
     * 
     * @since 1.0
     */
    public function getPrevOwner() {
        return $this->prevOwner;
    }
    /**
     * Returns the value of scale.
     * 
     * Scale is simply the number of digits that will appear to the right of 
     * decimal point. Only applicable if the datatype of the column is decimal, 
     * float or double.
     * 
     * @return int The number of numbers after the decimal point.
     * 
     * @since 1.0
     */
    public function getScale() : int {
        return $this->scale;
    }
    /**
     * Returns the size of the column.
     * 
     * @return int The size of the column. 
     * 
     * @since 1.0
     */
    public function getSize() : int {
        return $this->size;
    }
    /**
     * Returns an array that contains supported data types.
     * 
     * @return array An array that contains supported data types.
     * 
     * @since 1.0
     */
    public function getSupportedTypes() : array {
        return $this->supportedTypes;
    }
    /**
     * Checks if table name will be prefixed with database name or not.
     * 
     * @return bool True if it will be prefixed. False if not.
     * 
     * @since 1.0
     */
    public function isNameWithTablePrefix() : bool {
        return $this->withTablePrefix;
    }
    /**
     * Checks if the column allows null values.
     * 
     * @return bool true if the column allows null values. Default return 
     * value is false which means that the column does not allow null values.
     * 
     * @since 1.0
     */
    public function isNull() : bool {
        return $this->isNull;
    }
    /**
     * Checks if the column is part of the primary key or not.
     * 
     * @return bool true if the column is primary. 
     * Default return value is false.
     * 
     * @since 1.0
     */
    public function isPrimary() : bool {
        return $this->isPrimary;
    }
    /**
     * Returns the value of the property $isUnique.
     * 
     * @return bool true if the column value is unique. 
     * 
     * @since 1.0
     */
    public function isUnique() : bool {
        return $this->isUnique;
    }
    /**
     * Sets an alias for the column.
     * 
     * @param string|null $alias Column alias.
     * 
     * @since 1.0
     */
    public function setAlias(?string $alias = null) {
        $trimmed = trim($alias.'');

        if (strlen($trimmed) != 0) {
            $this->alias = $trimmed;
        }
    }
    /**
     * Sets a comment which will appear with the column.
     * 
     * @param string|null $comment Comment text. It must be non-empty string 
     * in order to set. If null is passed, the comment will be removed.
     * 
     * @since 1.0
     */
    public function setComment(?string $comment = null) {
        $trimmed = trim($comment.'');

        if (strlen($trimmed) != 0) {
            $this->comment = $trimmed;
        } else if ($comment === null) {
            $this->comment = null;
        }
    }
    /**
     * Sets a custom filtering function to clean up values before being used in 
     * database queries.
     * 
     * The function signature should be as follows : <code>function ($orgVal, $cleanedVa)</code>
     * where the first value is the original value and the second one is the value with 
     * basic filtering applied to.
     * 
     * @param callable $callback The callback
     * 
     * @since 1.0
     */
    public function setCustomFilter(callable $callback) {
        $this->cleanupFunc = $callback;
    }
    /**
     * Sets the type of column data.
     * 
     * Note that calling this method will set default value to null.
     * 
     * @param string $type The type of column data.
     * 
     * @throws DatabaseException The method will throw an exception if the given 
     * column type is not supported.
     * 
     * @since 1.0
     */
    public function setDatatype(string $type) {
        $trimmed = strtolower(trim($type));

        if (!in_array($trimmed, $this->getSupportedTypes())) {
            throw new DatabaseException('Column datatype not supported: \''.$trimmed.'\'.');
        }

        if (in_array($trimmed, Column::BOOL_TYPES)) {
            $this->setIsNull(false);
        }

        $this->datatype = $trimmed;
        $this->setDefault(null);
    }
    /**
     * Sets the default value for the column to use in case of insert.
     * 
     * @param mixed $defaultVal The default value.
     * 
     * @since 1.0
     */
    public function setDefault($defaultVal) {
        $this->default = $defaultVal;
    }
    /**
     * Updates the value of the property $isNull.
     * 
     * This property can be set to true if the column allow the insertion of 
     * null values. Note that for primary key column, the method will have no 
     * effect.
     * 
     * @param bool $bool true if the column allow null values. false 
     * if not.
     * 
     * @return bool true If the property value is updated. If the given 
     * value is not a boolean, the method will return false. Also, if 
     * the column represents a primary key, the method will always return false.
     * 
     * @since 1.0
     */
    public function setIsNull(bool $bool) : bool {
        $colDatatype = $this->getDatatype();

        if (!(in_array($colDatatype, Column::BOOL_TYPES)) && !$this->isPrimary()) {
            $this->isNull = $bool === true;

            return true;
        }

        return false;
    }
    /**
     * Updates the value of the property <b>$isPrimary</b>.
     * 
     * Note that once the column become primary, it will not allow null values.
     * 
     * @param bool $bool <b>true</b> if the column is primary key. false 
     * if not.
     * 
     * @since 1.0
     */
    public function setIsPrimary(bool $bool) {
        if ($bool) {
            $this->setIsNull(false);
        }
        $this->isPrimary = $bool;
    }
    /**
     * Sets the value of the property $isUnique.
     * 
     * @param bool $bool True if the column value is unique. false 
     * if not.
     * 
     * @since 1.0
     */
    public function setIsUnique(bool $bool) {
        $this->isUnique = $bool;
    }
    /**
     * Sets the name of the column.
     * 
     * @param string $name The name of the column as it appears in the database.
     * 
     * @since 1.0
     */
    public function setName(string $name) {
        $this->oldName = $this->getName();

        if (strlen($this->oldName) == 0) {
            $this->oldName = null;
        }

        $this->name = self::fixName(trim($name));
    }
    /**
     * Sets or unset the owner table of the column.
     * 
     * Note that the developer should not call this method manually. It is 
     * used only if the column is added or removed from Table object.
     * 
     * @param Table|null $table The owner of the column. If null is given, 
     * The owner will be unset.
     * 
     * @since 1.0
     */
    public function setOwner(?Table $table = null) {
        $this->prevOwner = $this->owner;

        if ($table instanceof Table) {
            $this->owner = $table;
            $colsCount = $table->getColsCount();
            $this->columnIndex = $colsCount == 0 ? 0 : $colsCount;
        } else {
            $this->owner = null;
            $this->columnIndex = -1;
        }
    }
    /**
     * Sets the value of Scale.
     * 
     * Scale is simply the number of digits that will appear to the right of 
     * decimal point. Only applicable if the datatype of the column is decimal, 
     * float and double.
     * 
     * @since 1.0
     */
    public function setScale(int $scale) : bool {
        if ($scale >= 0) {
            $this->scale = $scale;

            return true;
        }

        return false;
    }
    /**
     * Sets the size of the data that will be stored by the column.
     * 
     * @param int $size A positive number that represents the size. must be greater than 0.
     * 
     * @return bool If the size is set, the method will return true. Other than 
     * that, it will return false.
     * 
     */
    public function setSize(int $size) : bool {
        if ($size >= 0) {
            $this->size = $size;

            return true;
        }

        return false;
    }
    /**
     * Adds a set of values as a supported data types for the column.
     * 
     * @param array $dataTypes An indexed array that contains a strings that
     * represents the types.
     * 
     * @since 1.0
     */
    public function setSupportedTypes(array $dataTypes) {
        foreach ($dataTypes as $type) {
            $trimmed = strtolower($type);

            if (strlen($trimmed) != 0) {
                $this->supportedTypes[] = $trimmed;
            }
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
    public function setWithTablePrefix(bool $withDbPrefix) {
        $this->withTablePrefix = $withDbPrefix;
    }
}
