<?php
namespace WebFiori\Tests\Database\MySql;

use WebFiori\Database\ConnectionInfo;
use WebFiori\Database\Database;
use WebFiori\Database\DataType;
use WebFiori\Database\ColOption;
use WebFiori\Database\MySql\MySQLTable;

/**
 * Description of MySQLTestSchema
 *
 * @author Ibrahim
 */
class MySQLTestSchema extends Database {
    public function __construct() {
        $connInfo = new ConnectionInfo('mysql','root', getenv('MYSQL_ROOT_PASSWORD'), 'testing_db', '127.0.0.1');
        $this->setConnectionInfo($connInfo);
        $table00 = new MySQLTable('users');
        $table00->addColumns([
            'id' => [
                ColOption::TYPE => DataType::INT,
                ColOption::SIZE => 5,
                'is-primary' => true,
                ColOption::AUTO_INCREMENT => true
            ],
            'first-name' => [
                ColOption::SIZE => '15',
                ColOption::TYPE => DataType::VARCHAR
            ],
            'last-name' => [
                ColOption::SIZE => 20,
                ColOption::TYPE => DataType::VARCHAR
            ],
            'age' => [
                ColOption::TYPE => DataType::INT,
                ColOption::SIZE => 3
            ]
        ]);
        $this->addTable($table00);
        
        $table01 = new MySQLTable('users_privileges');
        $table01->addColumns([
            'id' => [
                ColOption::TYPE => DataType::INT,
                ColOption::SIZE => 5,
                'is-primary' => true
            ],
            'can-edit-price' => [
                ColOption::TYPE => DataType::BOOL,
                ColOption::DEFAULT => false
            ],
            'can-change-username' => [
                ColOption::TYPE => DataType::BOOL
            ],
            'can-do-anything' => [
                ColOption::TYPE => DataType::BOOL
            ]
        ]);
        $table01->addReference($table00, ['id'], 'user_privilege_fk', 'cascade', 'restrict');
        $this->addTable($table01);
        
        $table02 = new MySQLTable('users_tasks');
        $table02->addColumns([
            'task-id' => [
                ColOption::TYPE => DataType::INT,
                ColOption::SIZE => 5,
                'is-primary' => true,
                ColOption::AUTO_INCREMENT => true
            ],
            'user-id' => [
                ColOption::TYPE => DataType::INT,
                ColOption::SIZE => '5',
                'comment' => 'The ID of the user who must perform the activity.'
            ],
            'created-on' => [
                ColOption::TYPE => DataType::TIMESTAMP,
                ColOption::DEFAULT => 'now()',
            ],
            'last-updated' => [
                ColOption::TYPE => DataType::DATETIME,
                ColOption::NULL => true,
                'auto-update' => true
            ],
            'is-finished' => [
                ColOption::TYPE => DataType::BOOL,
                ColOption::DEFAULT => false
            ],
            'details' => [
                ColOption::SIZE => 1500,
                ColOption::TYPE => DataType::VARCHAR
            ]
        ]);
        $table02->addReference($table00, ['user-id'=>'id'], 'user_task_fk', 'cascade', 'restrict');
        $table02->setComment('The tasks at which each user can have.');
        $this->addTable($table02);
        
        $table03 = new MySQLTable('profile_pics');
        $table03->addColumns([
            'user-id' => [
                ColOption::TYPE => DataType::INT,
                ColOption::SIZE => 5,
                ColOption::PRIMARY => true
            ],
            'pic' => [
                ColOption::TYPE => 'mediumblob'
            ]
        ]);
        $table03->addReference($table00, ['user-id'=>'id'], 'user_profile_pic_fk', 'cascade', 'restrict');
        $this->addTable($table03);
    }
}
