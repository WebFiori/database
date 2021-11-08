<?php
namespace webfiori\database\mssql;

use webfiori\database\Table;
/**
 * A class that represents MSSQL table.
 *
 * @author Ibrahim
 * 
 * @version 1.0
 */
class MSSQLTable extends Table {
    private $uniqueConstName;
    /**
     * Creates a new instance of the class.
     * 
     * @param string $name The name of the table. If empty string is given, 
     * the value 'new_table' will be used as default.
     * 
     * @since 1.0
     */
    public function __construct($name = 'new_table') {
        parent::__construct($name);
    }
    /**
     * Adds multiple columns at once.
     * 
     * @param array $colsArr An associative array. The keys will act as column 
     * key in the table. The value of the key can be an object of type 'MSSQLColumn' 
     * or be an associative array of column options. The available options 
     * are: 
     * <ul>
     * <li><b>name</b>: The name of the column in the database. If not provided, 
     * the name of the key will be used but with every '-' replaced by '_'.</li>
     * <li><b>datatype</b>: The datatype of the column.  If not provided, 'varchar' 
     * will be used. Note that the value 'type' can be used as an 
     * alias to this index.</li>
     * <li><b>size</b>: Size of the column (if datatype does support size). 
     * If not provided, 1 will be used.</li>
     * <li><b>default</b>: A default value for the column if its value 
     * is not present in case of insert.</li>
     * <li><b>is-null</b>: A boolean. If the column allows null values, this should 
     * be set to true. Default is false.</li>
     * <li><b>is-primary</b>: A boolean. It must be set to true if the column 
     * represents a primary key. Note that the column will be set as unique 
     * once its set as a primary.</li>
     * <li><b>is-unique</b>: A boolean. If set to true, a unique index will 
     * be created for the column.</li>
     * <li><b>auto-update</b>: A boolean. If the column datatype is 
     * 'datetime' or similar type and this parameter is set to true, the time of update will 
     * change automatically without having to change it manually.</li>
     * <li><b>scale</b>: Number of numbers to the left of the decimal 
     * point. Only supported for decimal datatype.</li>
     * </ul>
     * 
     * @since 1.0
     */
    public function addColumns(array $colsArr) {
        $arrToAdd = [];

        foreach ($colsArr as $key => $arrOrObj) {
            if ($arrOrObj instanceof MSSQLColumn) {
                $arrToAdd[$key] = $arrOrObj;
            } else {
                if (gettype($arrOrObj) == 'array') {
                    if (!isset($arrOrObj['name'])) {
                        $arrOrObj['name'] = str_replace('-', '_', $key);
                    }
                    $colObj = MSSQLColumn::createColObj($arrOrObj);

                    if ($colObj instanceof MSSQLColumn) {
                        $arrToAdd[$key] = $colObj;
                    }
                }
            }
        }
        parent::addColumns($arrToAdd);
    }
    /**
     * Returns the name of the table.
     * 
     * Note that the method will add square brackets around the name.
     * 
     * @return string The name of the table. Default return value is 'new_table'.
     * 
     * @since 1.0
     */
    public function getName() {
        return MSSQLQuery::squareBr(parent::getName());
    }
    /**
     * Returns the name of the unique constraint.
     * 
     * @return string The name of the unique constraint. If it is not set, 
     * the method will return the name of the table prefixed with the 
     * string 'AF_' as constraint name.
     * 
     * @since 1.0
     */
    public function getUniqueConstraintName() {
        if (strlen($this->uniqueConstName) == 0) {
            return 'AK_'.$this->getNormalName();
        }

        return $this->uniqueConstName;
    }
    /**
     * Sets the name of the unique constraint.
     * 
     * @param string $name The name of the unique constraint. Must be non-empty
     * string.
     * 
     * @since 1.0
     */
    public function setUniqueConstraintName($name) {
        $trimmed = trim($name);

        if (strlen($trimmed) != 0) {
            $this->uniqueConstName = $trimmed;
        }
    }
    /**
     * Returns SQL query which can be used to create the table.
     * 
     * @return string A string that represents SQL query which can be used 
     * to create the table.
     * 
     * @since 1.0
     */
    public function toSQL() {
        $queryStr = "if not exists (select * from sysobjects where name='".$this->getNormalName()."' and xtype='U')\n";
        $queryStr .= 'create table '.$this->getName()." (\n";
        $queryStr .= $this->_createTableColumns();
        $pk = $this->_createPK();

        if (strlen($pk) != 0) {
            $queryStr .= ",\n".$pk;
        }
        $fk = $this->_createFK();

        if (strlen($fk) != 0) {
            $queryStr .= ",\n".$fk;
        }
        $un = $this->_createUnique();

        if (strlen($un) != 0) {
            $queryStr .= ",\n".$un;
        }
        $queryStr .= "\n)\n";

        return $queryStr;
    }
    private function _createFK() {
        $comma = '';
        $fkConstraint = '';
        
        foreach ($this->getForignKeys() as $fkObj) {
            $fkConstraint .= $comma;
            $sourceCols = [];

            foreach ($fkObj->getSourceCols() as $colObj) {
                $sourceCols[] = ''.$colObj->getName().'';
            }
            $targetCols = [];

            foreach ($fkObj->getOwnerCols() as $colObj) {
                $targetCols[] = ''.$colObj->getName().'';
            }
            $fkConstraint .= "    constraint ".$fkObj->getKeyName().' '
                    .'foreign key ('.implode(', ', $targetCols).') '
                    .'references '.$fkObj->getSourceName().' ('.implode(', ', $sourceCols).')';

            if ($fkObj->getOnUpdate() !== null) {
                $fkConstraint .= ' on update '.$fkObj->getOnUpdate();
            }

            if ($fkObj->getOnDelete() !== null) {
                $fkConstraint .= ' on delete '.$fkObj->getOnDelete();
            }
            $comma = ",\n";
        }

        return $fkConstraint;
    }
    private function _createPK() {
        if ($this->getPrimaryKeyColsCount() != 0) {
            $queryStr = "    constraint ".$this->getPrimaryKeyName().' primary key clustered(';
            $pkCols = [];

            foreach ($this->getPrimaryKeyColsKeys() as $key) {
                $pkCols[] = $this->getColByKey($key)->getName();
            }
            $queryStr .= implode(", ", $pkCols);
            $queryStr .= ") on [PRIMARY]";

            return $queryStr;
        } else {
            return '';
        }
    }
    private function _createTableColumns() {
        $cols = $this->getCols();
        $queryStr = '';
        $count = count($cols);
        $index = 0;

        foreach ($cols as $colObj) {
            if ($index + 1 == $count) {
                $queryStr .= '    '.$colObj->asString();
            } else {
                $queryStr .= '    '.$colObj->asString().",\n";
            }
            $index++;
        }

        return $queryStr;
    }
    private function _createUnique() {
        $uniqueCols = $this->getUniqueCols();

        if (count($uniqueCols) != 0) {
            $queryStr = "    constraint ".$this->getUniqueConstraintName().' unique (';
            $uCols = [];

            foreach ($uniqueCols as $colObj) {
                $uCols[] = $colObj->getNormalName();
            }
            $queryStr .= implode(", ", $uCols);
            $queryStr .= ")";

            return $queryStr;
        } else {
            return '';
        }
    }
}
