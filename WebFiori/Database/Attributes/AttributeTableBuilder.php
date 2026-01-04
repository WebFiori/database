<?php
namespace WebFiori\Database\Attributes;

use ReflectionClass;
use WebFiori\Database\ColOption;
use WebFiori\Database\DataType;
use WebFiori\Database\MsSql\MSSQLTable;
use WebFiori\Database\MySql\MySQLTable;
use WebFiori\Database\Table as TableClass;

class AttributeTableBuilder {
    public static function build(string $entityClass, string $dbType = 'mysql'): TableClass {
        $reflection = new ReflectionClass($entityClass);

        $tableAttr = $reflection->getAttributes(Table::class)[0] ?? null;

        if (!$tableAttr) {
            throw new \RuntimeException("Class $entityClass must have #[Table] attribute");
        }

        $tableConfig = $tableAttr->newInstance();

        $table = $dbType === 'mysql' 
            ? new MySQLTable($tableConfig->name) 
            : new MSSQLTable($tableConfig->name);

        if ($tableConfig->comment) {
            $table->setComment($tableConfig->comment);
        }

        $columns = [];
        $foreignKeys = [];

        // Check for class-level Column attributes
        $classColumnAttrs = $reflection->getAttributes(Column::class);

        if (!empty($classColumnAttrs)) {
            // Class-level approach: columns defined at class level
            foreach ($classColumnAttrs as $columnAttr) {
                $columnConfig = $columnAttr->newInstance();
                $columnKey = $columnConfig->name ?? throw new \RuntimeException("Column name is required for class-level attributes");

                $columns[$columnKey] = [
                    ColOption::TYPE => $columnConfig->type,
                    ColOption::NAME => $columnConfig->name,
                    ColOption::SIZE => $columnConfig->size,
                    ColOption::SCALE => $columnConfig->scale,
                    ColOption::PRIMARY => $columnConfig->primary,
                    ColOption::UNIQUE => $columnConfig->unique,
                    ColOption::NULL => $columnConfig->nullable,
                    ColOption::AUTO_INCREMENT => $columnConfig->autoIncrement,
                    ColOption::IDENTITY => $columnConfig->identity,
                    ColOption::AUTO_UPDATE => $columnConfig->autoUpdate,
                    ColOption::DEFAULT => $columnConfig->default,
                    ColOption::COMMENT => $columnConfig->comment,
                    ColOption::VALIDATOR => $columnConfig->callback
                ];
            }

            // Check for class-level ForeignKey attributes
            $classFkAttrs = $reflection->getAttributes(ForeignKey::class);

            foreach ($classFkAttrs as $fkAttr) {
                $fkConfig = $fkAttr->newInstance();
                $foreignKeys[] = [
                    'property' => $fkConfig->column,
                    'config' => $fkConfig
                ];
            }
        } else {
            // Property-level approach: columns defined on properties
            foreach ($reflection->getProperties() as $property) {
                $columnAttrs = $property->getAttributes(Column::class);

                if (empty($columnAttrs)) {
                    continue;
                }

                $columnConfig = $columnAttrs[0]->newInstance();
                $columnKey = self::propertyToKey($property->getName());

                $columns[$columnKey] = [
                    ColOption::TYPE => $columnConfig->type,
                    ColOption::NAME => $columnConfig->name,
                    ColOption::SIZE => $columnConfig->size,
                    ColOption::SCALE => $columnConfig->scale,
                    ColOption::PRIMARY => $columnConfig->primary,
                    ColOption::UNIQUE => $columnConfig->unique,
                    ColOption::NULL => $columnConfig->nullable,
                    ColOption::AUTO_INCREMENT => $columnConfig->autoIncrement,
                    ColOption::IDENTITY => $columnConfig->identity,
                    ColOption::AUTO_UPDATE => $columnConfig->autoUpdate,
                    ColOption::DEFAULT => $columnConfig->default,
                    ColOption::COMMENT => $columnConfig->comment,
                    ColOption::VALIDATOR => $columnConfig->callback
                ];

                $fkAttrs = $property->getAttributes(ForeignKey::class);

                foreach ($fkAttrs as $fkAttr) {
                    $fkConfig = $fkAttr->newInstance();
                    $foreignKeys[] = [
                        'property' => $columnKey,
                        'config' => $fkConfig
                    ];
                }
            }
        }

        $table->addColumns($columns);

        // Store table references for foreign keys
        $tableRegistry = [];

        foreach ($foreignKeys as $fk) {
            $refTableName = $fk['config']->table;
            $refColName = $fk['config']->column;

            // Create a minimal table reference if not exists
            if (!isset($tableRegistry[$refTableName])) {
                $refTable = $dbType === 'mysql' 
                    ? new MySQLTable($refTableName) 
                    : new MSSQLTable($refTableName);

                // Add the referenced column to make FK work
                $refTable->addColumns([
                    $refColName => [
                        ColOption::TYPE => DataType::INT,
                        ColOption::PRIMARY => true
                    ]
                ]);

                $tableRegistry[$refTableName] = $refTable;
            }

            $table->addReference(
                $tableRegistry[$refTableName],
                [$fk['property'] => $refColName],
                $fk['config']->name ?? 'fk_'.$fk['property'],
                $fk['config']->onUpdate,
                $fk['config']->onDelete
            );
        }

        return $table;
    }

    private static function propertyToKey(string $propertyName): string {
        return strtolower(preg_replace('/([a-z])([A-Z])/', '$1-$2', $propertyName));
    }
}
