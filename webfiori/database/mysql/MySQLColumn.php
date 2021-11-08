<?php
/**
 * MIT License
 *
 * Copyright (c) 2019,WebFiori framework.
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

use webfiori\database\Column;
use webfiori\database\DatabaseException;
use webfiori\database\DateTimeValidator;
use webfiori\database\ColumnFactory;

/**
 * A class that represents a column in MySQL table.
 *
 * @author Ibrahim
 * 
 * @version 1.0
 */
class MySQLColumn extends Column {
    /**
     * A boolean value. Set to true if column is primary and auto increment.
     * 
     * @var boolean 
     * 
     * @since 1.0
     */
    private $isAutoInc;
    /**
     * A boolean which can be set to true in order to update column timestamp.
     * 
     * @var boolean 
     * 
     * @since 1.0
     */
    private $isAutoUpdate;
    /**
     * Version number of MySQL server.
     * 
     * @var string 
     * 
     * @since 1.0
     */
    private $mySqlVersion;
    /**
     * Creates new instance of the class.
     * 
     * This method is used to initialize basic attributes of the column. 
     * First of all, it sets MySQL version number to 8.0. Then it initializes the 
     * supported datatypes.
     * 
     * @param string $name The name of the column as it appears in the 
     * database. It must be a string and its not empty. Default value is 'col'.
     * 
     * @param string $datatype The type of column data. Default value is 'varchar'.
     *  
     * @param int $size The size of the column. Used only in case of 
     * 'varachar', 'int' or decimal. If the given size is invalid, 1 will be used as default 
     * value. 
     * 
     * @since 1.0
     */
    public function __construct($name = 'col', $datatype = 'varchar', $size = 1) {
        parent::__construct($name);
        $this->setSupportedTypes([
            'int',
            'varchar',
            'timestamp',
            'tinyblob',
            'blob',
            'mediumblob',
            'longblob',
            'datetime',
            'text',
            'mediumtext',
            'decimal',
            'double',
            'float',
            'boolean', 
            'bool',
            'bit'
        ]);
        $this->setDatatype($datatype);

        if (!$this->setSize($size)) {
            $this->setSize(1);
        }
        $this->isAutoUpdate = false;
        $this->setMySQLVersion('8.0');
    }
    /**
     * Constructs a string that can be used to create the column in a table.
     * 
     * @return string A string that can be used to create the column in a table.
     * 
     * @since 1.0
     */
    public function __toString() {
        $retVal = $this->_firstColPart();
        $retVal .= $this->_nullPart();
        $colDataType = $this->getDatatype();

        if ($this->isUnique() && $colDataType != 'boolean' && $colDataType != 'bool') {
            $retVal .= 'unique ';
        }
        $retVal .= $this->_defaultPart();

        if ($colDataType == 'varchar' || $colDataType == 'text' || $colDataType == 'mediumtext') {
            $retVal .= 'collate '.$this->getCollation().' ';
        }
        $retVal .= $this->_commentPart();

        return trim($retVal);
    }
    /**
     * Returns a string that represents the column.
     * 
     * The string can be used to add the column to a table or alter its properties.
     * 
     * @return string
     * 
     * @since 1.0
     */
    public function asString() {
        return $this.'';
    }
    /**
     * Validates and cleans a value for usage in a query.
     * 
     * @param mixed $val The value that will be cleaned.
     * 
     * @return mixed The method will return a value which is based on applied filters 
     * and the datatype of the column.
     * 
     * @since 1.0
     */
    public function cleanValue($val) {
        $valType = gettype($val);

        if ($valType == 'array') {
            $retVal = [];

            foreach ($val as $arrVal) {
                $retVal[] = $this->_cleanValueHelper($arrVal);
            }

            return $retVal;
        } else {
            return $this->_cleanValueHelper($val);
        }
    }

    /**
     * Creates an instance of the class 'Column' given an array of options.
     * @param array $options An associative array of options. The available options 
     * are: 
     * <ul>
     * <li><b>name</b>: Required. The name of the column in the database. If not 
     * provided, no object will be created.</li>
     * <li><b>datatype</b>: The datatype of the column. If not provided, 'varchar' 
     * will be used. Equal option: 'type'.</li>
     * <li><b>size</b>: Size of the column (if datatype does support size). 
     * If not provided, 1 will be used.</li>
     * <li><b>default</b>: A default value for the column if its value 
     * is not present in case of insert.</li>
     * <li><b>is-null</b>: A boolean. If the column allows null values, this should 
     * be set to true. Default is false.</li>
     * <li><b>is-primary</b>: A boolean. It must be set to true if the column 
     * represents a primary key. Note that the column will be set as unique 
     * once its set as a primary. Equal option: primary.</li>
     * <li><b>auto-inc</b>: A boolean. Only applicable if the column is a 
     * primary key. Set to true to auto-increment column value by 1 for every 
     * insert.</li>
     * <li><b>is-unique</b>: A boolean. If set to true, a unique index will 
     * be created for the column.</li>
     * <li><b>auto-update</b>: A boolean. If the column datatype is 'timestamp' or 
     * 'datetime' and this parameter is set to true, the time of update will 
     * change automatically without having to change it manually.</li>
     * <li><b>scale</b>: Number of numbers to the left of the decimal 
     * point. Only supported for decimal datatype.</li>
     * <li><b>comment</b>: A comment which can be used to describe the column.</li>
     * <li><b>validator</b>: A PHP function which can be used to validate user 
     * values before submitting the query to database.</li>
     * </ul>
     * 
     * @return MySQLColumn|null The method will return an object of type 'MySQLColumn' 
     * if created. If the index 'name' is not set, the method will return null.
     * 
     * @since 1.0
     */
    public static function createColObj($options) {
        if (isset($options['name'])) {
            
            return ColumnFactory::create('mysql', $options['name'], $options);
        }
    }
    /**
     * Returns column alias.
     * 
     * Note that the method will add backticks around the alias.
     * 
     * @return string|null Name alias.
     * 
     * @since 1.0
     */
    public function getAlias() {
        $alias = parent::getAlias();

        if ($alias !== null) {
            $alias = MySQLQuery::backtick($alias);
        }

        return $alias;
    }
    /**
     * Returns the value of column collation.
     * 
     * @return string If MySQL version is '5.5' or lower, the method will 
     * return 'utf8mb4_unicode_ci'. Other than that, the method will return 
     * 'utf8mb4_unicode_520_ci'.
     * 
     * @since 1.0
     */
    public function getCollation() {
        $split = explode('.', $this->getMySQLVersion());

        if (isset($split[0]) && intval($split[0]) <= 5 && isset($split[1]) && intval($split[1]) <= 5) {
            return 'utf8mb4_unicode_ci';
        }

        return 'utf8mb4_unicode_520_ci';
    }
    /**
     * Returns the default value of the column.
     * 
     * Note that for 'datetime' and 'timestamp', if default value is set to 
     * 'now()' or 'current_timestamp', the method will return a date string in the 
     * format 'YYYY-MM-DD HH:MM:SS' that represents current time.
     * 
     * @return mixed The default value of the column.
     * 
     * @since 1.0
     */
    public function getDefault() {
        $defaultVal = parent::getDefault();
        $retVal = null;

        if ($defaultVal !== null) {
            $dt = $this->getDatatype();

            if ($dt == 'varchar' || $dt == 'text' || $dt == 'mediumtext' || 
                    //$dt == 'timestamp' || $dt == 'datetime' || 
                    $dt == 'tinyblob' || $dt == 'blob' || $dt == 'mediumblob' || 
                    $dt == 'longblob' || $dt == 'decimal' || $dt == 'float' || $dt == 'double'
                    ) {
                $retVal = substr($defaultVal, 1, strlen($defaultVal) - 2);

                if ($dt == 'decimal' || $dt == 'float' || $dt == 'double') {
                    $retVal = floatval($retVal);
                }
            } else if ($dt == 'timestamp' || $dt == 'datetime') {
                if (!($defaultVal == 'now()' || $defaultVal == 'current_timestamp')) {
                    $retVal = substr($defaultVal, 1, strlen($defaultVal) - 2);
                } else {
                    $retVal = $defaultVal;
                }
            } else  if ($dt == 'int') {
                $retVal = intval($defaultVal);
            } else  if ($dt == 'boolean' || $dt == 'bool') {
                return $defaultVal === "b'1'" || $defaultVal === true;
            }
            return $retVal;
        } else {
            return parent::getDefault();
        }
    }
    /**
     * Returns version number of MySQL server.
     * 
     * This one is used to maintain compatibility with old MySQL servers.
     * 
     * @return string MySQL version number (such as '5.5'). If version number 
     * is not set, The default return value is '8.0'.
     * 
     * @since 1.0
     */
    public function getMySQLVersion() {
        return $this->mySqlVersion;
    }
    /**
     * Returns the name of the column.
     * 
     * Note that the method will add backticks around the name.
     * 
     * @return string The name of the column.
     * 
     * @since 1.0
     */
    public function getName() {
        return MySQLQuery::backtick(parent::getName());
    }
    /**
     * Returns a string that represents the datatype of column data in 
     * PHP.
     * 
     * This method basically maps the data that can be stored in a column from 
     * MySQL type to PHP type. For example, if column type is 'varchar', the method 
     * will return the value 'string'. If the column allow null values, the 
     * method will return 'string|null' and so on.
     * 
     * @return string A string that represents column type in PHP (such as 
     * 'integer' or 'boolean').
     * 
     * @since 1.0
     */
    public function getPHPType() {
        $colType = $this->getDatatype();

        if ($colType == 'bool' || $colType == 'boolean') {
            $isNullStr = '';
        } else {
            $isNullStr = $this->isNull() ? '|null' : '';
        }

        if ($colType == 'int') {
            return 'int'.$isNullStr;
        } else  if ($colType == 'decimal' || $colType == 'double' || $colType == 'float') {
            return 'double'.$isNullStr;
        } else  if ($colType == 'boolean' || $colType == 'bool') {
            return 'boolean'.$isNullStr;
        } else if ($colType == 'varchar' || $colType == 'datetime'
                || $colType == 'timestamp' || $colType == 'blob'
                || $colType == 'mediumblob') {
            return 'string'.$isNullStr;
        } else {
            return parent::getPHPType().$isNullStr;
        }
    }
    /**
     * Checks if the column is auto increment or not.
     * 
     * @return boolean true if the column is auto increment.
     * 
     * @since 1.0
     */
    public function isAutoInc() {
        return $this->isAutoInc;
    }
    /**
     * Returns the value of the property 'isAutoUpdate'.
     * 
     * @return boolean If the column type is 'datetime' or 'timestamp' and the 
     * column is set to auto update in case of update query, the method will 
     * return true. Default return value is valse.
     * 
     * @since 1.0
     */
    public function isAutoUpdate() {
        return $this->isAutoUpdate;
    }
    /**
     * Sets the value of the property 'isAutoUpdate'.
     * 
     * It is used in case the user want to update the date of a column 
     * that has the type 'datetime' or 'timestamp' automatically if a record is updated. 
     * This method has no effect for other datatypes.
     * 
     * @param boolean $bool If true is passed, then the value of the column will 
     * be updated in case an update query is constructed. 
     * 
     * @since 1.0
     */
    public function setAutoUpdate($bool) {
        if ($this->getDatatype() == 'datetime' || $this->getDatatype() == 'timestamp') {
            $this->isAutoUpdate = $bool === true;
        }
    }
    /**
     * Sets the datatype of the column.
     * 
     * @param string $type A string that represents the datatype of the column.
     * 
     * @throws DatabaseException The method will throw an exception if the given 
     * column type is not supported.
     * 
     * @since 1.0
     */
    public function setDatatype($type) {
        try {
            parent::setDatatype($type);
        } catch (DatabaseException $ex) {
            throw new DatabaseException($ex->getMessage());
        }

        $s_type = $this->getDatatype();

        if ($s_type != 'int') {
            $this->setIsAutoInc(false);
        }

        $this->setDefault(null);
    }
    /**
     * Sets the default value for the column to use in case of insert.
     * 
     * For integer data type, the passed value must be an integer. For string types such as 
     * 'varchar' or 'text', the passed value must be a string. If the datatype 
     * is 'timestamp', the default will be set to current time and date 
     * if non-null value is passed (the value which is returned by the 
     * function date('Y-m-d H:i:s). If the passed 
     * value is a date string in the format 'YYYY-MM-DD HH:MM:SS', then it 
     * will be set to the given value. If the passed 
     * value is a date string in the format 'YYYY-MM-DD', then the default 
     * will be set to 'YYYY-MM-DD 00:00:00'. same applies to 'datetime' datatype. If 
     * null is passed, it implies that no default value will be used.
     * 
     * @param mixed $default The default value which will be set.
     * 
     * @since 1.0
     */
    public function setDefault($default) {
        parent::setDefault($this->cleanValue($default));
        $type = $this->getDatatype();

        if (($type == 'datetime' || $type == 'timestamp') && strlen($this->getDefault()) == 0 && $this->getDefault() !== null) {
            parent::setDefault(null);
        }
    }
    /**
     * Sets the value of the property <b>$isAutoInc</b>.
     * 
     * This attribute can be set only if the column is primary key and the 
     * datatype of the column is set to 'int'.
     * 
     * @param boolean $bool true or false.
     * 
     * @return boolean <b>true</b> if the property value changed. false 
     * otherwise.
     * 
     * @since 1.0
     */
    public function setIsAutoInc($bool) {
        if ($this->isPrimary() && gettype($bool) == 'boolean' && $this->getDatatype() == 'int') {
            $this->isAutoInc = $bool;

            return true;
        }

        return false;
    }
    /**
     * Makes the column primary or not.
     * 
     * Note that once the column become primary, it becomes unique by default. Also, 
     * Note that if column type is 'boolean', it cannot be a primary.
     * 
     * @param boolean $bool <b>true</b> if the column is primary key. false 
     * if not.
     * 
     * @since 1.0
     */
    public function setIsPrimary($bool) {
        if ($this->getDatatype() != 'boolean' && $this->getDatatype() != 'bool') {
            parent::setIsPrimary($bool);

            if ($this->isPrimary() === true) {
                $this->setIsNull(false);
                $this->setIsUnique(true);
            }
        } else {
            parent::setIsPrimary(false);
        }
    }
    /**
     * Sets version number of MySQL server.
     * 
     * Version number of MySQL is used to set the correct collation for the column 
     * in case of varchar or text data types. If MySQL version is '5.5' or lower, 
     * collation will be set to 'utf8mb4_unicode_ci'. Other than that, the 
     * collation will be set to 'utf8mb4_unicode_520_ci'.
     * 
     * @param string $vNum MySQL version number (such as '5.5').
     * 
     * @since 1.0
     */
    public function setMySQLVersion($vNum) {
        if (strlen($vNum) > 0) {
            $split = explode('.', $vNum);

            if (count($split) >= 2) {
                $major = intval($split[0]);
                $minor = intval($split[1]);

                if ($major >= 0 && $minor >= 0) {
                    $this->mySqlVersion = $vNum;
                }
            }
        }
    }
    /**
     * Sets or unset the owner table of the column.
     * 
     * Note that the developer should not call this method manually. It is 
     * used only if the column is added or removed from MySQLTable object.
     * 
     * @param MySQLTable|null $table The owner of the column. If null is given, 
     * The owner will be unset.
     * 
     * @since 1.0
     */
    public function setOwner($table) {
        parent::setOwner($table);

        if ($this->getOwner() !== null && $this->getOwner() instanceof MySQLTable) {
            $this->setMySQLVersion($this->getOwner()->getMySQLVersion());
        }
    }
    /**
     * Sets the value of Scale.
     * 
     * Scale is simply the number of digits that will appear to the right of 
     * decimal point. Only applicable if the datatype of the column is decimal, 
     * float and double.
     * 
     * @param int $val Number of numbers after the decimal point. It must be a 
     * positive number.
     * 
     * @return boolean If scale value is set, the method will return true. 
     * false otherwise. The method will not set the scale in the following cases:
     * <ul>
     * <li>Datatype of the column is not decimal, float or double.</li>
     * <li>Size of the column is 0.</li>
     * <li>Given scale value is greater than the size of the column.</li>
     * </ul>
     * 
     * @since 1.0
     */
    public function setScale($val) {
        $type = $this->getDatatype();

        if ($type == 'decimal' || $type == 'float' || $type == 'double') {
            $size = $this->getSize();

            if ($size != 0 && $val >= 0 && ($size - $val > 0)) {
                return parent::setScale($val);
            }
        }

        return false;
    }
    /**
     * Sets the size of data (for numercal types and 'varchar' only). 
     * 
     * If the data type of the column is 'int', the maximum size is 11. If a 
     * number greater than 11 is given, the value will be set to 11. The 
     * maximum size for the 'varchar' is 21845. If a value greater that that is given, 
     * the datatype of the column will be changed to 'mediumtext'.
     * For decimal, double and float data types, the value will represent 
     * the  precision. If zero is given, then no specific value for precision 
     * and scale will be used. If the datatype is boolean, the passed value will 
     * be ignored and the size is set to 1.
     * 
     * @param int $size The size to set.
     * 
     * @return boolean true if the size is set. The method will return 
     * false in case the size is invalid or datatype does not support 
     * size attribute. Also The method will return 
     * false in case the datatype of the column does not 
     * support size.
     * 
     * @since 1.0
     */
    public function setSize($size) {
        $type = $this->getDatatype();
        $retVal = false;

        if ($type == 'boolean' || $type == 'bool') {
            $retVal = parent::setSize(1);
        } else if ($type == 'varchar' || $type == 'text') {
            $retVal = $this->_textTypeSize($size);
        } else if ($type == 'int') {
            $retVal = $this->_intSize($size);
        } else if (($type == 'decimal' || $type == 'float' || $type == 'double') && $size >= 0) {
            $retVal = parent::setSize($size);
        } else {
            $retVal = false;
        }

        return $retVal;
    }
    private function _cleanValueHelper($val) {
        $colDatatype = $this->getDatatype();
        $cleanedVal = null;

        if ($val === null) {
            return null;
        } else if ($colDatatype == 'int') {
            $cleanedVal = intval($val);
        } else if ($colDatatype == 'bool' || $colDatatype == 'boolean') {
            if ($val === true) {
                return "b'1'";
            } else {
                return "b'0'";
            }
        } else if ($colDatatype == 'decimal' || $colDatatype == 'float' || $colDatatype == 'double') {
            $cleanedVal = "'".floatval($val)."'";
        } else if ($colDatatype == 'varchar' || $colDatatype == 'text' || $colDatatype == 'mediumtext') {
            $ownerTable = $this->getOwner();
            if ($ownerTable !== null) {
                $db = $ownerTable->getOwner();
                if ($db !== null) {
                    $conn = $db->getConnection();
                    $cleanedVal = mysqli_real_escape_string($conn->getMysqli(), $val);
                } else {
                    $cleanedVal = filter_var(addslashes($val));
                }
            } else {
                $cleanedVal = filter_var(addslashes($val));
            }
            // It is not secure if not escaped without connection
            // Think about multi-byte strings
            // At minimum, just sanitize the value using default filter
            
        } else if ($colDatatype == 'datetime' || $colDatatype == 'timestamp') {
            if ($val != 'now()' && $val != 'current_timestamp') {
                $cleanedVal = $this->_dateCleanUp($val);
            } else {
                $cleanedVal = $val;
            }
        } else {
            //blob mostly
            $cleanedVal = $val;
        }
        $retVal = call_user_func($this->getCustomCleaner(), $val, $cleanedVal);

        if ($retVal !== null && ($colDatatype == 'varchar' || $colDatatype == 'text' || $colDatatype == 'mediumtext')) {
            return "'".$retVal."'";
        }

        return $retVal;
    }
    private function _commentPart() {
        $colComment = $this->getComment();

        if ($colComment !== null) {
            return 'comment \''.$colComment.'\'';
        }
    }
    private function _dateCleanUp($val) {
        $trimmed = strtolower(trim($val));
        $cleanedVal = '';

        if ($trimmed == 'current_timestamp') {
            $cleanedVal = 'current_timestamp';
        } else if ($trimmed == 'now()') {
            $cleanedVal = 'now()';
        } else if (DateTimeValidator::isValidDateTime($trimmed)) {
            $cleanedVal = '\''.$trimmed.'\'';
        } else if (DateTimeValidator::isValidDate($trimmed)) {
            $cleanedVal = '\''.$trimmed.' 00:00:00\'';
        }

        return $cleanedVal;
    }
    private function _defaultPart() {
        $colDataType = $this->getDatatype();
        $colDefault = $this->getDefault();

        if ($colDefault !== null) {
            if ($colDataType == 'boolean' || $colDataType == 'bool') {
                if ($this->getDefault() === true) {
                    return 'default b\'1\' ';
                } else {
                    return 'default b\'0\' ';
                }
            } else if ($colDataType == 'datetime' || $colDataType == 'timestamp') {
                if ($colDefault == 'now()' || $colDefault == 'current_timestamp') {
                    return 'default '.$colDefault.' ';
                } else {
                    return 'default '.$this->cleanValue($colDefault).' ';
                }
            } else {
                return 'default '.$this->cleanValue($colDefault).' ';
            }
        }
    }
    
    private function _firstColPart() {
        $retVal = $this->getName().' ';
        $colDataType = $this->getDatatype();
        
        if ($colDataType == 'int') {
            $vNum = explode('.', $this->getMySQLVersion());
            $major = intval($vNum[0]);

            if ($major >= 8) {
                $retVal .= $colDataType.' ';
            } else {
                $retVal .= $colDataType.'('.$this->getSize().') ';
            }
        } else if ($colDataType == 'varchar' || $colDataType == 'text') {
            $retVal .= $colDataType.'('.$this->getSize().') ';
        } else if ($colDataType == 'boolean' || $colDataType == 'bool') {
            $retVal .= 'bit(1) ';
        } else if ($colDataType == 'decimal' || $colDataType == 'float' || $colDataType == 'double') {
            if ($this->getSize() != 0) {
                $retVal .= $colDataType.'('.$this->getSize().','.$this->getScale().') ';
            } else {
                $retVal .= $colDataType.' ';
            }
        } else {
            $retVal .= $colDataType.' ';
        }

        return $retVal;
    }
    private function _intSize($size) {
        if ($size > 0 && $size < 12) {
            parent::setSize($size);

            return true;
        } else if ($size > 11) {
            parent::setSize(11);

            return true;
        }

        return false;
    }
    private function _nullPart() {
        $colDataType = $this->getDatatype();

        if (!$this->isNull() || $colDataType == 'boolean' || $colDataType == 'bool') {
            return 'not null ';
        } else {
            return 'null ';
        }
    }
    private function _textTypeSize($size) {
        if ($size > 0) {
            parent::setSize($size);

            if ($size > 21845) {
                $this->setDatatype('mediumtext');
            }

            return true;
        }

        return false;
    }
}
