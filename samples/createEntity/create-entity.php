<?php

require_once '../mysql-db.php';

$database = getDatabaseInstance();

//First create the blueprint
require_once 'user-information-table.php';

//Get the blue print instance
$blueprint = $database->getTable('users_information');

//Get entity mapper
$entityMapper = $blueprint->getEntityMapper();

//Set properties of the entity
$entityMapper->setEntityName('UserInformation');
$entityMapper->setNamespace('');
$entityMapper->setPath(__DIR__);
$entityMapper->setUseJsonI(true);

//Create the entity. The output will be the class 'UserInformation'.
$entityMapper->create();

echo 'Entity class created at '.__DIR__;
