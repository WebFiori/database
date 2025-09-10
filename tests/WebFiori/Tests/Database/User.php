<?php
namespace WebFiori\Tests;
/**
 * Description of User
 *
 * @author Ibrahim
 */
class User {
    private $id;
    private $name;
    private $email;
    private $username;
    public function __construct() {
        $this->setUsername('Super User');
    }
    public function __toString() {
        return 'ID: '.$this->id.' Name: '.$this->name.' Email: '.$this->email.' Username: '.$this->username;
    }    
    public function setEmail($email) {
        $this->email = $email;
    }
    public function setUserID($id) {
        $this->id = $id;
    }
    public function setName($name) {
        $this->name = $name;
    }
    private function setUsername($u) {
        $this->username = $u;
    }
}
