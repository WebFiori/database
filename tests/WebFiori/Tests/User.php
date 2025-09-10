<?php
namespace WebFiori\Tests;

class User {
    private $id;
    private $email;
    private $username;
    private $userID;
    
    public function setId($id) {
        $this->id = $id;
    }
    
    public function getId() {
        return $this->id;
    }
    
    public function setUserID($userID) {
        $this->userID = $userID;
    }
    
    public function getUserID() {
        return $this->userID;
    }
    
    public function setEmail($email) {
        $this->email = $email;
    }
    
    public function getEmail() {
        return $this->email;
    }
    
    public function setUsername($username) {
        $this->username = $username;
    }
    
    public function getUsername() {
        return $this->username;
    }
    
    public function __toString() {
        $id = $this->userID !== null ? $this->userID : '';
        return 'ID: ' . $id . ' Name:  Email: ' . $this->email . ' Username: Super User';
    }
}
