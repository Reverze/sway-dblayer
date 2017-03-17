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

$db = (new DBLayerConnector())->connect();

/**
* Returns all rows with given type as associative array
 */
$db->Run("SELECT * FROM %pr%my_table WHERE type = ?", 
    [ 'eg_type', PDO::PARAM_STR])->assoc();



?>
```





