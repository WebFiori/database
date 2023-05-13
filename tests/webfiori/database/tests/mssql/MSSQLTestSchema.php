<?php
namespace webfiori\database\tests\mssql;

use webfiori\database\Database;
use webfiori\database\ConnectionInfo;
use webfiori\database\mssql\MSSQLTable;
/**
 * Description of MSSQLTestSchema
 *
 * @author Ibrahim
 */
class MSSQLTestSchema extends Database {
    public function __construct() {
        parent::__construct(new ConnectionInfo('mssql','sa', '1234567890', 'testing_db', 'localhost/sqlexpress'));
        
        $table00 = new MSSQLTable('users');
        $table00->setComment('This table is used to hold users info.');
        $table00->addColumns([
            'id' => [
                'type' => 'int',
                'size' => 5,
                'is-primary' => true,
            ],
            'first-name' => [
                'size' => '15',
                'type' => 'nvarchar'
            ],
            'last-name' => [
                'size' => 20,
                'type' => 'nvarchar'
            ],
            'age' => [
                'type' => 'int',
                'size' => 3
            ]
        ]);
        $this->addTable($table00);
        
        $table01 = new MSSQLTable('users_privileges');
        $table01->addColumns([
            'id' => [
                'type' => 'int',
                'size' => 5,
                'is-primary' => true
            ],
            'can-edit-price' => [
                'type' => 'boolean',
                'default' => false
            ],
            'can-change-username' => [
                'type' => 'boolean'
            ],
            'can-do-anything' => [
                'type' => 'boolean'
            ]
        ]);
        $table01->addReference($table00, ['id'], 'user_privilege_fk', 'cascade', 'cascade');
        $this->addTable($table01);
        
        $table02 = new MSSQLTable('users_tasks');
        $table02->setComment("A table used to hold 'users' tasks.");
        $table02->addColumns([
            'task-id' => [
                'type' => 'int',
                'size' => 5,
                'is-primary' => true,
            ],
            'user-id' => [
                'type' => 'int',
                'size' => '5',
                'comment' => "The ID of the user who must perform the 'activity'."
            ],
            'created-on' => [
                'type' => 'datetime2',
                'default' => 'now',
            ],
            'last-updated' => [
                'type' => 'datetime2',
                'is-null' => true,
                'auto-update' => true,
                'comment' => 'The last time this record was updated at.'
            ],
            'is-finished' => [
                'type' => 'boolean',
                'default' => false
            ],
            'details' => [
                'size' => 1500,
                'type' => 'varchar'
            ]
        ]);
        $table02->addReference($table00, ['user-id'=>'id'], 'user_task_fk', 'cascade', 'cascade');
        $this->addTable($table02);
        
        $table03 = new MSSQLTable('profile_pics');
        $table03->addColumns([
            'user-id' => [
                'type' => 'int',
                'size' => 5,
                'primary' => true
            ],
            'pic' => [
                'type' => 'binary',
                'siize' => 100
            ]
        ]);
        $table03->addReference($table00, ['user-id'=>'id'], 'user_profile_pic_fk', 'cascade', 'cascade');
        $this->addTable($table03);
    }
}
