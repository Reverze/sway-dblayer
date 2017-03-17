# DBLayer
Wrapper for PHP' pdo driver. <br>
This packages comes from swayengine 2.

## Installation

`composer require rev/sway-dblayer`

This will install package at latest version.

## Requirements

This packages requires PDO driver (for default, bundled with PHP).

## Usage

* Create connection handler
```php
<?php
/**
* Helps to create connection credentials
 */
$dblayerConnector = new DBLayerConnector();
$dblayerConnector->setDatabaseHostName('localhost');
$dblayerConnector->setDatabaseListenerPort(3306);
$dblayerConnector->setDatabaseName('my_db');
$dblayerConnector->setDatabaseUserName('john_sky');
$dblayerConnector->setUserPassword('my_secret_password');
$dblayerConnector->setSchemaPrefix('dev');
/**
* Sets to 'pdo_mysql'
 */
$dblayerConnector->useMysqlDriver();

/**
* Connects to database and get DBLayer instance
 */
$db = $dblayerConnector->connect();
?>
```

* Fetching elements

```php
<?php

/**
* Returns all rows with given type as associative array
 */
$db->Run("SELECT * FROM %pr%my_table WHERE type = ?", 
    [ 'eg_type', PDO::PARAM_STR])->assoc();

/**
* Returns first fetched row as associative array
 */
$db->Run("SELECT * FROM %pr%my_table WHERE type = ?",
    [ 'eg_type', PDO::PARAM_STR ])->assoc(0);

/**
* Returns value under column 'column1' at first fetched row.
 */
$db->Run("SELECT column1, column_2 FROM %pr%my_table WHERE type = ?",
    [ 'eg_type', PDO::PARAM_STR ])->assoc(0, 'column1');

/**
* Executes an query and pass array with fetched rows to given anonymous function.
* Anonymous function must returns a value.
 */
$db->Run("SELECT * FROM %pr%my_table WHERE type = ?",
    [ 'eg_type', PDO::PARAM_STR ])->assoc(function($entries) {
        //do something with fetched entries       
        return $entries;
    });
?>
```







