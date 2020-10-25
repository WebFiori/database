<?php
namespace webfiori\database;

use InvalidArgumentException;
/**
 * A class which is used to map a 'Table' object to an entity class.
 *
 * @author Ibrahim
 * 
 * @version 1.0
 */
class EntityMapper {
    /**
     * A string that represents the generated class.
     * 
     * @var string 
     * 
     * @since 1.0
     */
    private $classStr;
    /**
     * The class name of the entity.
     * 
     * @var string
     * 
     * @since 1.0 
     */
    private $entityName;
    /**
     * The namespace of the auto-generated entity.
     * 
     * @var string|null
     * 
     * @since 1.0 
     */
    private $entityNamespace;
    /**
     * The location of the auto-generated entity.
     * 
     * @var string|null
     * 
     * @since 1.0 
     */
    private $entityPath;
    /**
     * An attribute which is when set to true, the interface 'webfiori\json\JsonI' will 
     * be part of the generated entity.
     * 
     * @var boolean 
     */
    private $implJsonI;
    /**
     * The linked table object.
     * 
     * @var Table 
     * 
     * @since 1.0
     */
    private $table;
    /**
     * Creates new instance of the class.
     * 
     * @param Table $tableObj The table that will be mapped to an entity.
     * 
     * @param string $className The name of the class that the entity will be 
     * created in.
     * 
     * @param string $path The directory at which the entity will be created in. 
     * the default value is the constant __DIR__. 
     * 
     * @param string $namespace The namespace at which the entity will belongs 
     * to. If invalid is given, 'webfiori\database\entity' is used as default value.
     * 
     * @throws InvalidArgumentException If the given object is not of type 
     * 'webfiori\database\Table'.
     * 
     * @since 1.0
     */
    public function __construct($tableObj, $className, $path = __DIR__, $namespace = 'webfiori\\database\\entity') {
        if (!($tableObj instanceof Table)) {
            throw new InvalidArgumentException('Provided parameter is not an '
                    ."object of type 'webfiori\database\\Table'");
        }

        $this->table = $tableObj;

        if (!$this->setPath($path)) {
            $this->setPath(__DIR__);
        }

        if (!$this->setNamespace($namespace)) {
            $this->setNamespace('webfiori\\database\\entity');
        }

        if (!$this->setEntityName($className)) {
            $this->setEntityName('NewEntity');
        }

        $this->setUseJsonI(false);
    }
    /**
     * Creates the class that the table records will be mapped to.
     * 
     * @return boolean If the class is created, the method will return true. 
     * If not, the method will return false.
     * 
     * @since 1.0
     */
    public function create() {
        $this->classStr = '';
        $file = fopen($this->getAbsolutePath(), 'w+');
        $retVal = false;

        if (is_resource($file)) {
            $ns = $this->getNamespace();
            $entityClassName = $this->getEntityName();
            $this->classStr .= ""
            ."<?php\nnamespace ".$ns.";\n\n";

            if ($this->implJsonI) {
                $this->classStr .= ""
                ."use webfiori\json\Json;\n"
                ."use webfiori\json\JsonI;\n"
                ."\n";
            }
            $this->classStr .= "/**\n"
            ." * An auto-generated entity class which maps to a record in the\n"
            ." * table '".$this->getTable()->getName()."'\n"
            ." **/\n";

            if ($this->implJsonI) {
                $this->classStr .= "class ".$entityClassName." implements JsonI {\n";
            } else {
                $this->classStr .= "class ".$entityClassName." {\n";
            }
            $this->_createEntityVariables();
            $this->_createEntityMethods();
            $this->_imlpJsonX();
            $this->classStr .= "}\n";
            fwrite($file, $this->classStr);
            fclose($file);
            $retVal = true;
        }

        return $retVal;
    }
    /**
     * Returns the full path to the entity class.
     * 
     * @return string The method will return the full path to the file that contains 
     * the mapped class.
     * 
     * @since 1.0
     * 
     */
    public function getAbsolutePath() {
        return $this->getPath().DIRECTORY_SEPARATOR.$this->getEntityName().'.php';
    }
    /**
     * Returns an array that contains the names of attributes mapped from columns 
     * names.
     * 
     * Attributes names are generated based on the names of keys. For example, 
     * if we have two columns one with key 'user-id' and the second one with 
     * name 'user-PASS', then the two attributes which represents the two columns 
     * will have the names 'userId' and 'userPASS'.
     * 
     * @return array An indexed array that contains attributes names. 
     * 
     * @since 1.0
     */
    public function getAttribitesNames() {
        $keys = $this->getTable()->getColsKeys();
        $retVal = [];

        foreach ($keys as $keyName) {
            $split = explode('-', $keyName);
            $attrName = '';
            $index = 0;

            foreach ($split as $namePart) {
                if (strlen($namePart) == 1) {
                    $attrName .= strtolower($namePart);
                    $index++;
                } else {
                    if ($index != 0) {
                        $firstChar = $namePart[0];
                        $attrName .= strtoupper($firstChar).substr($namePart, 1);
                    } else {
                        $index++;
                        $attrName .= strtolower($namePart);
                    }
                }
            }
            $retVal[] = $attrName;
        }

        return $retVal;
    }
    /**
     * Returns an associative array that contains the possible names 
     * of the methods which exist in the entity class that the result 
     * of a select query on the table will be mapped to.
     * 
     * The names of the methods are constructed from the names of columns 
     * keys. For example, if the name of the column key is 'user-id', the 
     * name of setter method will be 'setUserId' and the name of setter 
     * method will be 'setUserId'.
     * 
     * @return array An associative array. The array will have two indices. 
     * The first index has the name 'setters' which will contain the names 
     * of setters and the second index is 'getters' which contains the names 
     * of the getters.
     * 
     * @since 1.0
     */
    public function getEntityMethods() {
        $keys = $this->getTable()->getColsKeys();
        $retVal = [
            'setters' => [],
            'getters' => []
        ];

        foreach ($keys as $keyName) {
            $split = explode('-', $keyName);
            $methodName = '';

            foreach ($split as $namePart) {
                if (strlen($namePart) == 1) {
                    $methodName .= strtoupper($namePart);
                } else {
                    $firstChar = $namePart[0];
                    $methodName .= strtoupper($firstChar).substr($namePart, 1);
                }
            }
            $retVal['getters'][] = 'get'.$methodName;
            $retVal['setters'][] = 'set'.$methodName;
        }

        return $retVal;
    }
    /**
     * Returns the name of the class that the table is mapped to.
     * 
     * @return string The method will return a string that represents the 
     * name of the class that the table is mapped to.
     * 
     * @since 1.0
     */
    public function getEntityName() {
        return $this->entityName;
    }
    /**
     * Returns the namespace at which the entity belongs to.
     * 
     * @return string The method will return a string that represents the name
     * of the namespace at which the entity belongs to.
     * 
     * @since 1.0
     */
    public function getNamespace() {
        return $this->entityNamespace;
    }
    /**
     * Returns the name of the directory at which the entity will be created in.
     * 
     * @return string The method will return a string that represents the name 
     * of the directory at which the entity will be created in.
     * 
     * @since 1.0
     */
    public function getPath() {
        return $this->entityPath;
    }
    /**
     * Returns an associative array that maps possible entity methods names with 
     * table columns names in the database.
     * 
     * Assuming that the table has two columns. The first one has a key = 'user-id' 
     * and the second one has a key 'password'. Also, let's assume that the first column 
     * has the name 'id' in the database and the second one has the name 'user_pass'. 
     * If this is the case, the method will return something like the following array:
     * <p>
     * <code>[<br/>
     * 'setUserId'=>'id',<br/>
     * 'setPassword'=>'user_pass'<br/>
     * ]</code>
     * </p>
     * 
     * @return array An associative array. The indices represents the names of 
     * the methods in the entity class and the values are the names of table 
     * columns as they appear in the database.
     * 
     * @since 1.0
     */
    public function getSettersMap() {
        $keys = $this->getTable()->getColsKeys();
        $retVal = [];

        foreach ($keys as $keyName) {
            $methodName = $this->mapToMethodName($keyName, 's');
            $mappedCol = trim($this->getTable()->getColByKey($keyName)->getName(), '`');
            $retVal[$methodName] = $mappedCol;
        }

        return $retVal;
    }
    /**
     * Returns the table instance which is associated with the mapper.
     * 
     * @return Table An object of type 'Table'.
     * 
     * @since 1.0
     */
    public function getTable() {
        return $this->table;
    }
    /**
     * Maps key name to entity method name.
     * 
     * @param string $colKey The name of column key such as 'user-id'.
     * 
     * @param string $type The type of the method. This one can have only two values, 
     * 's' for setter method and 'g' for getter method. Default is 'g'.
     * 
     * @return string The name of the mapped method name. If the passed column 
     * key is empty string, the method will return empty string.
     * 
     * @since 1.0
     */
    public function mapToMethodName($colKey, $type = 'g') {
        $trimmed = trim($colKey);

        if (strlen($trimmed) !== 0) {
            $split = explode('-', $trimmed);
            $methodName = '';

            foreach ($split as $namePart) {
                if (strlen($namePart) == 1) {
                    $methodName .= strtoupper($namePart);
                } else {
                    $firstChar = $namePart[0];
                    $methodName .= strtoupper($firstChar).substr($namePart, 1);
                }
            }

            if ($type == 's') {
                return 'set'.$methodName;
            } else {
                return 'get'.$methodName;
            }
        }

        return '';
    }
    /**
     * Sets the name of the entity class.
     * 
     * @param string $name A string that represents the name of the entity class.
     * 
     * @return boolean If the name is set, the method will return true. If 
     * not set, the method will return false.
     * 
     * @since 1.0
     */
    public function setEntityName($name) {
        $trimmed = trim($name);

        if ($this->_isValidClassName($trimmed)) {
            $this->entityName = $trimmed;

            return true;
        }

        return false;
    }
    /**
     * Sets the namespace at which the entity will belongs to.
     * 
     * @param string $ns A string that represents the namespace.
     * 
     * @return boolean If the namespace is set, the method will return true. If 
     * not set, the method will return false.
     * 
     * @since 1.0
     */
    public function setNamespace($ns) {
        $trimmed = trim($ns);

        if ($this->_isValidNs($trimmed)) {
            $this->entityNamespace = $trimmed;

            return true;
        }

        return false;
    }
    /**
     * Sets the location at which the entity class will be created on.
     * 
     * @param string $path A string that represents the path to the folder at 
     * which the entity will be created on.
     * 
     * @return boolean If the path is set, the method will return true. If 
     * not set, the method will return false.
     * 
     * @since 1.0
     */
    public function setPath($path) {
        if (is_dir($path)) {
            $this->entityPath = $path;

            return true;
        }

        return false;
    }
    /**
     * Sets the value of the attribute '$implJsonI'. 
     * 
     * If this attribute is set to true, the generated entity will implemented 
     * the interface 'webfiori\json\JsonI'. Not that this will make the entity class 
     * depends on the library 'Json'.
     * 
     * @param boolean $bool True to make it implement the interface JsonI and 
     * false to not.
     * 
     * @since 1.0
     */
    public function setUseJsonI($bool) {
        $this->implJsonI = $bool === true;
    }
    private function _createEntityMethods() {
        $entityAttrs = $this->getAttribitesNames();
        $attrsCount = count($entityAttrs);
        $colsTypes = $this->getTable()->getColsDatatypes();
        $colsNames = $this->getTable()->getColsNames();
        $settersGettersMap = $this->getEntityMethods();

        for ($x = 0 ; $x < $attrsCount ; $x++) {
            $colName = $colsNames[$x];
            $setterName = $settersGettersMap['setters'][$x];
            $attrName = $entityAttrs[$x];
            $phpType = $this->getTable()->getColByIndex($x)->getPHPType();
            $this->classStr .= ""
            ."    /**\n"
            ."     * Sets the value of the attribute '".$attrName."'.\n"
            ."     * \n"
            ."     * The value of the attribute is mapped to the column which has\n"
            ."     * the name '$colName'.\n"
            ."     * \n"
            ."     * @param \$$entityAttrs[$x] ".$phpType." The new value of the attribute.\n"
            ."     **/\n"
            .'    public function '.$setterName.'($'.$entityAttrs[$x].") {\n";

            if ($colsTypes[$x] == 'boolean') {
                $this->classStr .= '        $this->'.$entityAttrs[$x].' = $'.$entityAttrs[$x]." === true || $".$entityAttrs[$x]." == 'Y';\n";
            } else {
                $this->classStr .= '        $this->'.$entityAttrs[$x].' = $'.$entityAttrs[$x].";\n";
            }
            $this->classStr .= "    }\n";
            $getterName = $settersGettersMap['getters'][$x];
            $this->classStr .= ""
            ."    /**\n"
            ."     * Returns the value of the attribute '".$attrName."'.\n"
            ."     * \n"
            ."     * The value of the attribute is mapped to the column which has\n"
            ."     * the name '$colName'.\n"
            ."     * \n"
            ."     * @return ".$phpType." The value of the attribute.\n"
            ."     **/\n"
            .'    public function '.$getterName."() {\n"
            .'        return $this->'.$entityAttrs[$x].";\n"
            ."    }\n";
        }
    }
    private function _createEntityVariables() {
        $index = 0;
        $entityAttrs = $this->getAttribitesNames();

        foreach ($entityAttrs as $attrName) {
            $colObj = $this->getTable()->getColByIndex($index);
            $this->classStr .= ""
            ."    /**\n"
            ."     * The attribute which is mapped to the column '".$colObj->getName()."'.\n"
            ."     * \n"
            ."     * @var ".$colObj->getPHPType()."\n"
            ."     **/\n"
            ."    private $".$attrName.";\n";
            $index++;
        }
    }
    private function _imlpJsonX() {
        if ($this->implJsonI) {
            $this->classStr .= ""
            ."    /**\n"
            ."     * Returns an object of type 'JsonX' that contains object information.\n"
            ."     * \n"
            ."     * The returned object will have the following attributes:\n";
            $arrayStr = '';
            $attrsStr = '';
            $attributes = $this->getAttribitesNames();
            $gettersMap = $this->getEntityMethods()['getters'];
            $index = 0;
            $comma = "";

            foreach ($attributes as $attrName) {
                $arrayStr .= $comma."            '$attrName' => \$this->$gettersMap[$index]()";
                $index++;
                $comma = ",\n";
                $attrsStr .= "     * <li>$attrName</li>\n";
            }
            $this->classStr .= ""
            ."     * <ul>\n"
            ."$attrsStr"
            ."     * </ul>\n"
            ."     * \n"
            ."     * @return Json An object of type 'Json'.\n"
            ."     */\n"
            ."    public function toJSON() {\n"
            ."        \$json = new Json([\n"
            ."$arrayStr\n"
            ."        ]);\n"
            ."        return \$json;\n"
            ."    }\n";
        }
    }
    private function _isValidClassName($cn) {
        $trim = trim($cn);
        $len = strlen($cn);

        if ($len > 0) {
            for ($x = 0 ; $x < $len ; $x++) {
                $ch = $trim[$x];

                if ($x == 0 && $ch >= '0' && $ch <= '9') {
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
    private function _isValidNs($ns) {
        $trim = trim($ns);
        $len = strlen($ns);

        if ($len > 0) {
            $slashCount = 0;

            for ($x = 0 ; $x < $len ; $x++) {
                $ch = $trim[$x];

                if ($x == 0 && ($ch == '\\' || ($ch >= '0' && $ch <= '9'))) {
                    return false;
                } else {
                    if ($ch == '\\' && $slashCount > 1) {
                        return false;
                    } else {
                        if ($ch == '\\') {
                            $slashCount++;
                            continue;
                        } else {
                            if (!($ch == '_' || ($ch >= 'a' && $ch <= 'z') || ($ch >= 'A' && $ch <= 'Z') || ($ch >= '0' && $ch <= '9'))) {
                                return false;
                            }
                        }
                    }
                }
                $slashCount = 0;
            }

            return true;
        }

        return false;
    }
}
