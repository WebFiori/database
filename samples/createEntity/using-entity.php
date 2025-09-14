<?php

require_once '../mysql-db.php';
require_once 'UserInformation.php';

$database = getDatabaseInstance();

$resultSet = $database->table('users_information')
        ->select()
        ->execute();

$mappedSet = $resultSet->map(function (array $record)
{
    return UserInformation::map($record);
});

echo '<ul>';

foreach ($mappedSet as $record) {
    //$record is an object of type UserInformation
    echo '<li>'.$record->getFirstName().' '.$record->getLastName().'</li>';
}
echo '</ul>';echo '</ul>';
