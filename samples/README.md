# Code Samples

This folder contains code samples for some use cases at which the library can be used in.

All examples are based on MySQL but there are applicable to other databases.

* [Connecting to Database](connect-to-db.php)
* [Basic Queries](basicQueries)
  * [Select Query](basicQueries/select.php)
  * [Insert Query](basicQueries/insert.php)
  * [Update Query](basicQueries/update.php)
  * [Delete Query](basicQueries/delete.php)
* [Building Database Structure](createDatabase)
  * [Blueprint 1 (Functional)](createDatabase/user-information-table.php)
  * [Blueprint 2 (Functional)](createDatabase/user-bookmarks-table.php)
  * [Blueprint 1 (OOP)](createDatabase/UserInformationTable.php)
  * [Blueprint 2 (OOP)](createDatabase/UserBookmarksTable.php)
  * [Initializing Tables](createDatabase/create-database.php)
* [Creating and Using Entity Classes](createEntity) 
  * [Entity Blueprint](createEntity/user-information-table.php)
  * [Creating Entity Class](createEntity/create-entity.php)
  * [Using Entity Class](createEntity/using-entity.php)

## How to Run Samples

First thing is to make sure that you have MySQL installed in addition to PHP. Later on, you can follow following steps:

* Clone this repo.
* Install dependencies using composer.
* Open terminal in the root folder.
* Start PHP's development server by running the command `php -S localhost:8989`.
* Open your web browser and navigate to any of the samples to execute.

For example, to run the `select` sample, navigate to http://localhost/samples/basicQueries/select.php .



