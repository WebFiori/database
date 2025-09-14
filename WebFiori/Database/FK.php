<?php
/**
 * This file is licensed under MIT License.
 * 
 * Copyright (c) 2023 Ibrahim BinAlshikh
 * 
 * For more information on the license, please visit: 
 * https://github.com/WebFiori/.github/blob/main/LICENSE
 * 
 */
namespace WebFiori\Database;

/**
 * A class that represents a foreign key.
 * 
 * A foreign key must have an owner table and a source table. The 
 * source table will contain original values and the owner is simply the table 
 * that own the key.
 * 
 * @author Ibrahim
 */
class FK extends ForeignKey {
    /**
     * An action that can be performed on update or delete.
     * 
     * @var string
     */
    const SET_NULL = 'set null';
    /**
     * An action that can be performed on update or delete.
     * 
     * @var string
     */
    const SET_DEFAULT = 'set default';
    /**
     * An action that can be performed on update or delete.
     * 
     * @var string
     */
    const NO_ACTION = 'no action';
    /**
     * An action that can be performed on update or delete.
     * 
     * @var string
     */
    const CASCADE = 'cascade';
    /**
     * An action that can be performed on update or delete.
     * 
     * @var string
     */
    const RESTRICT = 'restrict';
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
    public function __construct(string $name = 'key_name', ?Table $ownerTable = null, ?Table $sourceTable = null, array $cols = []) {
        parent::__construct($name, $ownerTable, $sourceTable, $cols);
    }
}
