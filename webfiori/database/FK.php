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
namespace webfiori\database;

/**
 * A class that represents a foreign key.
 * 
 * A foreign key must have an owner table and a source table. The 
 * source table will contain original values and the owner is simply the table 
 * that own the key.
 * 
 * @author Ibrahim
 */
class FK extends webfiori\database\ForeignKey {
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
    public function __construct(string $name = 'key_name', Table $ownerTable = null, Table $sourceTable = null, array $cols = []) {
        parent::__construct($name, $ownerTable, $sourceTable, $cols);
    }
}
