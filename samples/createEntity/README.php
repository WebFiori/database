# Creating and Using Entity Classes

Entity classes are classes which are based on blueprints (or tables). They can be used to map records of tables to objects. Every blueprint will have an instance of the class `EntityMapper` which can be used to create an entity class.

Entity classes that are generated using the class `EntityMapper` are special. They will have one static method with name `map()` which can automatically map a record to an instance of the entity. 


Sample Sources:
* [user-information-table.php](user-information-table.php)
* [create-entity.php](create-entity.php)
* [UserInformation.php](UserInformation.php)
* [using-entity.php](using-entity.php)

First source code file contains the blueprint at which the entity will be based on. The second source code file shows how to use the blueprint in creating the entity. Third file is the output of the second file (the generated entity). Last one, shows how the entity can be used in action with result sets.
