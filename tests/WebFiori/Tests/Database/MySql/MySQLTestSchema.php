<?php
namespace WebFiori\Tests\Database\MySql;

use WebFiori\Database\ConnectionInfo;
use WebFiori\Database\Database;
use WebFiori\Database\DataType;
use WebFiori\Database\MySql\MySQLTable;

/**
 * Description of MySQLTestSchema
 *
 * @author Ibrahim
 */
class MySQLTestSchema extends Database {
    public function __construct() {
        $connInfo = new ConnectionInfo('mysql','root', '123456', 'testing_db', '127.0.0.1');
        $this->setConnectionInfo($connInfo);
        $table00 = new MySQLTable('users');
        $table00->addColumns([
            'id' => [
                'type' => DataType::INT,
                'size' => 5,
                'is-primary' => true,
                'auto-inc' => true
            ],
            'first-name' => [
                'size' => '15',
                'type' => DataType::VARCHAR
            ],
            'last-name' => [
                'size' => 20,
                'type' => DataType::VARCHAR
            ],
            'age' => [
                'type' => DataType::INT,
                'size' => 3
            ]
        ]);
        $this->addTable($table00);
        
        $table01 = new MySQLTable('users_privileges');
        $table01->addColumns([
            'id' => [
                'type' => DataType::INT,
                'size' => 5,
                'is-primary' => true
            ],
            'can-edit-price' => [
                'type' => DataType::BOOL,
                'default' => false
            ],
            'can-change-username' => [
                'type' => DataType::BOOL
            ],
            'can-do-anything' => [
                'type' => DataType::BOOL
            ]
        ]);
        $table01->addReference($table00, ['id'], 'user_privilege_fk', 'cascade', 'restrict');
        $this->addTable($table01);
        
        $table02 = new MySQLTable('users_tasks');
        $table02->addColumns([
            'task-id' => [
                'type' => DataType::INT,
                'size' => 5,
                'is-primary' => true,
                'auto-inc' => true
            ],
            'user-id' => [
                'type' => DataType::INT,
                'size' => '5',
                'comment' => 'The ID of the user who must perform the activity.'
            ],
            'created-on' => [
                'type' => DataType::TIMESTAMP,
                'default' => 'now()',
            ],
            'last-updated' => [
                'type' => DataType::DATETIME,
                'is-null' => true,
                'auto-update' => true
            ],
            'is-finished' => [
                'type' => DataType::BOOL,
                'default' => false
            ],
            'details' => [
                'size' => 1500,
                'type' => DataType::VARCHAR
            ]
        ]);
        $table02->addReference($table00, ['user-id'=>'id'], 'user_task_fk', 'cascade', 'restrict');
        $table02->setComment('The tasks at which each user can have.');
        $this->addTable($table02);
        
        $table03 = new MySQLTable('profile_pics');
        $table03->addColumns([
            'user-id' => [
                'type' => DataType::INT,
                'size' => 5,
                'primary' => true
            ],
            'pic' => [
                'type' => 'mediumblob'
            ]
        ]);
        $table03->addReference($table00, ['user-id'=>'id'], 'user_profile_pic_fk', 'cascade', 'restrict');
        $this->addTable($table03);
    }
}
