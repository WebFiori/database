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
namespace webfiori\database;

/**
 * A class that represents a foreign key.
 * 
 * A foreign key must have an owner table and a source table. The 
 * source table will contain original values and the owner is simply the table 
 * that own the key.
 * 
 * @author Ibrahim
 * 
 * @deprecated Use the class FK.
 */
class ForeignKey {
    /**
     * An array of allowed conditions for 'on delete' and 'on update'.
     * The array have the following strings:
     * <ul>
     * <li>set null</li>
     * <li>restrict</li>
     * <li>set default</li>
     * <li>no action</li>
     * <li>cascade</li>
     * </ul>
     * @var array 
     * @since 1.0 
     */
    const CONDITIONS = [
        'set null','restrict','set default',
        'no action','cascade'
    ];
    private $colsMap;
    /**
     * The name of the key.
     * 
     * @var string 
     * 
     * @since 1.0 
     */
    private $keyName;
    /**
     * The 'on delete' condition.
     * 
     * @var string 
     * 
     * @since 1.0  
     */
    private $onDeleteCondition;
    /**
     * The 'on update' condition.
     * 
     * @var string 
     * 
     * @since 1.0  
     */
    private $onUpdateCondition;
    /**
     * An array that contains the names of sources columns. 
     * 
     * @var array 
     * 
     * @since 1.0
     */
    private $ownerCols;
    /**
     * The table at which the key will be added to.
     * 
     * @var Table 
     * 
     * @since 1.0
     */
    private $ownerTableObj;
    /**
     * An array that contains the names of referenced columns. 
     * 
     * @var array 
     * 
     * @since 1.0
     */
    private $sourceCols;
    /**
     * The table which the values are taken from.
     * 
     * @var Table 
     * 
     * @since 1.0
     */
    private $sourceTableObj;

    /**
     * Creates new foreign key.
     *
     * @param string $name The name of the key. It must be a string, and it's not empty.
     * Also, it must not contain any spaces or any characters other than A-Z, a-z and
     * underscore. The default value is 'key_name'.
     *
     * @param Table $ownerTable The table that will contain the key.
     *
     * @param Table $sourceTable The name of the table that contains the
     * original values.
     *
     * @param array $cols An associative array that contains the names of key
     * columns. The indices must be columns in the owner table and the values are
     * columns in the source table.
     *
     * @throws DatabaseException If one of the tables of the foreign key is not set.
     */
    public function __construct(
            string $name = 'key_name',
            Table $ownerTable = null,
            Table $sourceTable = null,
            array $cols = []) {
        $this->sourceCols = [];
        $this->ownerCols = [];

        if ($sourceTable instanceof Table) {
            $this->setSource($sourceTable);
        }

        if ($ownerTable instanceof Table) {
            $this->setOwner($ownerTable);
        }
        $this->keyName = 'key_name';
        $this->setKeyName($name);

        foreach ($cols as $k => $v) {
            $this->addReference($k, $v);
        }
    }

    /**
     * Add a column reference to the foreign key.
     *
     * Note that before using this method, the owner table and the source
     * table must be set. In addition, the two columns must have same data type.
     *
     * @param string $ownerColName The name of the column that belongs to the owner.
     * This one will take the value from source column.
     *
     * @param string $sourceColName The name of the column that belongs to the
     * source. The value of the owner column will be taken from this column. If
     * not provided, it will assume that the name of the source column is
     * the same as the owner column.
     *
     * @return bool If the reference is created, the method will return true.
     * Other than that, the method will return false.
     *
     * @throws DatabaseException If one of the tables of the foreign key is not set.
     * @since 1.0
     */
    public function addReference(string $ownerColName, string $sourceColName = null) : bool {
        if ($this->addReferenceHelper()) {
            $ownerColName = trim($ownerColName);
            $sourceColName = $sourceColName === null ? $ownerColName : trim($sourceColName);

            $sourceTbl = $this->getSource();
            $ownerTbl = $this->getOwner();

            $ownerCol = $ownerTbl->getColByKey($ownerColName);

            if ($ownerCol !== null) {
                $sourceCol = $sourceTbl->getColByKey($sourceColName);

                if ($sourceCol !== null && $sourceCol->getDatatype() == $ownerCol->getDatatype()) {
                    $this->ownerCols[$ownerColName] = $ownerCol;
                    $this->sourceCols[$sourceColName] = $sourceCol;
                    $this->colsMap[$ownerColName] = $sourceColName;

                    return true;
                }
            }
        }

        return false;
    }
    /**
     * Returns an associative array of columns keys that represents columns
     * at which the key is using.
     * 
     * @return array The indices of the array are keys of owner table columns
     * and values are keys of source table columns.
     */
    public function getColumnsMap() : array {
        return $this->colsMap;
    }
    /**
     * Returns the name of the key.
     * 
     * @return string The name of the key.
     * 
     * @since 1.0
     */
    public function getKeyName() : string {
        return $this->keyName;
    }
    /**
     * Returns the condition that will happen if the value of the column in the 
     * reference table is deleted.
     * 
     * @return string|null The on delete condition as string or null in 
     * case it is not set.
     * 
     * @since 1.0 
     */
    public function getOnDelete() {
        return $this->onDeleteCondition;
    }
    /**
     * Returns the condition that will happen if the value of the column in the 
     * reference table is updated.
     * 
     * @return string|null The on update condition as string or null in 
     * case it is not set.
     * 
     * @since 1.0 
     */
    public function getOnUpdate() {
        return $this->onUpdateCondition;
    }
    /**
     * Returns the table who owns the key.
     * 
     * The table that owns the key is simply the table that will take values 
     * from source table.
     * 
     * @return Table|null If the key owner is set, the method will return 
     * an object of type 'Table'. that represent it. If not set, 
     * the method will return null.
     * 
     * @since 1.0
     */
    public function getOwner() {
        return $this->ownerTableObj;
    }
    /**
     * Returns an associative array which contains the columns that belongs to 
     * the table that will contain the key.
     * 
     * @return array An associative array. The indices will represent columns 
     * names and the values are objects of type 'Column'.
     * 
     * @since 1.0
     */
    public function getOwnerCols() : array {
        return $this->ownerCols;
    }
    /**
     * Returns the source table.
     * 
     * The source table is simply the table that will contain 
     * original values.
     * 
     * @return Table|null If the source is set, the method will return 
     * an object of type 'Table'. that represent it. If not set, 
     * the method will return null.
     * 
     * @since 1.0
     */
    public function getSource() {
        return $this->sourceTableObj;
    }
    /**
     * Returns an associative array which contains the columns that will be 
     * referenced.
     * 
     * @return array An associative array. The indices will represent columns 
     * names and the values are objects of type 'Column'.
     * 
     * @since 1.0
     */
    public function getSourceCols() : array {
        return $this->sourceCols;
    }

    /**
     * Returns the name of the table that is referenced by the key.
     * 
     * The referenced table is simply the table that contains original values.
     * 
     * @return string The name of the table that is referenced by the key. If 
     * it is not set, the method will return empty string.
     * 
     * @since 1.0
     */
    public function getSourceName() : string {
        $source = $this->getSource();

        if ($source !== null) {
            return $source->getName();
        }

        return '';
    }
    /**
     * Removes a column from the key given owner column name.
     * 
     * @param string $ownerColName The name of the owner column name.
     * 
     * @return bool If a column which has the given name was found and removed, 
     * the method will return true. Other than that, the method will return false.
     * 
     * @since 1.0
     */
    public function removeReference(string $ownerColName) : bool {
        $trimmed = trim($ownerColName);
        $colIndex = 0;

        foreach ($this->getOwnerCols() as $k => $v) {
            if ($k == $trimmed) {
                $sourceIndex = 0;

                foreach ($this->getSourceCols() as $sK => $sV) {
                    if ($sourceIndex == $colIndex) {
                        unset($this->sourceCols[$sK]);
                        unset($this->ownerCols[$k]);

                        return true;
                    }
                    $sourceIndex++;
                }
            }
            $colIndex++;
        }

        return false;
    }
    /**
     * Sets the name of the key.
     * 
     * @param string $name The name of the key. A valid key name must follow the 
     * following rules:
     * <ul>
     * <li>Must be non-empty string.</li>
     * <li>First character must not be a number.</li>
     * <li>Can only contain the following characters: [A-Z], [a-z], [0-9] and 
     * underscore.</li>
     * </ul>
     * 
     * @return bool|string true if the name of the key is set. The method will 
     * return the constant ForeignKey::INV_KEY_NAME in 
     * case if the given key name is invalid.
     * 
     * @since 1.0
     */
    public function setKeyName(string $name) : bool {
        $trim = trim($name);

        if ($this->validateAttr($trim)) {
            $this->keyName = $trim;

            return true;
        }

        return false;
    }
    /**
     * Sets the value of the property $onUpdateCondition.
     * 
     * @param string $val A value from the array ForeignKey::CONDITIONS. 
     * If the given value is null, the condition will be set to null.
     * 
     * @since 1.0
     */
    public function setOnDelete(string $val) {
        $fix = strtolower(trim($val));

        if (in_array($fix, self::CONDITIONS)) {
            $this->onDeleteCondition = $fix;
        } else {
            $this->onDeleteCondition = null;
        }
    }
    /**
     * Sets the value of the property $onUpdateCondition.
     * 
     * @param string $val A value from the array ForeignKey::CONDITIONS. 
     * If the given value is null, the condition will be set to null.
     * 
     * @since 1.0
     */
    public function setOnUpdate(string $val) {
        $fix = strtolower(trim($val));

        if (in_array($fix, self::CONDITIONS)) {
            $this->onUpdateCondition = $fix;
        } else if ($val == null) {
            $this->onUpdateCondition = null;
        }
    }
    /**
     * Sets the table who owns the key.
     * 
     * The table that owns the key is simply the table that will take values 
     * from source table.
     * 
     * @param Table $table An object of type 'Table'.
     * 
     * @since 1.0
     */
    public function setOwner(Table $table) {
        $this->ownerTableObj = $table;
        $this->ownerCols = [];
    }
    /**
     * Sets the source table that will be referenced.
     * 
     * The source table is simply the table that will contain 
     * original values.
     * 
     * @param Table $table An object of type 'Table'.
     * 
     * @since 1.0
     */
    public function setSource(Table $table) {
        $this->sourceTableObj = $table;
        $this->sourceCols = [];
    }

    /**
     * @throws DatabaseException
     */
    private function addReferenceHelper(): bool {
        if ($this->getOwner() !== null) {
            if ($this->getSource() !== null) {
                return true;
            } else {
                throw new DatabaseException('Source table of the foreign key is not set.');
            }
        } else {
            throw new DatabaseExceptiont('Owner table of the foreign key is not set.');
        }
    }
    /**
     * A method that is used to validate the names of the key attributes (such as source column 
     * name or source table name).
     * 
     * @param string $trimmed The string to validate. It must be a string, and it's not empty.
     * Also, it must not contain any spaces or any characters other than A-Z, a-z and
     * underscore.
     * 
     * @return bool true if the given parameter is valid. false in 
     * case if the given parameter is invalid.
     * 
     * @since 1.0
     */
    private function validateAttr(string $trimmed) : bool {
        $len = strlen($trimmed);

        if ($len != 0 && strpos($trimmed, ' ') === false) {
            for ($x = 0 ; $x < $len ; $x++) {
                $ch = $trimmed[$x];

                if ($x == 0 && ($ch >= '0' && $ch <= '9')) {
                    return false;
                }

                if (!($ch == '_' || ($ch >= 'a' && $ch <= 'z') || ($ch >= 'A' && $ch <= 'Z') || ($ch >= '0' && $ch <= '9'))) {
                    return false;
                }
            }

            return true;
        }

        return false;
    }
}
