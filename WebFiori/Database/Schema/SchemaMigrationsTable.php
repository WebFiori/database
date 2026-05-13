<?php

/**
 * This file is licensed under MIT License.
 * 
 * Copyright (c) 2026-present WebFiori Framework
 * 
 * For more information on the license, please visit: 
 * https://github.com/WebFiori/.github/blob/main/LICENSE
 * 
 */
namespace WebFiori\Database\Schema;

use WebFiori\Database\Attributes\Column;
use WebFiori\Database\Attributes\Table;
use WebFiori\Database\DataType;

/**
 * Schema migrations tracking table.
 * 
 * This table stores information about applied migrations and seeders.
 * Uses class-level attributes for pure schema definition.
 */
#[Table(name: 'schema_changes')]
#[Column(
    name: 'id',
    type: DataType::INT,
    primary: true,
    autoIncrement: true,
    identity: true,
    comment: 'The unique identifier of the change.'
)]
#[Column(
    name: 'change_name',
    type: DataType::VARCHAR,
    size: 255,
    comment: 'The name of the change.'
)]
#[Column(
    name: 'type',
    type: DataType::VARCHAR,
    size: 20,
    comment: 'The type of the change (migration, seeder, etc.).'
)]
#[Column(
    name: 'db_name',
    type: DataType::VARCHAR,
    size: 255,
    comment: 'The name of the database at which the migration was applied to.'
)]
#[Column(
    name: 'applied_on',
    type: DataType::DATETIME,
    comment: 'The date and time at which the change was applied.'
)]
#[Column(
    name: 'batch',
    type: DataType::INT,
    default: 1,
    comment: 'The batch number when this change was applied.'
)]
#[Column(
    name: 'status',
    type: DataType::VARCHAR,
    size: 20,
    default: 'applied',
    comment: 'Status of the change: applied or skipped.'
)]
class SchemaMigrationsTable {
}
