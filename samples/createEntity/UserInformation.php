<?php

use WebFiori\Database\RecordMapper;
use webfiori\json\Json;
use webfiori\json\JsonI;

/**
 * An auto-generated entity class which maps to a record in the
 * table 'users_information'
 **/
class UserInformation implements JsonI {
    /**
     * The attribute which is mapped to the column 'email'.
     * 
     * @var string
     **/
    private $email;
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
     * A mapper which is used to map a record to an instance of the class.
     * 
     * @var RecordMapper
     **/
    private static $RecordMapper;
    /**
     * Returns the value of the attribute 'email'.
     * 
     * The value of the attribute is mapped to the column which has
     * the name 'email'.
     * 
     * @return string The value of the attribute.
     **/
    public function getEmail() {
        return $this->email;
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
     * Maps a record which is taken from the table users_information to an instance of the class.
     * 
     * @param array $record An associative array that represents the
     * record. 
     * @return UserInformation An instance of the class.
     */
    public static function map(array $record) {
        if (self::$RecordMapper === null || count(array_keys($record)) != self::$RecordMapper->getSettersMapCount()) {
            self::$RecordMapper = new RecordMapper(self::class, array_keys($record));
        }

        return self::$RecordMapper->map($record);
    }
    /**
     * Sets the value of the attribute 'email'.
     * 
     * The value of the attribute is mapped to the column which has
     * the name 'email'.
     * 
     * @param $email string The new value of the attribute.
     **/
    public function setEmail($email) {
        $this->email = $email;
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
     * Returns an object of type 'Json' that contains object information.
     * 
     * The returned object will have the following attributes:
     * <ul>
     * <li>email</li>
     * <li>firstName</li>
     * <li>id</li>
     * <li>lastName</li>
     * </ul>
     * 
     * @return Json An object of type 'Json'.
     */
    public function toJSON() : Json {
        $json = new Json([
            'email' => $this->getEmail(),
            'firstName' => $this->getFirstName(),
            'id' => $this->getId(),
            'lastName' => $this->getLastName()
        ]);

        return $json;
    }
}
