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
namespace webfiori\database\mysql;

use webfiori\database\Column;
use webfiori\database\Table;
/**
 * A class that represents MySQL table.
 *
 * @author Ibrahim
 * 
 * @version 1.0
 */
class MySQLTable extends Table {
    private $charset;
    private $engine;
    private $mysqlVnum;
    /**
     * Creates a new instance of the class.
     * 
     * This method will initialize the basic settings of the table. It will 
     * set MySQL version to 8.0, the engine to 'InnoDB' and char set to 
     * 'utf8mb4'.  
     * 
     * @param string $name The name of the table.
     * 
     * @since 1.0
     */
    public function __construct($name = 'new_table') {
        parent::__construct($name);
        $this->engine = 'InnoDB';
        $this->charset = 'utf8mb4';
        $this->mysqlVnum = '8.0';
    }
    /**
     * Adds new column to the table.
     * 
     * Note that the column will be added only if no column was found in 
     * the table which has the same name 
     * as the given column (key name and database name).
     * 
     * @param string $key The index at which the column will be added to. The name 
     * of the key can only have the following characters: [A-Z], [a-z], [0-9] 
     * and '-'.
     * 
     * @param MySQLColumn $colObj An object of type MySQLColumn. 
     * 
     * @return boolean true if the column is added. false otherwise.
     * 
     * @since 1.0
     */
    public function addColumn($key, Column $colObj) {
        if (parent::addColumn($key, $colObj)) {
            $this->_checkPKs();

            return true;
        }

        return false;
    }
    /**
     * Adds multiple columns at once.
     * 
     * @param array $colsArr An associative array. The keys will act as column 
     * key in the table. The value of the key can be an object of type 'MySQLColumn' 
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
     * <li><b>comment</b> A comment which can be used to describe the column.</li>
     * </ul>
     * 
     * @since 1.0
     */
    public function addColumns(array $colsArr) {
        $arrToAdd = [];

        foreach ($colsArr as $key => $arrOrObj) {
            if ($arrOrObj instanceof MySQLColumn) {
                $arrToAdd[$key] = $arrOrObj;
            } else {
                if (gettype($arrOrObj) == 'array') {
                    if (!isset($arrOrObj['name'])) {
                        $arrOrObj['name'] = str_replace('-', '_', $key);
                    }
                    $colObj = MySQLColumn::createColObj($arrOrObj);

                    if ($colObj instanceof MySQLColumn) {
                        $arrToAdd[$key] = $colObj;
                    }
                }
            }
        }
        parent::addColumns($arrToAdd);
    }
    /**
     * Returns the character set that is used by the table.
     * 
     * @return string The character set that is used by the table.. The default 
     * value is 'utf8'.
     * 
     * @since 1.0
     */
    public function getCharSet() {
        return $this->charset;
    }
    /**
     * Returns the value of table collation.
     * 
     * If MySQL version is '5.5' or lower, the method will 
     * return 'utf8mb4_unicode_ci'. Other than that, the method will return 
     * 'utf8mb4_unicode_520_ci'.
     * 
     * @return string Table collation.
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
     * Returns the name of the storage engine used by the table.
     * 
     * @return string The name of the storage engine used by the table. The default 
     * value is 'InnoDB'.
     * 
     * @since 1.0
     */
    public function getEngine() {
        return $this->engine;
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
        return $this->mysqlVnum;
    }
    /**
     * Returns the name of the table.
     * 
     * Note that the method will add backticks around the name.
     * 
     * @return string The name of the table. Default return value is 'new_table'.
     * 
     * @since 1.0
     */
    public function getName() {
        return MySQLQuery::backtick(parent::getName());
    }
    /**
     * Removes a column given its key.
     * 
     * @param string $key Column key.
     * 
     * @return boolean If the column was removed, the method will return true. 
     * Other than that, the method will return false.
     * 
     * @since 1.0
     */
    public function removeColByKey($key) {
        $col = parent::removeColByKey($key);

        if ($col !== null) {
            $this->_checkPKs();
        }

        return $col;
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
                    $this->mysqlVnum = $vNum;
                }
            }
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
        $queryStr = '';

        $queryStr .= 'create table if not exists '.$this->getName().' ('."\n";
        $queryStr .= $this->_createTableColumns();
        $queryStr .= ')'."\n";
        $comment = $this->getComment();

        if ($comment !== null) {
            $queryStr .= 'comment \''.$comment.'\''."\n";
        }
        $queryStr .= 'engine = '.$this->getEngine()."\n";
        $queryStr .= 'default charset = '.$this->getCharSet()."\n";

        return $queryStr.'collate = '.$this->getCollation().';';
    }

    private function _checkPKs() {
        $primaryCount = $this->getPrimaryKeyColsCount();

        if ($primaryCount > 1) {
            foreach ($this->getPrimaryKeyColsKeys() as $colKey) {
                $col = $this->getColByKey($colKey);

                if ($col->isPrimary()) {
                    $col->setIsUnique(false);
                }
            }
        } else {
            foreach ($this->getPrimaryKeyColsKeys() as $colKey) {
                $col = $this->getColByKey($colKey);

                if ($col->isPrimary()) {
                    $col->setIsUnique(true);
                }
            }
        }
    }

    private function _createTableColumns() {
        $cols = $this->getCols();
        $queryStr = '';
        $count = count($cols);
        $index = 0;

        foreach ($cols as $colObj) {
            $autoIncPart = $colObj->isAutoInc() ? ' auto_increment' : '';

            if ($index + 1 == $count) {
                $queryStr .= '    '.$colObj->asString().$autoIncPart."\n";
            } else {
                $queryStr .= '    '.$colObj->asString().$autoIncPart.",\n";
            }
            $index++;
        }

        if ($this->getPrimaryKeyColsCount() != 0) {
            $queryStr = '    '.trim($queryStr);
            $pkCols = [];

            foreach ($this->getPrimaryKeyColsKeys() as $key) {
                $pkCols[] = ''.$this->getColByKey($key)->getName().'';
            }
            $pkConstraint = ",\n".'    constraint `'.$this->getPrimaryKeyName().'` primary key ('.implode(', ', $pkCols).')';
            $queryStr .= $pkConstraint;
        }

        foreach ($this->getForignKeys() as $fkObj) {
            $sourceCols = [];

            foreach ($fkObj->getSourceCols() as $colObj) {
                $sourceCols[] = ''.$colObj->getName().'';
            }
            $targetCols = [];

            foreach ($fkObj->getOwnerCols() as $colObj) {
                $targetCols[] = ''.$colObj->getName().'';
            }
            $fkConstraint = ",\n".'    constraint `'.$fkObj->getKeyName().'` '
                    .'foreign key ('.implode(', ', $targetCols).') '
                    .'references '.$fkObj->getSourceName().' ('.implode(', ', $sourceCols).')';

            if ($fkObj->getOnUpdate() !== null) {
                $fkConstraint .= ' on update '.$fkObj->getOnUpdate();
            }

            if ($fkObj->getOnDelete() !== null) {
                $fkConstraint .= ' on delete '.$fkObj->getOnDelete();
            }
            $queryStr .= $fkConstraint;
        }

        return $queryStr."\n";
    }
}
