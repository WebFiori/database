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

use webfiori\database\Column;
use webfiori\database\ColumnFactory;
use webfiori\database\DateTimeValidator;
/**
 * A class that represents a column in MSSQL table.
 *
 * @author Ibrahim
 * 
 * @version 1.0
 */
class MSSQLColumn extends Column {
    /**
     * A boolean which can be set to true in order to auto-update any 
     * date datatype column..
     * 
     * @var boolean 
     * 
     * @since 1.0
     */
    private $isAutoUpdate;
    /**
     * A boolean which is set to true if the column is of type int and is
     * set as an identity.
     * 
     * @var bool
     */
    private $isIdintity;
    /**
     * Creates new instance of the class.
     * 
     * @param string $name The unique name of the column.
     * 
     * @param string $datatype The datatype of the column. Default value is 
     * 'nvarchar'
     * 
     * @param int $size The size of the column. Used only if column type 
     * supports size.
     * 
     * @since 1.0
     */
    public function __construct(string $name = 'col', string $datatype = 'nvarchar', int $size = 1) {
        parent::__construct($name);
        $this->isAutoUpdate = false;
        $this->isIdintity = false;
        $this->setSupportedTypes([
            'int',
            'bigint',
            'varchar',
            'nvarchar',
            'char',
            'nchar',
            'binary',
            'varbinary',
            'date',
            'datetime2',
            'time',
            'money',
            'bit',
            'decimal',
            'float',
            'boolean',
            'bool'
        ]);
        $this->setDatatype($datatype);

        if (!$this->setSize($size)) {
            $this->setSize(1);
        }
    }
    /**
     * Returns a string that represents the column.
     * 
     * The string can be used to alter or add the column to a table.
     * 
     * @return string
     */
    public function __toString() {
        $retVal = $this->_firstColPart();
        $retVal .= $this->_nullPart();
        $retVal .= $this->_defaultPart();

        return trim($retVal);
    }
    /**
     * Returns a string that represents the column.
     * 
     * The string can be used to alter or add the column to a table.
     * 
     * @return string
     */
    public function asString() : string {
        return $this->__toString();
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
     * Returns a string which can be used to add column comment as extended 
     * property.
     * 
     * The returned SQL statement will use the procedure 'sp_[add|update|drop]extendedproperty'
     * 
     * @param string $spType The type of operation. Can be 'add', 'update' or 'drop'.
     * 
     * @return string If the comment of the column is set, the method will
     * return non-empty string. Other than that, empty string is returned.
     */
    public function createColCommentCommand(string $spType = 'add') {
        $comment = $this->getComment();

        if ($comment === null) {
            return '';
        }
        $table = $this->getOwner();

        if ($table === null) {
            return '';
        }
        $tableName = $table->getNormalName();
        $colName = $this->getNormalName();

        if (in_array($spType, ['update', 'add', 'drop'])) {
            $sp = "sp_".$spType."extendedproperty";
        } else {
            $sp = 'sp_addextendedproperty';
        }

        return "exec $sp\n"
                ."@name = N'MS_Description',\n"
                ."@value = '$comment',\n"
                ."@level0type = N'Schema',\n"
                ."@level0name = 'dbo',\n"
                ."@level1type = N'Table',\n"
                ."@level1name = '$tableName',\n"
                ."@level2type = N'Column',\n"
                ."@level2name = '$colName';";
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
     * <li><b>is-unique</b>: A boolean. If set to true, a unique index will 
     * be created for the column.</li>
     * <li><b>auto-update</b>: A boolean. If the column datatype is 'date', 'time' 
     * or 'datetime2' and this parameter is set to true, the time of update will 
     * change automatically without having to change it manually.</li>
     * <li><b>scale</b>: Number of numbers to the left of the decimal 
     * point. Only supported for decimal datatype.</li>
     * <li><b>comment</b>: A comment which can be used to describe the column.</li>
     * <li><b>validator</b>: A PHP function which can be used to validate user 
     * values before submitting the query to database.</li>
     * </ul>
     * 
     * @return MSSQLColumn|null The method will return an object of type 'MySQLColumn' 
     * if created. If the index 'name' is not set, the method will return null.
     */
    public static function createColObj(array $options) {
        if (isset($options['name'])) {
            return ColumnFactory::create('mssql', $options['name'], $options);
        }
    }
    /**
     * Returns column alias.
     * 
     * Note that the method will add square brackets around the alias.
     * 
     * @return string|null Name alias.
     * 
     * @since 1.0
     */
    public function getAlias() {
        $alias = parent::getAlias();

        if ($alias !== null) {
            $alias = MSSQLQuery::squareBr($alias);
        }

        return $alias;
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

            if ($dt == 'varchar' || $dt == 'nvarchar' || $dt == 'mediumtext' || 
                    $dt == 'char' || $dt == 'nchar'
                    ) {
                $retVal = substr($defaultVal, 1, strlen($defaultVal) - 2);
            } else if ($dt == 'datetime2' || $dt == 'date') {
                if (!($defaultVal == 'now' || $defaultVal == 'current_timestamp')) {
                    $retVal = substr($defaultVal, 1, strlen($defaultVal) - 2);
                } else {
                    $retVal = $defaultVal;
                }
            } else if ($dt == 'int' || $dt == 'bigint') {
                $retVal = intval($defaultVal);
            } else if ($dt == 'boolean') {
                return $defaultVal === 1 || $defaultVal === true;
            } else if ($dt == 'float' || $dt == 'decimal' || $dt == 'money') {
                $retVal = floatval($defaultVal);
            } else if ($dt == 'mixed') {
                return $defaultVal;
            }

            return $retVal;
        } else {
            return parent::getDefault();
        }
    }
    /**
     * Returns the name of the column.
     * 
     * Note that the method will add square brackets around the name.
     * 
     * @return string The name of the column.
     * 
     * @since 1.0
     */
    public function getName() : string {
        return MSSQLQuery::squareBr(parent::getName());
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
    public function getPHPType() : string {
        $colType = $this->getDatatype();

        if ($colType == 'boolean') {
            $isNullStr = '';
        } else {
            $isNullStr = $this->isNull() ? '|null' : '';
        }

        if ($colType == 'int' || $colType == 'bit' || $colType == 'bigint') {
            return 'int'.$isNullStr;
        } else if ($colType == 'decimal' || $colType == 'float' || $colType == 'money') {
            return 'float'.$isNullStr;
        } else if ($colType == 'boolean' || $colType == 'bool') {
            return 'bool'.$isNullStr;
        } else if ($colType == 'varchar' || $colType == 'nvarchar'
                || $colType == 'datetime2' || $colType == 'date'
                || $colType == 'nchar' || $colType == 'binary' || $colType == 'varbinary') {
            return 'string'.$isNullStr;
        } else {
            return parent::getPHPType().$isNullStr;
        }
    }
    /**
     * Returns the value of the property 'isAutoUpdate'.
     * 
     * @return boolean If the column type is 'datetime' or 'timestamp' and the 
     * column is set to auto update in case of update query, the method will 
     * return true. Default return value is false.
     * 
     * @since 1.0
     */
    public function isAutoUpdate() : bool {
        return $this->isAutoUpdate;
    }
    /**
     * Checks if the column represents an identity column or not.
     * 
     * Identity column only applies to int and bigint data types.
     * 
     * @return bool If the column is set as an identity, the method will
     * return true. False if not. Default is false.
     */
    public function isIdentity () : bool {
        return $this->isIdintity;
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
    public function setAutoUpdate(bool $bool) {
        if ($this->getDatatype() == 'datetime2' || $this->getDatatype() == 'date') {
            $this->isAutoUpdate = $bool;
        }
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
     */
    public function setDatatype(string $type) {
        parent::setDatatype($type);

        if (!($this->getDatatype() == 'int' || $this->getDatatype() == 'bigint')) {
            $this->isIdintity = false;
        }
    }
    /**
     * Sets the default value for the column to use in case of insert.
     * 
     * For integer data type, the passed value must be an integer. For string 
     * , the passed value must be a string. If the datatype 
     * is 'datetime2', the default will be set to current time and date 
     * if non-null value is passed (the value which is returned by the 
     * function date('Y-m-d H:i:s). If the passed 
     * value is a date string in the format 'YYYY-MM-DD HH:MM:SS', then it 
     * will be set to the given value. If the passed 
     * value is a date string in the format 'YYYY-MM-DD', then the default 
     * will be set to 'YYYY-MM-DD 00:00:00'. If 
     * null is passed, it implies that no default value will be used.
     * 
     * @param mixed $default The default value which will be set.
     * 
     * @since 1.0
     */
    public function setDefault($default) {
        if ($this->getDatatype() == 'mixed' && $default !== null) {
            $default .= '';
        }
        parent::setDefault($this->cleanValue($default));
        $type = $this->getDatatype();

        if (($type == 'datetime2' || $type == 'date') && $this->getDefault() !== null && strlen($this->getDefault()) == 0) {
            parent::setDefault(null);
        }
    }
    /**
     * Sets the value of the property which is used to check if the column
     * represents an identity or not.
     * 
     * @param bool $bool True to set the column as identity column. False
     * to set as non-identity.
     */
    public function setIsIdentity(bool $bool) {
        $dType = $this->getDatatype();

        if ($dType == 'int' || $dType == 'bigint') {
            $this->isIdintity = $bool;
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
    public function setScale(int $val) : bool {
        $type = $this->getDatatype();

        if ($type == 'decimal') {
            $size = $this->getSize();

            if ($size != 0 && $val >= 0 && ($size - $val > 0)) {
                return parent::setScale($val);
            }
        }

        return false;
    }

    private function _cleanValueHelper($val) {
        $colDatatype = $this->getDatatype();
        $cleanedVal = null;
        $valType = gettype($val);
        
        if ($val === null) {
            return null;
        } else if ($colDatatype == 'int' || $colDatatype == 'bigint') {
            $cleanedVal = intval($val);
        } else if ($colDatatype == 'boolean') {
            if ($val === true) {
                $cleanedVal = 1;
            } else {
                $cleanedVal = 0;
            }
        } else if ($colDatatype == 'decimal' || $colDatatype == 'float' || $colDatatype == 'double') {
            $cleanedVal = floatval($val);
        } else if ($colDatatype == 'varchar' || $colDatatype == 'nvarchar' 
                || $colDatatype == 'char' || $colDatatype == 'nchar') {
            
            $cleanedVal = filter_var(str_replace('@!@', "''", addslashes(str_replace("'", '@!@', $val))));
        // It is not secure if not escaped without connection
        // Think about multi-byte strings
        // At minimum, just sanitize the value using default filter plus
        //escaping special characters
        // The @!@ used as replacement for single qut since MSSQL
        // use it as escape character
        } else if ($colDatatype == 'datetime2' || $colDatatype == 'date') {
            if ($val != 'now' && $val != 'current_timestamp') {
                $cleanedVal = $this->_dateCleanUp($val);
            } else {
                $cleanedVal = $val;
            }
        } else if ($colDatatype == 'mixed') {
            
            
            if ($valType == 'string') {
                $cleanedVal =  filter_var(addslashes($val));
            } else if ($valType == 'double') {
                $cleanedVal = "'".floatval($val)."'";
            } else if ($valType == 'boolean') {
                if ($val === true) {
                    $cleanedVal = 1;
                } else {
                    $cleanedVal = 0;
                }
            } else {
                $cleanedVal = $val;
            }
        } else {
            $cleanedVal = $val;
        }
        $retVal = call_user_func($this->getCustomCleaner(), $val, $cleanedVal);

        if ($retVal !== null && (($colDatatype == 'mixed' && $valType == 'string')|| $colDatatype == 'varchar' || $colDatatype == 'nvarchar' 
                || $colDatatype == 'char' || $colDatatype == 'nchar')) {
            if ($colDatatype == 'nchar' || $colDatatype == 'nvarchar' || $colDatatype == 'mixed') {
                return "N'".$retVal."'";
            }

            return "'".$retVal."'";
        }

        return $retVal;
    }
    private function _dateCleanUp($val) {
        $trimmed = strtolower(trim($val));
        $cleanedVal = '';

        if ($trimmed == 'current_timestamp' || $trimmed == 'now()' || $trimmed == 'now') {
            $cleanedVal = 'getdate()';
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
            if ($colDataType == 'boolean') {
                if ($this->getDefault() === true) {
                    return 'default 1 ';
                } else {
                    return 'default 0 ';
                }
            } else if ($colDataType == 'datetime2' || $colDataType == 'time' || $colDataType == 'date') {
                if ($colDefault == 'now' || $colDefault == 'current_timestamp') {
                    return 'default getdate() ';
                } else {
                    return 'default '.$this->cleanValue($colDefault).' ';
                }
            } else if ($colDataType == 'mixed') {
                return "default ". $colDefault." ";
            } else {
                return 'default '.$this->cleanValue($colDefault).' ';
            }
        }
    }


    private function _firstColPart() {
        $retVal = MSSQLQuery::squareBr($this->getName()).' ';
        $colDataTypeSq = MSSQLQuery::squareBr($this->getDatatype());
        $colDataType = $this->getDatatype();

        if ($colDataType == 'varchar' || $colDataType == 'nvarchar'
                || $colDataType == 'char' || $colDataType == 'nchar'
                || $colDataType == 'binary' || $colDataType == 'varbinary') {
            $retVal .= $colDataTypeSq.'('.$this->getSize().') ';
        } else if ($colDataType == 'boolean') {
                $retVal .= '[bit] ';
        } else if ($colDataType == 'decimal') {
            if ($this->getSize() != 0) {
                $retVal .= $colDataTypeSq.'('.$this->getSize().','.$this->getScale().') ';
            } else {
                $retVal .= $colDataTypeSq.'(18,0) ';
            }
        } else if ($colDataType == 'mixed') {
            //Treat mixed as nvarchar datatype when creating the column.
            $retVal .= MSSQLQuery::squareBr('nvarchar').'(256) ';
        } else {
            $retVal .= $colDataTypeSq.' ';
        }

        if (($colDataType == 'int' || $colDataType == 'bigint') && $this->isIdentity()) {
            $retVal .= 'identity(1,1) ';
        }

        return $retVal;
    }
    private function _nullPart() {
        $colDataType = $this->getDatatype();

        if (!$this->isNull() || $colDataType == 'boolean') {
            return 'not null ';
        } else {
            return 'null ';
        }
    }
}
