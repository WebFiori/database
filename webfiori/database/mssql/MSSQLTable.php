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
     * A boolean which is used to indicate if create SQL query will have extended
     * properties included or not.
     * 
     * @var bool
     */
    private $withExtendedProps;
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
        $this->setWithExtendedProps(false);
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
        if ($colObj instanceof MSSQLColumn && $colObj->isIdentity() && $this->hasIdentity()) {
            return false;
        }

        return parent::addColumn($key, $colObj);
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
     * @return Table The method will return the instance at which the method
     * is called on.
     * 
     * @since 1.0
     */
    public function addColumns(array $colsArr) : Table {
        $arrToAdd = [];
        $fksArr = [];
        
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
                        
                        if (isset($arrOrObj['fk'])) {
                            $fksArr[$key] = $arrOrObj['fk'];
                            
                        }
                    }
                }
            }
        }

        parent::addColumns($arrToAdd);
        
        foreach ($fksArr as $col => $fkArr) {
            $this->addReferenceFromArray($col, $fkArr);
        }
        
        return $this;
    }
    /**
     * Returns a string which can be used to add table comment as extended 
     * property.
     * 
     * The returned SQL statement will use the procedure 'sp_[add|update|drop]extendedproperty'
     * 
     * @param string $spType The type of operation. Can be 'add', 'update' or 'drop'.
     * 
     * @return string If the comment of the table is set, the method will
     * return non-empty string. Other than that, empty string is returned.
     */
    public function createTableCommentCommand(string $spType = 'add') {
        $comment = $this->getComment();

        if ($comment === null || !$this->isWithExtendedProps()) {
            return '';
        }

        $tableName = $this->getNormalName();

        if (in_array($spType, ['update', 'add', 'drop'])) {
            $sp = "sp_".$spType."extendedproperty";
        } else {
            $sp = 'sp_addextendedproperty';
        }
        $query = "exec $sp\n"
                    ."@name = N'MS_Description',\n"
                    ."@value = '".str_replace("'", "''", $comment)."',\n"
                    ."@level0type = N'Schema',\n"
                    ."@level0name = 'dbo',\n"
                    ."@level1type = N'Table',\n"
                    ."@level1name = '$tableName';";

        if ($spType == 'add') {
            return "if not exists (select null from sys.EXTENDED_PROPERTIES where major_id = OBJECT_ID('".$tableName."') and name = N'MS_Description' and minor_id = 0)\n"
                    .$query."";
        } else if ($spType == 'update') {
            return "if exists (select null from sys.EXTENDED_PROPERTIES where major_id = OBJECT_ID('".$tableName."') and name = N'MS_Description' and minor_id = 0)\n"
                    .$query."";
        } else {
            return $query;
        }
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
    public function getName() : string {
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
        if ($this->uniqueConstName === null) {
            $this->uniqueConstName = $this->getNormalName();
        }

        return 'AK_'.$this->getNormalName();
    }
    /**
     * Checks if the table has identity column or not.
     * 
     * Note that a table is allowed to have only one column as an identity.
     * 
     * @return bool True if the table has identity column. False otherwise.
     */
    public function hasIdentity() : bool {
        foreach ($this->getCols() as $colObj) {
            if ($colObj->isIdentity()) {
                return true;
            }
        }

        return false;
    }
    /**
     * Checks if extended property will be included in in 'create table' statement.
     * 
     * @return bool True if they will be included. False if not. Default return
     * value is false.
     */
    public function isWithExtendedProps() : bool {
        return $this->withExtendedProps;
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
     * Sets the value of the property which is used to indicate if extended property
     * will be included in 'create table' statement.
     * 
     * @param bool $bool True to include it. False to not.
     */
    public function setWithExtendedProps(bool $bool) {
        $this->withExtendedProps = $bool;
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
        $queryStr .= $this->createTableColumnsString();
        $pk = $this->createPKString();

        if (strlen($pk) != 0) {
            $queryStr .= ",\n".$pk;
        }
        $fk = $this->createFKString();

        if (strlen($fk) != 0) {
            $queryStr .= ",\n".$fk;
        }
        $un = $this->createUniqueString();

        if (strlen($un) != 0) {
            $queryStr .= ",\n".$un;
        }
        $queryStr .= "\n)\n";
        $comment = $this->createTableCommentCommand();

        if (strlen($comment) != 0) {
            $queryStr .= $comment."\n";
        }
        $colsComments = $this->getAddColsComments();

        if (strlen($colsComments) != 0) {
            $queryStr .= $colsComments."\n";
        }

        return $queryStr;
    }
    private function createFKString() {
        $comma = '';
        $fkConstraint = '';

        foreach ($this->getForeignKeys() as $fkObj) {
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
    private function createPKString() {
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
    private function createTableColumnsString() {
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
    private function createUniqueString() {
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
    private function getAddColsComments() {
        $str = '';

        foreach ($this->getCols() as $colObj) {
            $comment = $colObj->createColCommentCommand();

            if (strlen($comment) != 0) {
                $str .= $comment."\n";
            }
        }

        return trim($str);
    }
}
