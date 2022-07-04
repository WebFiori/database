<?php
namespace webfiori\database;
/**
 * A class which is used to map a database record to a system entity.
 *
 * @author Ibrahim
 */
class RecordMapper {
    private $settersMap;
    private $clazzName;
    /**
     * Creates new instance of the class.
     * 
     * @param string $clazz The name of the class that a record will be mapped
     * to. Usually obtained using the syntax 'Class::class'.
     * 
     * @param array $columns An array that holds the names of database table
     * columns as they appear in the database.
     */
    public function __construct(string $clazz = '', array $columns = []) {
        $this->settersMap = [];
        if (strlen(trim($clazz)) != 0) {
            $this->setClass($clazz);
        } else {
            $this->clazzName = '';
        }
        $this->extractMethodsNames($columns);
    }
    /**
     * Adds a custom method to map column value to a setter method.
     * 
     * Note that if setter was specified for the column, this method
     * will override existing one.
     * 
     * @param string $colName The name of the column as it appears in the
     * database.
     * 
     * @param string $methodName The name of the method that the column will
     * be mapped to. If not provided, the name of the column will be used to
     * generate the name of the method as follows: Replacing every space in the
     * name by underscore. Then appending the string 'set' and capitalizing
     * first letter of the name of the column and capitalizing every letter 
     * after the underscore.
     * 
     * @return type
     */
    public function addSetterMap(string $colName, $methodName = null) {
        $trimmedColName = trim(trim(trim(trim($colName, '`'), ']'), '['));
        if (strlen($trimmedColName) == 0) {
            return;
        }
        if ($methodName === null) {
            $methodName = 'set'.$this->columnNameToMethodName($trimmedColName);
        }
        $this->settersMap[$methodName] = $trimmedColName;
    }
    /**
     * Sets the class that the records will be mapped to.
     * 
     * Note that the method will throw an exception if the class
     * does not exist.
     * 
     * @param string $clazz The name of the class (including namespace).
     * 
     * @throws DatabaseException
     */
    public function setClass($clazz) {
        $trimmed = trim($clazz);
        if (class_exists($trimmed)) {
            $this->clazzName = $clazz;
        } else {
            throw new DatabaseException('Class not found: '.$clazz);
        }
    }
    /**
     * Returns the name of the class that the mapper will use to map a
     * record.
     * 
     * @return string The name of the class that the mapper will use to map a
     * record.
     */
    public function getClass() : string {
        return $this->clazzName;
    }
    /**
     * Maps a record to the specified entity class.
     * 
     * This method will simply attempt to create an instance of the specified
     * class and use setter map to set its attributes.
     * 
     * @param array $record An associative array that holds record information.
     * The indices of the array should be columns names and the values of the
     * indices are the values fetched from the database.
     * 
     * @return object|null The method will return an instance of the specified class.
     * If no class was specified, the method will return null.
     */
    public function map(array $record) {
        $instance = new $this->clazzName();
        
        foreach ($this->getSettrsMap() as $method => $colName) {
            if (is_callable([$instance, $method])) {
                try {
                    $instance->$method($record[$colName]);
                } catch (\Throwable $ex) {
                    
                }
            }
        }
      return $instance;
    }
    /**
     * Returns an array that holds the methods and each record they are mapped to.
     * 
     * @return array An associative array. The indices will represent methods
     * names and the values are columns names.
     */
    public function getSettrsMap() : array {
        return $this->settersMap;
    }
    private function extractMethodsNames($columnsNames) {
        foreach ($columnsNames as $name) {
            $this->addSetterMap($name);
        }
    }
    private function columnNameToMethodName($colName) {
        $expl = explode('_', $colName);
        $methName = '';
        for($x = 0 ; $x < count($expl) ; $x++) {
            $xStr = $expl[$x];
            $upper = strtoupper($xStr[0]);
            if (strlen($xStr) == 1) {
                $methName .= $upper;
            } else {
                $methName .= $upper.substr($xStr, 1);
            }
        }
        return $methName;
    }
}
