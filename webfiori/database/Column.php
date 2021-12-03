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
     * An array which holds all supported datatypes of the column.
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
     * @param string $name The name of the column as it appears in the database.
     * 
     * @since 1.0
     */
    public function __construct($name) {
        $this->setName($name);
        $this->supportedTypes = ['char'];
        $this->setDatatype('char');
        $this->setSize(1);
        $this->scale = 0;
        $this->setIsPrimary(false);
        $this->setIsNull(false);
        $this->setIsUnique(false);
        $this->setWithTablePrefix(false);
        $this->columnIndex = -1;
        $this->cleanupFunc = function ($val, $cleanedVal)
        {
            return $cleanedVal;
        };
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
    public abstract function asString();
    /**
     * Filters and cleans column value before using it in a query.
     * 
     * @return mixed The value after cleanup.
     * 
     * @since 1.0
     */
    public abstract function cleanValue($val);
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
     * @return Closure The function which is used to filter the value of the column.
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
    public function getDatatype() {
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
    public function getIndex() {
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
    public function getName() {
        $ownerTable = $this->getOwner();

        if ($ownerTable !== null && $this->isNameWithTablePrefix()) {
            return $ownerTable->getName().'.'.$this->name;
        }

        return $this->name;
    }
    /**
     * 
     * Returns the name of the column.
     * 
     * @return string The name of the column.
     * 
     * @since 1.0
     */
    public final function getNormalName() {
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
     * Returns a string that represents the datatype as one of PHP datatypes.
     * 
     * The main aim of this method is to produce correct type hinting when mapping 
     * the column to an entity class. For example, the 'varchar' in MySQL is 
     * a 'string' in PHP.
     * 
     * @return string A string that represents column datatype in PHP.
     * 
     * @since 1.0
     */
    public function getPHPType() {
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
    public function getScale() {
        return $this->scale;
    }
    /**
     * Returns the size of the column.
     * 
     * @return int The size of the column. 
     * 
     * @since 1.0
     */
    public function getSize() {
        return $this->size;
    }
    /**
     * Returns an array that contains supported datatypes.
     * 
     * @return array An array that contains supported datatypes.
     * 
     * @since 1.0
     */
    public function getSupportedTypes() {
        return $this->supportedTypes;
    }
    /**
     * Checks if table name will be prefixed with database name or not.
     * 
     * @return boolean True if it will be prefixed. False if not.
     * 
     * @since 1.0
     */
    public function isNameWithTablePrefix() {
        return $this->withTablePrefix;
    }
    /**
     * Checks if the column allows null values.
     * 
     * @return boolean true if the column allows null values. Default return 
     * value is false which means that the column does not allow null values.
     * 
     * @since 1.0
     */
    public function isNull() {
        return $this->isNull;
    }
    /**
     * Checks if the column is part of the primary key or not.
     * 
     * @return boolean true if the column is primary. 
     * Default return value is false.
     * 
     * @since 1.0
     */
    public function isPrimary() {
        return $this->isPrimary;
    }
    /**
     * Returns the value of the property $isUnique.
     * 
     * @return boolean true if the column value is unique. 
     * 
     * @since 1.0
     */
    public function isUnique() {
        return $this->isUnique;
    }
    /**
     * Sets an alias for the column.
     * 
     * @param string $alias Column alias.
     * 
     * @since 1.0
     */
    public function setAlias($alias) {
        $trimmed = trim($alias);

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
    public function setComment($comment) {
        $trimmed = trim($comment);

        if (strlen($trimmed) != 0) {
            $this->comment = $trimmed;
        } else {
            if ($comment === null) {
                $this->comment = null;
            }
        }
    }
    /**
     * Sets a custom filtering function to cleanup values before being used in 
     * database queries.
     * 
     * The function signature should be as follows : <code>function ($orgVal, $cleanedVa)</code>
     * where the first value is the original value and the second one is the value with 
     * basic filtering applied to.
     * 
     * @param Closure $callback The callback.
     * 
     * @return boolean If it was updated, the method will return true. Other than that, 
     * the method will return false.
     * 
     * @since 1.0
     */
    public function setCustomFilter($callback) {
        if (is_callable($callback)) {
            $this->cleanupFunc = $callback;

            return true;
        }

        return false;
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
    public function setDatatype($type) {
        $trimmed = strtolower(trim($type));

        if (!in_array($trimmed, $this->getSupportedTypes())) {
            throw new DatabaseException('Column datatype not supported: \''.$trimmed.'\'.');
        }

        if ($trimmed == 'bool' || $trimmed == 'boolean') {
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
     * @param boolean $bool true if the column allow null values. false 
     * if not.
     * 
     * @return boolean true If the property value is updated. If the given 
     * value is not a boolean, the method will return false. Also if 
     * the column represents a primary key, the method will always return false.
     * 
     * @since 1.0
     */
    public function setIsNull($bool) {
        $colDatatype = $this->getDatatype();

        if (!($colDatatype == 'bool' || $colDatatype == 'boolean') && !$this->isPrimary()) {
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
     * @param boolean $bool <b>true</b> if the column is primary key. false 
     * if not.
     * 
     * @since 1.0
     */
    public function setIsPrimary($bool) {
        $isPr = $bool === true;

        if ($isPr) {
            $this->setIsNull(false);
        }
        $this->isPrimary = $isPr;
    }
    /**
     * Sets the value of the property $isUnique.
     * 
     * @param boolean $bool True if the column value is unique. false 
     * if not.
     * 
     * @since 1.0
     */
    public function setIsUnique($bool) {
        $this->isUnique = $bool === true;
    }
    /**
     * Sets the name of the column.
     * 
     * @param string $name The name of the column as it appears in the database.
     * 
     * @since 1.0
     */
    public function setName($name) {
        $this->oldName = $this->getName();
        if ($this instanceof MySQLColumn) {
            $this->name = trim($name, '`');
        } else if ($this instanceof MSSQLColumn) {
            $this->name = trim(trim($name, '['), ']');
        } else {
            $this->name = trim($name);
        }
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
    public function setOwner($table) {
        if ($table instanceof Table) {
            $this->prevOwner = $this->owner;
            $this->owner = $table;
            $colsCount = $table->getColsCount();
            $this->columnIndex = $colsCount == 0 ? 0 : $colsCount;
        } else if ($table === null) {
            $this->prevOwner = $this->owner;
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
    public function setScale($scale) {
        if ($scale >= 0) {
            $this->scale = $scale;

            return true;
        }

        return false;
    }
    /**
     * Sets the size of the data that will be stored by the column.
     * 
     * @param int $size A positive number that represents the size. must be greater 
     * than 0.
     * 
     * @return boolean If the size is set, the method will return true. Other than 
     * that, it will return false.
     * 
     * @since 1.0
     */
    public function setSize($size) {
        if ($size > 0) {
            $this->size = $size;

            return true;
        }

        return false;
    }
    /**
     * Adds a set of values as a supported datatypes for the column.
     * 
     * @param array $datatypes An indexed array that contains a strings that 
     * represents the types.
     * 
     * @since 1.0
     */
    public function setSupportedTypes(array $datatypes) {
        foreach ($datatypes as $type) {
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
     * @param boolean $withDbPrefix True to prefix table name with database name. 
     * false to not prefix table name with database name.
     * 
     * @since 1.0
     */
    public function setWithTablePrefix($withDbPrefix) {
        $this->withTablePrefix = $withDbPrefix === true;
    }
}
