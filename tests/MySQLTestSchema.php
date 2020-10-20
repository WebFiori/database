<?php
namespace webfiori\database\tests;
use webfiori\database\mysql\MySQLTable;
use webfiori\database\Database;
use webfiori\database\mysql\MySQLConnection;
use webfiori\database\ConnectionInfo;

/**
 * Description of MySQLTestSchema
 *
 * @author Ibrahim
 */
class MySQLTestSchema extends Database {
    public function __construct() {
        $connInfo = new ConnectionInfo('mysql','root', '12345', 'testing_db');
        parent::__construct($connInfo);
        $table00 = new MySQLTable('users');
        $table00->addColumns([
            'id' => [
                'type' => 'int',
                'size' => 5,
                'is-primary' => true,
                'auto-inc' => true
            ],
            'first-name' => [
                'size' => '15'
            ],
            'last-name' => [
                'size' => 20
            ],
            'age' => [
                'type' => 'int',
                'size' => 3
            ]
        ]);
        $this->addTable($table00);
        
        $table01 = new MySQLTable('users_privileges');
        $table01->addColumns([
            'id' => [
                'type' => 'int',
                'size' => 5,
                'is-primary' => true
            ],
            'can-edit-price' => [
                'type' => 'bool',
                'default' => false
            ],
            'can-change-username' => [
                'type' => 'boolean'
            ],
            'can-do-anything' => [
                'type' => 'bool'
            ]
        ]);
        $table01->addReference($table00, ['id'], 'user_privilege_fk', 'cascade', 'restrict');
        $this->addTable($table01);
        
        $table02 = new MySQLTable('users_tasks');
        $table02->addColumns([
            'task-id' => [
                'type' => 'int',
                'size' => 5,
                'is-primary' => true,
                'auto-inc' => true
            ],
            'user-id' => [
                'type' => 'int',
                'size' => '5',
                'comment' => 'The ID of the user who must perform the activity.'
            ],
            'created-on' => [
                'type' => 'timestamp',
                'default' => 'now()',
            ],
            'last-updated' => [
                'type' => 'datetime',
                'is-null' => true,
                'auto-update' => true
            ],
            'is-finished' => [
                'type' => 'boolean',
                'default' => false
            ],
            'details' => [
                'size' => 1500
            ]
        ]);
        $table02->addReference($table00, ['user-id'=>'id'], 'user_task_fk', 'cascade', 'restrict');
        $table02->setComment('The tasks at which each user can have.');
        $this->addTable($table02);
    }
}
