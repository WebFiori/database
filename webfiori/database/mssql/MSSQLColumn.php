<?php
namespace webfiori\database\mssql;

use webfiori\database\Column;
/**
 * A class that represents a column in MSSQL table.
 *
 * @author Ibrahim
 * 
 * @version 1.0
 */
class MSSQLColumn extends Column {
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
    public function __construct($name = 'col', $datatype = 'nvarchar', $size = 1) {
        parent::__construct($name);
        $this->setSupportedTypes([
            'int',
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
            'boolean'
        ]);
        $this->setDatatype($datatype);

        if (!$this->setSize($size)) {
            $this->setSize(1);
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
    public function getName() {
        return MSSQLQuery::squareBr(parent::getName());
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
    public static function createColObj($options) {
        if (isset($options['name'])) {
            if (isset($options['datatype'])) {
                $datatype = $options['datatype'];
            } else  if (isset($options['type'])) {
                $datatype = $options['type'];
            } else {
                $datatype = 'nvarchar';
            }
            $col = new MSSQLColumn($options['name'], $datatype);
            $size = isset($options['size']) ? intval($options['size']) : 1;
            $col->setSize($size);

            self::_primaryCheck($col, $options);
            self::_extraAttrsCheck($col, $options);

            return $col;
        }
    }
    /**
     * 
     * @param MySQLColumn $col
     * @param array $options
     */
    private static function _extraAttrsCheck(&$col, $options) {
        $scale = isset($options['scale']) ? intval($options['scale']) : 2;
        $col->setScale($scale);

        if (isset($options['default'])) {
            $col->setDefault($options['default']);
        }

        if (isset($options['is-unique'])) {
            $col->setIsUnique($options['is-unique']);
        }

        //the 'not null' or 'null' must be specified or it will cause query 
        //or it will cause query error.
        $isNull = isset($options['is-null']) ? $options['is-null'] : false;
        $col->setIsNull($isNull);

        if (isset($options['auto-update'])) {
            $col->setAutoUpdate($options['auto-update']);
        }

        if (isset($options['comment'])) {
            $col->setComment($options['comment']);
        }

        if (isset($options['validator'])) {
            $col->setCustomFilter($options['validator']);
        }
    }
    /**
     * 
     * @param MSSQLColumn $col
     * @param array $options
     */
    private static function _primaryCheck(&$col, $options) {
        $isPrimary = isset($options['primary']) ? $options['primary'] : false;

        if (!$isPrimary) {
            $isPrimary = isset($options['is-primary']) ? $options['is-primary'] : false;
        }
        $col->setIsPrimary($isPrimary);
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

        if ($colType == 'boolean') {
            $isNullStr = '';
        } else {
            $isNullStr = $this->isNull() ? '|null' : '';
        }

        if ($colType == 'int' || $colType == 'bit') {
            return 'int'.$isNullStr;
        } else  if ($colType == 'decimal' || $colType == 'float' || $colType == 'money') {
            return 'double'.$isNullStr;
        } else  if ($colType == 'boolean') {
            return 'boolean'.$isNullStr;
        } else {
            return 'string'.$isNullStr;
        }
    }
    public function __toString() {
        $retVal = $this->_firstColPart();
        $retVal .= $this->_nullPart();
        $retVal .= $this->_defaultPart();
        return trim($retVal);
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
            } else if ($colDataType == 'datetime2' || $colDataType == 'time'
                    || $colDataType == 'date') {
                if ($colDefault == 'now' || $colDefault == 'current_timestamp') {
                    return 'default getdate() ';
                } else {
                    return 'default '.$this->cleanValue($colDefault).' ';
                }
            } else {
                return 'default '.$this->cleanValue($colDefault).' ';
            }
        }
    }
    private function _nullPart() {
        $colDataType = $this->getDatatype();

        if (!$this->isNull() || $colDataType == 'boolean') {
            return 'not null ';
        } else {
            return 'null ';
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
            $retVal .= '[bit](1) ';
        } else if ($colDataType == 'decimal') {
            if ($this->getSize() != 0) {
                $retVal .= $colDataTypeSq.'('.$this->getSize().','.$this->getScale().') ';
            } else {
                $retVal .= $colDataTypeSq.'(18,0) ';
            }
        } else {
            $retVal .= $colDataTypeSq.' ';
        }

        return $retVal;
    }
    public function asString() {
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
    
    private function _cleanValueHelper($val) {
        $colDatatype = $this->getDatatype();
        $cleanedVal = null;

        if ($val === null) {
            return null;
        } else if ($colDatatype == 'int') {
            $cleanedVal = intval($val);
        } else if ($colDatatype == 'boolean') {
            if ($val === true) {
                return 1;
            } else {
                return 0;
            }
        } else if ($colDatatype == 'decimal' || $colDatatype == 'float' || $colDatatype == 'double') {
            $cleanedVal = floatval($val);
        } else if ($colDatatype == 'varchar' || $colDatatype == 'nvarchar' 
                || $colDatatype == 'char' || $colDatatype == 'nchar') {
            
            $cleanedVal = filter_var(addslashes($val));
            // It is not secure if not escaped without connection
            // Think about multi-byte strings
            // At minimum, just sanitize the value using default filter
            
        } else if ($colDatatype == 'datetime2') {
            if ($val != 'now' && $val != 'current_timestamp') {
                $cleanedVal = $this->_dateCleanUp($val);
            } else {
                $cleanedVal = $val;
            }
        } else {
            $cleanedVal = $val;
        }
        $retVal = call_user_func($this->getCustomCleaner(), $val, $cleanedVal);

        if ($retVal !== null && ($colDatatype == 'varchar' || $colDatatype == 'nvarchar' 
                || $colDatatype == 'char' || $colDatatype == 'nchar')) {
            return "'".$retVal."'";
        }

        return $retVal;
    }
    private function _dateCleanUp($val) {
        $trimmed = strtolower(trim($val));
        $cleanedVal = '';

        if ($trimmed == 'current_timestamp') {
            $cleanedVal = 'current_timestamp';
        } else if ($trimmed == 'now()') {
            $cleanedVal = 'now()';
        } else if ($this->_validateDateAndTime($trimmed)) {
            $cleanedVal = '\''.$trimmed.'\'';
        } else if ($this->_validateDate($trimmed)) {
            $cleanedVal = '\''.$trimmed.' 00:00:00\'';
        }

        return $cleanedVal;
    }
    /**
     * 
     * @param type $date
     */
    private function _validateDate($date) {
        if (strlen($date) == 10) {
            $split = explode('-', $date);

            if (count($split) == 3) {
                $year = intval($split[0]);
                $month = intval($split[1]);
                $day = intval($split[2]);

                return $year > 1969 && $month > 0 && $month < 13 && $day > 0 && $day < 32;
            }
        }

        return false;
    }
    /**
     * Checks if a date-time string is valid or not.
     * @param string $date A date string in the format 'YYYY-MM-DD HH:MM:SS'.
     * @return boolean If the string represents correct date and time, the 
     * method will return true. False if it is not valid.
     */
    private function _validateDateAndTime($date) {
        $trimmed = trim($date);

        if (strlen($trimmed) == 19) {
            $dateAndTime = explode(' ', $trimmed);

            if (count($dateAndTime) == 2) {
                return $this->_validateDate($dateAndTime[0]) && $this->_validateTime($dateAndTime[1]);
            }
        }

        return false;
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
        parent::setDefault($this->cleanValue($default));
        $type = $this->getDatatype();

        if (($type == 'datetime2' ) && strlen($this->getDefault()) == 0 && $this->getDefault() !== null) {
            parent::setDefault(null);
        }
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

                
            } else if ($dt == 'datetime2') {
                if (!($defaultVal == 'now' || $defaultVal == 'current_timestamp')) {
                    $retVal = substr($defaultVal, 1, strlen($defaultVal) - 2);
                } else {
                    $retVal = $defaultVal;
                }
            } else  if ($dt == 'int') {
                $retVal = intval($defaultVal);
            } else  if ($dt == 'boolean') {
                return $defaultVal === 1 || $defaultVal === true;
            } else if ($dt == 'float' || $dt == 'decimal' || $dt == 'money') {
                $retVal = floatval($defaultVal);
            }
            return $retVal;
        } else {
            return parent::getDefault();
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

        if ($type == 'decimal') {
            $size = $this->getSize();

            if ($size != 0 && $val >= 0 && ($size - $val > 0)) {
                return parent::setScale($val);
            }
        }

        return false;
    }
    /**
     * 
     * @param type $time
     */
    private function _validateTime($time) {
        if (strlen($time) == 8) {
            $split = explode(':', $time);

            if (count($split) == 3) {
                $hours = intval($split[0]);
                $minutes = intval($split[1]);
                $sec = intval($split[2]);

                return $hours >= 0 && $hours <= 23 && $minutes >= 0 && $minutes < 60 && $sec >= 0 && $sec < 60;
            }
        }

        return false;
    }
}
