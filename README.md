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
* [Usage](#usage)
  * [Connecting to Database](#connecting-to-database)
  * [Running Basic SQL Queries](#running-basic-sql-queries)
    * [Insert Query](#insert-query)
    * [Select Query](#select-query)
    * [Update Query](#update-query)
    * [Delete Query](#delete-query)

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


For every query, the table that the query will be executed on must be specified. To specyfy the table, the method `Database::table(string $tblName)`. The method will return an instance of the class `AbstractQuery`. The class `AbstractQuery` has many methods which are used to further build the query. Commonly used methods include the following:

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

This query is used to delete specific record from the database. To execute a delete query, the method `AbstractQuery::delete()`. A `where` condition should be included to delete specific record. To include a `where` condition, the method `AbstractQuery::where()` can be used.

``` php
$connection = new ConnectionInfo('mysql', 'root', '123456', 'testing_db');
$database = new Database($connection);

$database->table('posts')->delete()->where('author', 'Ibrahim');
```


