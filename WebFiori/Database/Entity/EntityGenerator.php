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
namespace WebFiori\Database\Entity;

use WebFiori\Database\Column;
use WebFiori\Database\Table;

/**
 * Entity code generator using PHP 8 features.
 * 
 * Generates immutable entities with:
 * - Constructor with named arguments
 * - Protected properties (extensible)
 * - Only getters (no setters)
 * - Proper type hints
 * 
 * @author Ibrahim
 */
class EntityGenerator {
    private string $entityName;
    private string $namespace;
    private string $path;
    private Table $table;

    /**
     * Creates a new entity generator.
     * 
     * @param Table $table The table blueprint to generate entity from
     * @param string $entityName The name of the entity class
     * @param string $path The directory path where entity will be created
     * @param string $namespace The namespace for the entity class
     */
    public function __construct(Table $table, string $entityName, string $path = __DIR__, string $namespace = '') {
        $this->table = $table;
        $this->entityName = $entityName;
        $this->path = rtrim($path, '/\\');
        $this->namespace = trim($namespace, '\\');
    }

    /**
     * Generates the entity class file.
     * 
     * @return bool True on success, false on failure
     */
    public function generate(): bool {
        $code = $this->buildClass();
        $filePath = $this->path.DIRECTORY_SEPARATOR.$this->entityName.'.php';

        return file_put_contents($filePath, $code) !== false;
    }

    /**
     * Builds the complete class code.
     * 
     * @return string The generated PHP code
     */
    private function buildClass(): string {
        $code = "<?php\n\n";

        if ($this->namespace) {
            $code .= "namespace {$this->namespace};\n\n";
        }

        $code .= "/**\n";
        $code .= " * Auto-generated immutable entity for table '{$this->table->getName()}'\n";
        $code .= " * \n";
        $code .= " * Generated on: ".date('Y-m-d H:i:s')."\n";
        $code .= " * \n";
        $code .= " * This entity uses:\n";
        $code .= " * - Protected properties (extensible)\n";
        $code .= " * - Named arguments (PHP 8+)\n";
        $code .= " * - Immutable (no setters)\n";
        $code .= " */\n";
        $code .= "class {$this->entityName} {\n";

        $code .= $this->buildConstructor();
        $code .= $this->buildGetters();

        $code .= "}\n";

        return $code;
    }

    /**
     * Builds the constructor with promoted properties.
     * 
     * @return string The constructor code
     */
    private function buildConstructor(): string {
        $code = "    public function __construct(\n";
        $params = [];

        foreach ($this->table->getCols() as $key => $col) {
            $phpType = $col->getPHPType();
            $propName = $this->toCamelCase($key);
            $nullable = $this->isNullable($col) ? '?' : '';
            $default = $this->getDefault($col);

            $params[] = "        protected {$nullable}{$phpType} \${$propName}{$default}";
        }

        $code .= implode(",\n", $params);
        $code .= "\n    ) {}\n\n";

        return $code;
    }

    /**
     * Builds getter methods for all properties.
     * 
     * @return string The getter methods code
     */
    private function buildGetters(): string {
        $code = '';

        foreach ($this->table->getCols() as $key => $col) {
            $phpType = $col->getPHPType();
            $propName = $this->toCamelCase($key);
            $methodName = 'get'.ucfirst($propName);
            $nullable = $this->isNullable($col) ? '?' : '';

            $code .= "    public function {$methodName}(): {$nullable}{$phpType} {\n";
            $code .= "        return \$this->{$propName};\n";
            $code .= "    }\n\n";
        }

        return $code;
    }

    /**
     * Gets the default value for a property.
     * 
     * @param Column $col The column to get default for
     * @return string The default value as PHP code
     */
    private function getDefault(Column $col): string {
        if ($col->isAutoInc() || $col->isNull()) {
            return ' = null';
        }

        $phpType = $col->getPHPType();
        $default = $col->getDefault();

        $typeDefaults = [
            'string' => " = ''",
            'int' => ' = 0',
            'float' => ' = 0.0',
            'bool' => ' = false'
        ];

        if ($default !== null) {
            return match ($phpType) {
                'string' => " = '" . addslashes($default) . "'",
                'int', 'float' => " = {$default}",
                'bool' => $default ? ' = true' : ' = false',
                default => ''
            };
        }

        return $typeDefaults[$phpType] ?? '';
    }

    /**
     * Checks if column should be nullable in PHP.
     * 
     * @param Column $col The column to check
     * @return bool True if nullable, false otherwise
     */
    private function isNullable(Column $col): bool {
        return $col->isNull() || $col->isAutoInc();
    }

    /**
     * Converts kebab-case to camelCase.
     * 
     * @param string $key The kebab-case string
     * @return string The camelCase string
     */
    private function toCamelCase(string $key): string {
        $parts = explode('-', $key);
        $camelCase = array_shift($parts);

        foreach ($parts as $part) {
            $camelCase .= ucfirst($part);
        }

        return $camelCase;
    }
}
