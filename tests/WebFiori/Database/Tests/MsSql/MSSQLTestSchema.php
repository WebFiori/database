<?php
namespace WebFiori\Database\tests\mssql;

use WebFiori\Database\ColOption;
use WebFiori\Database\ConnectionInfo;
use WebFiori\Database\Database;
use WebFiori\Database\DataType;
use WebFiori\Database\mssql\MSSQLTable;
/**
 * Description of MSSQLTestSchema
 *
 * @author Ibrahim
 */
class MSSQLTestSchema extends Database {
    public function __construct() {
        parent::__construct(new ConnectionInfo('mssql','sa', '1234567890@Eu', 'testing_db', SQL_SERVER_HOST, 1433, [
            'TrustServerCertificate' => 'true'
        ]));
        
        $table00 = new MSSQLTable('users');
        $table00->setComment('This table is used to hold users info.');
        $table00->addColumns([
            'id' => [
                ColOption::TYPE => DataType::INT,
                ColOption::SIZE => 5,
                ColOption::PRIMARY => true,
                ColOption::IDENTITY => true
            ],
            'first-name' => [
                ColOption::SIZE => '15',
                ColOption::TYPE => DataType::NVARCHAR
            ],
            'last-name' => [
                ColOption::SIZE => 20,
                ColOption::TYPE => DataType::NVARCHAR
            ],
            'age' => [
                ColOption::TYPE => DataType::INT,
                ColOption::SIZE => 3
            ]
        ]);
        $this->addTable($table00);
        
        $table01 = new MSSQLTable('users_privileges');
        $table01->addColumns([
            'id' => [
                ColOption::TYPE => DataType::INT,
                ColOption::SIZE => 5,
                ColOption::PRIMARY => true,
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
        $table01->addReference($table00, ['id'], 'user_privilege_fk', 'no action', 'no action');
        $this->addTable($table01);
        
        $table02 = new MSSQLTable('users_tasks');
        $table02->setComment("A table used to hold 'users' tasks.");
        $table02->addColumns([
            'task-id' => [
                ColOption::TYPE => DataType::INT,
                ColOption::SIZE => 5,
                ColOption::PRIMARY => true,
                ColOption::IDENTITY => true
            ],
            'user-id' => [
                ColOption::TYPE => DataType::INT,
                ColOption::SIZE => '5',
                ColOption::COMMENT => "The ID of the user who must perform the 'activity'."
            ],
            'created-on' => [
                ColOption::TYPE => DataType::DATETIME2,
                ColOption::DEFAULT => 'now',
                ColOption::SIZE => 0
            ],
            'last-updated' => [
                ColOption::TYPE => DataType::DATETIME2,
                ColOption::NULL => true,
                ColOption::AUTO_UPDATE => true,
                ColOption::SIZE => 0,
                ColOption::COMMENT => 'The last time this record was updated at.'
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
        $table02->addReference($table00, ['user-id'=>'id'], 'user_task_fk', 'no action', 'no action');
        $this->addTable($table02);
        
        $table03 = new MSSQLTable('profile_pics');
        $table03->addColumns([
            'user-id' => [
                ColOption::TYPE => DataType::INT,
                ColOption::SIZE => 5,
                ColOption::PRIMARY => true
            ],
            'pic' => [
                ColOption::TYPE => 'binary',
                ColOption::SIZE => 1
            ]
        ]);
        $table03->addReference($table00, ['user-id'=>'id'], 'user_profile_pic_fk', 'no action', 'no action');
        $this->addTable($table03);
    }
}
