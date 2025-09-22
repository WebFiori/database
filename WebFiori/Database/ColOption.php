<?php
/**
 * This file is licensed under MIT License.
 * 
 * Copyright (c) 2024 Ibrahim BinAlshikh
 * 
 * For more information on the license, please visit: 
 * https://github.com/WebFiori/.github/blob/main/LICENSE
 * 
 */
namespace WebFiori\Database;

/**
 * A class which holds constants that represents column options.
 *
 * @author Ibrahim
 */
class ColOption {
    /**
     * An option which is used to set column datatype.
     */
    const TYPE = 'type';
    /**
     * An option which is used to set size of the column (such as varchar).
     */
    const SIZE = 'size';
    /**
     * An option which is used to indicate if the column is primary or not.
     */
    const PRIMARY = 'primary';
    /**
     * An option which is used to indicate if the column is unique or not.
     */
    const UNIQUE = 'unique';
    /**
     * An option which is used to indicate if the column is auto-increment or not (MySQL).
     */
    const AUTO_INCREMENT  = 'auto-inc';
    /**
     * An option which is used to indicate if the column is identity or not (SQL Server).
     */
    const IDENTITY = 'identity';
    /**
     * An option which is used to set a custom validation rule for column value.
     */
    const VALIDATOR = 'validator';
    /**
     * An option which is used to set a comment about the column.
     */
    const COMMENT = 'comment';
    /**
     * An option which is used to indicate if the column is auto-update on update DML statement (timestamps).
     */
    const AUTO_UPDATE = 'auto-update';
    /**
     * An option which is used to indicate if the column is nullable or not.
     */
    const NULL = 'is-null';
    /**
     * An option which is used to set default value in case no value was provided on insert DML statement.
     */
    const DEFAULT = 'default';
    /**
     * An option which is used to set column name.
     */
    const NAME = 'name';
    /**
     * An option which is used to set scale (decimal type)
     */
    const SCALE = 'scale';
    /**
     * An option which is used to add foreign key constraint to the column.
     */
    const FK = 'fk';
    /**
     * An option which is used to set foreign key name.
     */
    const FK_NAME = 'name';
    /**
     * An option which is used to set on update condition of the foreign key.
     */
    const FK_ON_UPDATE = 'on-update';
    /**
     * An option which is used to set on delete condition of the foreign key.
     */
    const FK_ON_DELETE = 'on-delete';
    /**
     * An option which is used to set source table of the foreign key.
     */
    const FK_TABLE = 'table';
    /**
     * An option which is used to set name of referenced column of the foreign key.
     */
    const FK_COL = 'col';
}
