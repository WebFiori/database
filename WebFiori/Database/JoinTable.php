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

use WebFiori\Database\MsSql\MSSQLColumn;
use WebFiori\Database\MsSql\MSSQLQuery;
use WebFiori\Database\MsSql\MSSQLTable;
use WebFiori\Database\MySql\MySQLColumn;
use WebFiori\Database\MySql\MySQLQuery;
use WebFiori\Database\MySql\MySQLTable;
/**
 * A class that represents two joined tables.
 *
 * @author Ibrahim
 * 
 * @version 1.0
 */
class JoinTable extends Table {
    /**
     * Join conditions.
     * 
     * @var Condition 
     * 
     * @since 1.0
     */
    private $joinConditions;
    /**
     * The type of the join.
     * 
     * @var string
     * 
     * @since 1.0 
     */
    private $joinType;
    /**
     * The left table of the join.
     * 
     * @var Table
     * 
     * @since 1.0 
     */
    private $left;
    /**
     * The right table of the join.
     * 
     * @var Table
     * 
     * @since 1.0 
     */
    private $right;
    /**
     * Creates new instance of the class.
     * 
     * @param Table $left The left table of the join.
     * 
     * @param Table $right The right table of the join.
     * 
     * @param string $joinType A string that represents join type such as 'left' or 
     * 'right'.
     * 
     * @param string $alias An optional alias for the table. It is simply will 
     * be set as the name of the table.
     * 
     * @since 1.0
     */
    public function __construct(Table $left, Table $right, string $joinType = 'join', string $alias = 'new_table') {
        $this->left = $left;
        $this->right = $right;
        $this->joinType = $joinType;

        parent::__construct($alias);


        $this->addColsHelper();
        $this->addColsHelper(false);
        $this->setOwner($this->getLeft()->getOwner());
    }
    /**
     * Adds a condition which could be used to join the two tables.
     * 
     * @param Condition $cond The condition.
     * 
     * @param string $joinOp This one is used to chain multiple conditions 
     * with each other. This one can have values such as 'and' or 'or'.
     * 
     * @since 1.0
     */
    public function addJoinCondition(Condition $cond, string $joinOp = 'and') {
        if ($this->joinConditions === null) {
            $this->joinConditions = $cond;
        } else {
            $newCond = new Condition($this->joinConditions, $cond, $joinOp);
            $this->joinConditions = $newCond;
        }
    }
    /**
     * Adds multiple columns at once.
     * 
     * @param array $colsArr An associative array. The keys will act as column 
     * key in the table. The value of the key can be an object of type 'MSSQLColumn' or 'MySQLColumn'
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
     */
    public function addColumns(array $colsArr) : Table {
        $arrToAdd = [];

        foreach ($colsArr as $key => $arrOrObj) {
            if ($arrOrObj instanceof Column) {
                $arrToAdd[$key] = $arrOrObj;
            } else {
                if (gettype($arrOrObj) == 'array') {
                    if (!isset($arrOrObj['name'])) {
                        $arrOrObj['name'] = str_replace('-', '_', $key);
                    }
                    if ($this->getLeft() instanceof MSSQLTable) {
                        $colObj = MSSQLColumn::createColObj($arrOrObj);
                    } else {
                        $colObj = MySQLColumn::createColObj($arrOrObj);
                    }

                    if ($colObj instanceof Column) {
                        $arrToAdd[$key] = $colObj;
                    }
                }
            }
        }

        return parent::addColumns($arrToAdd);
    }
    /**
     * Returns a string which represents the join condition of the two tables.
     * 
     * The format of the string will be similar to the following: 
     * "`left_table` join_type `right_table` [on(join_cond)]".
     * The join condition will be included only if specified.
     * 
     * @return string
     * 
     * @since 1.0
     */
    public function getJoin() : string {
        if ($this->getLeft() instanceof JoinTable) {
            $retVal = ' '.$this->getJoinType()
                .' '.$this->getRight()->getName();
        } else {
            $retVal = $this->getLeft()->getName()
                .' '.$this->getJoinType()
                .' '.$this->getRight()->getName();
        }


        if ($this->getJoinCondition() !== null) {
            $retVal .= ' on('.$this->getJoinCondition().')';
        }

        return $retVal;
    }
    /**
     * Returns the condition at which the two tables joined based on.
     * 
     * @return Condition|null The condition at which the two tables joined based on. 
     * This also can be a chain of conditions.
     * 
     * @since 1.0
     */
    public function getJoinCondition() {
        return $this->joinConditions;
    }
    /**
     * Returns a string that represents join type.
     * 
     * @return string A string such as 'left' or 'right'.
     * 
     * @since 1.0
     */
    public function getJoinType() : string {
        return $this->joinType;
    }
    /**
     * Returns the left table of the join.
     * 
     * @return Table left table of the join.
     * 
     * @since 1.0
     */
    public function getLeft() : Table {
        return $this->left;
    }
    public function getName() : string {
        $left = $this->getLeft();

        while ($left instanceof JoinTable) {
            $left = $left->getLeft();
        }

        if ($left instanceof MySQLTable) {
            return MySQLQuery::backtick($this->getNormalName());
        } else {
            if ($left instanceof MSSQLTable) {
                return MSSQLQuery::squareBr($this->getNormalName());
            }
        }

        return parent::getName();
    }
    /**
     * Returns the right table of the join.
     * 
     * @return Table right table of the join.
     * 
     * @since 1.0
     */
    public function getRight() : Table {
        return $this->right;
    }

    public function toSQL() {
    }
    private function addColsHelper(bool $left = true) {
        $prefix = $left === true ? 'left' : 'right';

        if ($left) {
            $cols = $this->getLeft()->getCols();
        } else {
            $cols = $this->getRight()->getCols();
        }

        foreach ($cols as $colKey => $colObj) {
            if ($this->hasColumnWithKey($colKey)) {
                $colKey = $prefix.'-'.$colKey;
            }
            $colObj->setWithTablePrefix(false);

            if ($colObj->getOwner() instanceof JoinTable && $colObj->getAlias() !== null) {
                $colObj->setName($colObj->getAlias());
            } else {
                if ($this->hasColumn($colObj->getNormalName())) {
                    $colObj->setAlias($prefix.'_'.$colObj->getNormalName());
                }
            }
            $this->addColumn($colKey, $this->copyCol($colObj));
        }
    }
    private function copyCol(Column $column) {
        if ($column instanceof MySQLColumn) {
            $copyCol = new MySQLColumn($column->getName(), $column->getDatatype(), $column->getSize());
        } else {
            $copyCol = new MSSQLColumn($column->getName(), $column->getDatatype(), $column->getSize());
        }
        $copyCol->setOwner($column->getOwner());
        $copyCol->setCustomFilter($column->getCustomCleaner());
        $copyCol->setIsNull($column->isNull());
        $copyCol->setAlias($column->getAlias());

        return $copyCol;
    }
}
