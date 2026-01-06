<?php
namespace WebFiori\Database\Attributes;

use ReflectionClass;
use WebFiori\Database\ColOption;
use WebFiori\Database\DataType;
use WebFiori\Database\Factory\TableFactory;
use WebFiori\Database\Table as TableClass;

class AttributeTableBuilder {
    public static function build(string $entityClass, string $dbType = 'mysql'): TableClass {
        $reflection = new ReflectionClass($entityClass);

        $tableAttr = $reflection->getAttributes(Table::class)[0] ?? null;
        if (!$tableAttr) {
            throw new InvalidAttributeException("Class $entityClass must have #[Table] attribute");
        }

        $tableConfig = $tableAttr->newInstance();
        $columns = [];
        $foreignKeys = [];

        $classColumnAttrs = $reflection->getAttributes(Column::class);

        if (!empty($classColumnAttrs)) {
            foreach ($classColumnAttrs as $columnAttr) {
                $columnConfig = $columnAttr->newInstance();
                $columnKey = $columnConfig->name ?? throw new InvalidAttributeException("Column name is required for class-level attributes");
                $columns[$columnKey] = self::columnConfigToArray($columnConfig);
            }

            foreach ($reflection->getAttributes(ForeignKey::class) as $fkAttr) {
                $foreignKeys[] = $fkAttr->newInstance();
            }
        } else {
            foreach ($reflection->getProperties() as $property) {
                $columnAttrs = $property->getAttributes(Column::class);
                if (empty($columnAttrs)) {
                    continue;
                }

                $columnConfig = $columnAttrs[0]->newInstance();
                $columnKey = self::propertyToKey($property->getName());
                $columns[$columnKey] = self::columnConfigToArray($columnConfig);

                foreach ($property->getAttributes(ForeignKey::class) as $fkAttr) {
                    $fk = $fkAttr->newInstance();
                    // For property-level FK, the local column is the property itself
                    $foreignKeys[] = ['localColumn' => $columnKey, 'config' => $fk];
                }
            }
        }

        $table = TableFactory::create($dbType, $tableConfig->name, $columns);

        if ($tableConfig->comment) {
            $table->setComment($tableConfig->comment);
        }

        // Add foreign keys
        foreach ($foreignKeys as $fk) {
            if ($fk instanceof ForeignKey) {
                // Class-level FK
                self::addForeignKey($table, $fk, $dbType);
            } else {
                // Property-level FK
                self::addPropertyForeignKey($table, $fk['localColumn'], $fk['config'], $dbType);
            }
        }

        return $table;
    }

    private static function addForeignKey(TableClass $table, ForeignKey $fk, string $dbType): void {
        $refTableName = self::resolveTableName($fk->table);
        $columnsMap = $fk->getColumnsMap();

        // Build reference columns for the referenced table
        $refColumns = [];
        $mapping = [];

        foreach ($columnsMap as $local => $ref) {
            if (is_int($local)) {
                // Simple array ['col1', 'col2'] - same name on both sides
                $local = $ref;
            }
            $refColumns[$ref] = [ColOption::TYPE => DataType::INT, ColOption::PRIMARY => true];
            $mapping[$local] = $ref;
        }

        $refTable = TableFactory::create($dbType, $refTableName, $refColumns);

        $table->addReference(
            $refTable,
            $mapping,
            $fk->name ?? 'fk_'.implode('_', array_keys($mapping)),
            $fk->onUpdate,
            $fk->onDelete
        );
    }

    private static function addPropertyForeignKey(TableClass $table, string $localColumn, ForeignKey $fk, string $dbType): void {
        $refTableName = self::resolveTableName($fk->table);
        $refColName = $fk->column ?? $localColumn;

        $refTable = TableFactory::create($dbType, $refTableName, [
            $refColName => [ColOption::TYPE => DataType::INT, ColOption::PRIMARY => true]
        ]);

        $table->addReference(
            $refTable,
            [$localColumn => $refColName],
            $fk->name ?? 'fk_'.$localColumn,
            $fk->onUpdate,
            $fk->onDelete
        );
    }

    private static function resolveTableName(string $tableOrClass): string {
        if (class_exists($tableOrClass)) {
            $reflection = new ReflectionClass($tableOrClass);
            $tableAttr = $reflection->getAttributes(Table::class)[0] ?? null;
            if ($tableAttr) {
                return $tableAttr->newInstance()->name;
            }
        }
        return $tableOrClass;
    }

    private static function columnConfigToArray(Column $config): array {
        return [
            ColOption::TYPE => $config->type,
            ColOption::NAME => $config->name,
            ColOption::SIZE => $config->size,
            ColOption::SCALE => $config->scale,
            ColOption::PRIMARY => $config->primary,
            ColOption::UNIQUE => $config->unique,
            ColOption::NULL => $config->nullable,
            ColOption::AUTO_INCREMENT => $config->autoIncrement,
            ColOption::IDENTITY => $config->identity,
            ColOption::AUTO_UPDATE => $config->autoUpdate,
            ColOption::DEFAULT => $config->default,
            ColOption::COMMENT => $config->comment,
            ColOption::VALIDATOR => $config->callback
        ];
    }

    private static function propertyToKey(string $propertyName): string {
        return strtolower(preg_replace('/([a-z])([A-Z])/', '$1-$2', $propertyName));
    }
}
