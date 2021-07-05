<?php
namespace webfiori\database\entity;

use webfiori\json\Json;
use webfiori\json\JsonI;

/**
 * An auto-generated entity class which maps to a record in the
 * table 'users'
 **/
class UserClass implements JsonI {
    /**
     * The attribute which is mapped to the column 'age'.
     * 
     * @var int
     **/
    private $age;
    /**
     * The attribute which is mapped to the column 'first_name'.
     * 
     * @var string
     **/
    private $firstName;
    /**
     * The attribute which is mapped to the column 'id'.
     * 
     * @var int
     **/
    private $id;
    /**
     * The attribute which is mapped to the column 'last_name'.
     * 
     * @var string
     **/
    private $lastName;
    /**
     * Returns the value of the attribute 'age'.
     * 
     * The value of the attribute is mapped to the column which has
     * the name 'age'.
     * 
     * @return int The value of the attribute.
     **/
    public function getAge() {
        return $this->age;
    }
    /**
     * Returns the value of the attribute 'firstName'.
     * 
     * The value of the attribute is mapped to the column which has
     * the name 'first_name'.
     * 
     * @return string The value of the attribute.
     **/
    public function getFirstName() {
        return $this->firstName;
    }
    /**
     * Returns the value of the attribute 'id'.
     * 
     * The value of the attribute is mapped to the column which has
     * the name 'id'.
     * 
     * @return int The value of the attribute.
     **/
    public function getId() {
        return $this->id;
    }
    /**
     * Returns the value of the attribute 'lastName'.
     * 
     * The value of the attribute is mapped to the column which has
     * the name 'last_name'.
     * 
     * @return string The value of the attribute.
     **/
    public function getLastName() {
        return $this->lastName;
    }
    /**
     * Sets the value of the attribute 'age'.
     * 
     * The value of the attribute is mapped to the column which has
     * the name 'age'.
     * 
     * @param $age int The new value of the attribute.
     **/
    public function setAge($age) {
        $this->age = $age;
    }
    /**
     * Sets the value of the attribute 'firstName'.
     * 
     * The value of the attribute is mapped to the column which has
     * the name 'first_name'.
     * 
     * @param $firstName string The new value of the attribute.
     **/
    public function setFirstName($firstName) {
        $this->firstName = $firstName;
    }
    /**
     * Sets the value of the attribute 'id'.
     * 
     * The value of the attribute is mapped to the column which has
     * the name 'id'.
     * 
     * @param $id int The new value of the attribute.
     **/
    public function setId($id) {
        $this->id = $id;
    }
    /**
     * Sets the value of the attribute 'lastName'.
     * 
     * The value of the attribute is mapped to the column which has
     * the name 'last_name'.
     * 
     * @param $lastName string The new value of the attribute.
     **/
    public function setLastName($lastName) {
        $this->lastName = $lastName;
    }
    /**
     * Maps a record which is taken from the table `users` to an instance of the class.
     * 
     * @param array $record An associative array that represents the
     * record. The array should have the following indices:
     * <ul>
     * <li>id</li>
     * <li>first_name</li>
     * <li>last_name</li>
     * <li>age</li>
     * </ul>
     * 
     * @return UserClass An instance of the class.
     */
    public static function map(array $record) {
        $instance = new UserClass();
        $instance->setId($record['id']);
        $instance->setFirstName($record['first_name']);
        $instance->setLastName($record['last_name']);
        $instance->setAge($record['age']);
        
        return $instance;
    }
    /**
     * Returns an object of type 'Json' that contains object information.
     * 
     * The returned object will have the following attributes:
     * <ul>
     * <li>age</li>
     * <li>firstName</li>
     * <li>id</li>
     * <li>lastName</li>
     * </ul>
     * 
     * @return Json An object of type 'Json'.
     */
    public function toJSON() {
        $json = new Json([
            'age' => $this->getAge(),
            'firstName' => $this->getFirstName(),
            'id' => $this->getId(),
            'lastName' => $this->getLastName()
        ]);
        return $json;
    }
}
