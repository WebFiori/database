# Webfiori Database Abstraction Layer

Database abstraction layer of WebFiori framework.

<p style="text-align: center">
  <a href="https://github.com/WebFiori/database/actions">
    <img alt="PHP 8 Build Status" src="https://github.com/WebFiori/database/workflows/Build%20PHP%208.2/badge.svg?branch=main">
  </a>
  <a href="https://codecov.io/gh/WebFiori/database">
    <img alt="CodeCov" src="https://codecov.io/gh/WebFiori/database/branch/main/graph/badge.svg?token=cDF6CxGTFi" />
  </a>
  <a href="https://sonarcloud.io/dashboard?id=WebFiori_database">
      <img alt="Quality Checks" src="https://sonarcloud.io/api/project_badges/measure?project=WebFiori_database&metric=alert_status" />
  </a>
  <a href="https://github.com/WebFiori/database/releases">
      <img alt="Version" src="https://img.shields.io/github/release/WebFiori/database.svg?label=latest" />
  </a>
  <a href="https://packagist.org/packages/webfiori/database">
      <img alt="Downloads" src="https://img.shields.io/packagist/dt/webfiori/database?color=light-green">
  </a>
</p>

## Content 

* [Supported PHP Versions](#supported-php-versions)
* [Supported Databases](#supported-databases)
* [Features](#features)
* [Installation](#installation)
* [Usage](#usage)
  * [Connecting to Database](#connecting-to-database)
  * [Running Basic SQL Queries](#running-basic-sql-queries)
    * [Insert Query](#insert-query)
    * [Select Query](#select-query)
    * [Update Query](#update-query)
    * [Delete Query](#delete-query)
  * [Building Database Structure](#running-basic-sql-queries)
    * [Creating Table Blueprint](#creating-table-blueprint)
    * [Seeding Structure to Database](#seeding-structure-to-database)
  * [Creating Entity Classes and Using Them](#creating-entity-classes-and-using-them)
    * [Creating an Entity Class](#creating-an-entity-class) 
    * [Using Entity Class](#using-entity-class)

## Supported PHP Versions
|                                                                                           Build Status                                                                                            |
|:-------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------:|
| <a target="_blank" href="https://github.com/WebFiori/database/actions/workflows/php70.yml"><img src="https://github.com/WebFiori/database/workflows/Build%20PHP%207.0/badge.svg?branch=main"></a> |
| <a target="_blank" href="https://github.com/WebFiori/database/actions/workflows/php71.yml"><img src="https://github.com/WebFiori/database/workflows/Build%20PHP%207.1/badge.svg?branch=main"></a> |
| <a target="_blank" href="https://github.com/WebFiori/database/actions/workflows/php72.yml"><img src="https://github.com/WebFiori/database/workflows/Build%20PHP%207.2/badge.svg?branch=main"></a> |
| <a target="_blank" href="https://github.com/WebFiori/database/actions/workflows/php73.yml"><img src="https://github.com/WebFiori/database/workflows/Build%20PHP%207.3/badge.svg?branch=main"></a> |
| <a target="_blank" href="https://github.com/WebFiori/database/actions/workflows/php74.yml"><img src="https://github.com/WebFiori/database/workflows/Build%20PHP%207.4/badge.svg?branch=main"></a> |
| <a target="_blank" href="https://github.com/WebFiori/database/actions/workflows/php80.yml"><img src="https://github.com/WebFiori/database/workflows/Build%20PHP%208.0/badge.svg?branch=main"></a> |
| <a target="_blank" href="https://github.com/WebFiori/database/actions/workflows/php81.yml"><img src="https://github.com/WebFiori/database/workflows/Build%20PHP%208.1/badge.svg?branch=main"></a> |
| <a target="_blank" href="https://github.com/WebFiori/database/actions/workflows/php82.yml"><img src="https://github.com/WebFiori/database/workflows/Build%20PHP%208.2/badge.svg?branch=main"></a> |

## Supported Databases
- MySQL
- MSSQL

## Features
* Building your database structure within PHP.
* Fast and easy to use query builder.
* Database abstraction which makes it easy to migrate your system to different DBMS.

## Installation
To install the library using composer, add following dependency to `composer.json`: `"webfiori/database":"0.7.1"

## Usage

### Connecting to Database

Connecting to a database is simple. First step is to define database connection information using the class `ConnectionInfo`. Later, the instance can be used to establish a connection to the database using the class `Database`.

``` php

use webfiori\database\ConnectionInfo;
use webfiori\database\Database;

//This assumes that MySQL is installed on locahost
//and root password is set to '123456' 
//and there is a schema with name 'testing_db'
$connection = new ConnectionInfo('mysql', 'root', '123456', 'testing_db');
$database = new Database($connection);

```

### Running Basic SQL Queries

Most common SQL queries that will be executed in any relational DBMS are insert, select, update, and delete. Following examples shows how the 4 types can be constructed.


For every query, the table that the query will be executed on must be specified. To specify the table, the method `Database::table(string $tblName)`. The method will return an instance of the class `AbstractQuery`. The class `AbstractQuery` has many methods which are used to further build the query. Commonly used methods include the following:

* `AbstractQuery::insert(array $cols)`: Construct an insert query.
* `AbstractQuery::select(array $cols)`: Construct a select query.
* `AbstractQuery::update(array $cols)`: Construct an update query.
* `AbstractQuery::delete()`: Construct a delete query.
* `AbstractQuery::where($col, $val)`: Adds a condition to the query.

After building the query, the method `AbstractQuery::execute()` can be called to execute the query. If the query is a `select` query, the method will return an instance of the class `ResultSet`. The instance can be used to traverse the records that was returned by the DBMS.

#### Insert Query

Insert query is used to add records to the database. To execute an insert query, the method `AbstractQuery::insert(array $cols)`. The method accepts one parameter. The parameter is an associative array. The indices of the array are columns names and the values of the indices are the values that will be inserted.

``` php
$connection = new ConnectionInfo('mysql', 'root', '123456', 'testing_db');
$database = new Database($connection);

$database->table('posts')->insert([
    'title' => 'Super New Post',
    'author' => 'Me'
])->execute();
```

#### Select Query

A select query is used to fetch database records and use them in application logic. To execute a select query, the method `AbstractQuery::select(array $cols)`. The method accepts one optional parameter. The parameter is an array that holds the names of the columns that will be selected. In this case, the method `AbstractQuery::execute()` will return an object of type `ResultSet`. The result set will contain raw fetched records as big array that holds the actual records. Each record is stored as an associative array.

``` php
$connection = new ConnectionInfo('mysql', 'root', '123456', 'testing_db');
$database = new Database($connection);

//This assumes that we have a table called 'posts' in the database.
$resultSet = $database->table('posts')->select()->execute();

foreach ($resultSet as $record) {
    echo $record['title'];
}
```

It is possible to add a condition to the select query using the method `AbstractQuery::where()`.

``` php
$connection = new ConnectionInfo('mysql', 'root', '123456', 'testing_db');
$database = new Database($connection);

//This assumes that we have a table called 'posts' in the database.
$resultSet = $database->table('posts')
                      ->select()
                      ->where('author', 'Ibrahim')
                      ->execute();

foreach ($resultSet as $record) {
    echo $record['title'];
}
```

#### Update Query

Update query is used to update a single record  or multiple records. To execute an update query, the method `AbstractQuery::update(array $cols)`. The method accepts one parameter. The parameter is an associative array. The indices of the array are columns names and the values of the indices are the updated values. Usually, for any update query, a `where` condition will be included. To include a `where` condition, the method `AbstractQuery::where()` can be used.

``` php
$connection = new ConnectionInfo('mysql', 'root', '123456', 'testing_db');
$database = new Database($connection);

$database->table('posts')->update([
    'title' => 'Super New Post By ibrahim',
])->where('author', 'Ibrahim')
->andWhere('created-on', '2023-03-24')->execute();
```

#### Delete Query

This query is used to delete specific record from the database. To execute delete query, the method `AbstractQuery::delete()`. A `where` condition should be included to delete specific record. To include a `where` condition, the method `AbstractQuery::where()` can be used.

``` php
$connection = new ConnectionInfo('mysql', 'root', '123456', 'testing_db');
$database = new Database($connection);

$database->table('posts')->delete()->where('author', 'Ibrahim');
```

### Building Database Structure

One of the features of the library is the ability to define database structure in the source code and later, seed the created structure to create database tables. The blueprint of tables are represented by the class `Table`. The main aim of the blueprint is to make sure that data types in database are represented correctly in the source code.

> Note: Sample source code on how to build the structure can be found [here](https://github.com/WebFiori/database/tree/main/samples/createDatabase).

#### Creating Table Blueprint

Each blueprint must have following attributes defined:

* Name of the blueprint (database table name).
* Columns and thier properties such as data type.
* Any relations with other tables.

The method `Database::createBlueprint()` is used to create a table based on connected DBMS. The method will return an instance of the class `Table` which can be used to further customize the blueprint.

``` php
$database->createBlueprint('users_information')->addColumns([
    'id' => [
        'type' => 'int',
        'size' => 5,
        'primary' => true,
        'auto-inc' => true
    ],
    'first-name' => [
        'type' => 'varchar',
        'size' => 15
    ],
    'last-name' => [
        'type' => 'varchar',
        'size' => 15
    ],
    'email' => [
        'type' => 'varchar',
        'size' => 128
    ]
]);

```

> Note: It is possible to represent the blueprint using classes. A sample blueprint as class can be found [here](https://github.com/WebFiori/database/blob/main/samples/createDatabase/UserInformationTable.php).

#### Seeding Structure to Database

After creating all blueprints, a query must be structured and executed to create database tables. Building the query can be performed using the method `Database::createTables()`. After calling this method, the method `Database::execute()` must be called to create all database tables.

``` php 
//Build the query
$database->createTables();

//Just to display created query
echo '<pre>'.$database->getLastQuery().'</pre>';

//Execute
$database->execute();
```

### Creating Entity Classes and Using Them

Entity classes are classes which are based on blueprints (or tables). They can be used to map records of tables to objects. Every blueprint will have an instance of the class `EntityMapper` which can be used to create an entity class.

Entity classes that are generated using the class `EntityMapper` are special. They will have one static method with name `map()` which can automatically map a record to an instance of the entity.

#### Creating an Entity Class

First step in creating an entity is to have the blueprint at which the entity will be based on. From the bluprint, an instance of the class `EntityMapper` is generated. After having the instance, the probperties of the entity is set such as its name, namespace and where it will be created. Finally, the method `EntityMapper::create()` can be invoked to write the source code of the class.

``` php

$blueprint = $database->getTable('users_information');

//Get entity mapper
$entityMapper = $blueprint->getEntityMapper();

//Set properties of the entity
$entityMapper->setEntityName('UserInformation');
$entityMapper->setNamespace('');
$entityMapper->setPath(__DIR__);

//Create the entity. The output will be the class 'UserInformation'.
$entityMapper->create();
```

#### Using Entity Class

Entity class can me used to map a record to an object. Each entity will have a special method called `map()`. The method accepts a single paramater which is an associative array that represents fetched record.

The result set instance has one of array methods which is called `map($callback)` This method acts exactly as the function `array_map($callback, $array)`. The return value of the method is another result set with mapped records.

``` php
$resultSet = $database->table('users_information')
        ->select()
        ->execute();

$mappedSet = $resultSet->map(function (array $record) {
    return UserInformation::map($record);
});

echo '<ul>';

foreach ($mappedSet as $record) {
    //$record is an object of type UserInformation
    echo '<li>'.$record->getFirstName().' '.$record->getLastName().'</li>';
}
echo '</ul>';
```

### Transaction

Suppose that in the database there are 3 tables, `user_info`, `user_login` and `user_contact`. In order to have a full user profile, user information must exist on the 3 tables at same time. Suppose that record creation in the first and second table was a success. But due some error, the record was not created in the last table. This would cause data interty error. To resolve this, the insertion process must be rolled back. In such cases, database transactions can be of great help.

A database transaction is a unit of work which consist of multiple operations that must be performed togather. If one operation fail, then all operations must be rolled back. A transaction can be initiated using the method `Database::transaction()`. The method has two arguments, first one is the logic of the transaction as closure and second one is an optional array of arguments to be passed to the cloasure. The first parameter of the closure will be always an instance of `Database`.

If the closure returns `false` or the closure throws a `DatabaseException`, the transaction is rolled back.

``` php
$this->transaction(function (Database $db, User $toAdd) {
    $db->table('users')->insert([
        'full-name' => $toAdd->getFullName(),
        'email' => $toAdd->getEmail(),
        'created-by' => $toAdd->getCreatedBy(),
        'is-refresh' => 0
    ])->execute();

//Assuming such methods exist on calling class
    $addedUserId = $db->getLastUserID();
    $toAdd->getLoginInformation()->setUserId($addedUserId);
    $db->addUserLoginInfo($toAdd->getLoginInformation());
}, [$entity]);
```

