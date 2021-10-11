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
    public function __construct($name) {
        parent::__construct($name);
        $this->setSupportedTypes([
            'char',
            'nchar',
            'varchar',
            'nvarchar',
            'binary',
            'varbinary',
            'date',
            'datetime2',
            'time',
            'int',
            'money',
            'bit',
            'decimal',
            'float',
            'boolean'
        ]);
    }
    public function __toString() {
        $retVal = $this->_firstColPart();
        $retVal .= $this->_nullPart();
        $retVal .= $this->_defaultPart();
        return $retVal;
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
        $colDataType = MSSQLQuery::squareBr($this->getDatatype());
        
        if ($colDataType == 'varchar' || $colDataType == 'nvarchar'
                || $colDataType == 'char' || $colDataType == 'nchar'
                || $colDataType == 'binary' || $colDataType == 'varbinary') {
            $retVal .= $colDataType.'('.$this->getSize().') ';
        } else if ($colDataType == 'boolean') {
            $retVal .= 'bit(1) ';
        } else if ($colDataType == 'decimal') {
            if ($this->getSize() != 0) {
                $retVal .= $colDataType.'('.$this->getSize().','.$this->getScale().') ';
            } else {
                $retVal .= $colDataType.'(18,0) ';
            }
        } else {
            $retVal .= $colDataType.' ';
        }

        return $retVal;
    }
    public function asString() {
        
    }

    public function cleanValue($val) {
        
    }

}
