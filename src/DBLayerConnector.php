<?php

class DBLayerConnector
{
    /**
     * Database's name
     * @var string|null
     */
    private $dbName = null;

    /**
     * Database's hostname
     * @var string|null
     */
    private $dbHost = null;

    /**
     * Database's access password
     * @var string|null
     */
    private $dbPassword = null;

    /**
     * Database listener port
     * @var int
     */
    private $dbPort = 3306;

    /**
     * Database user name
     * @var string|null
     */
    private $dbUserName = null;

    /**
     * Schema name prefix
     * @var string
     */
    private $dbSchemaPrefix = "";

    /**
     * Pdo's connection driver
     * @var string|null
     */
    private $pdoDriver =  'mysql';
    /**
     * Sets database's name
     * @param string $databaseName
     * @throws DBLayerIncompleteConfigException
     */
    public function setDatabaseName(string $databaseName)
    {
        if (empty($this->dbName)){
            if (empty($databaseName)){
                throw new DBLayerIncompleteConfigException("Database's name cannot be empty");
            }

            $this->dbName = $databaseName;
        }
    }

    /**
     * Sets database hostname
     * @param string $databaseHostName
     * @throws DBLayerIncompleteConfigException
     */
    public function setDatabaseHostName(string $databaseHostName)
    {
        if (empty($this->dbHost)){
            if (empty($databaseHostName)){
                throw new DBLayerIncompleteConfigException("Database's hostname cannot be empty!");
            }

            $this->dbHost = $databaseHostName;
        }
    }

    /**
     * Sets database user's password.
     * @param string $databaseUserPassword
     * @throws DBLayerIncompleteConfigException
     */
    public function setUserPassword(string $databaseUserPassword)
    {
        if (empty($this->dbPassword)){
            if (empty($databaseUserPassword)){
                throw new DBLayerIncompleteConfigException("User's password cannot be empty!");
            }

            $this->dbPassword = $databaseUserPassword;
        }
    }

    /**
     * Sets database's listener port
     * @param int $databaseListenerPort
     * @throws DBLayerIncompleteConfigException
     */
    public function setDatabaseListenerPort(int $databaseListenerPort)
    {
        if ($this->dbPort === 3306){
            if (empty($databaseListenerPort)){
                throw new DBLayerIncompleteConfigException("Database's listener port must be defined!");
            }

            $this->dbPort = $databaseListenerPort;
        }
    }

    /**
     * Sets database username
     * @param string $databaseUserName
     * @throws DBLayerIncompleteConfigException
     */
    public function setDatabaseUserName(string $databaseUserName)
    {
        if (empty($this->dbUserName)){
            if (empty($databaseUserName)){
                throw new DBLayerIncompleteConfigException("Database username cannot be empty!");
            }

            $this->dbUserName = $databaseUserName;
        }
    }

    /**
     * @param string $schemaPrefix
     */
    public function setSchemaPrefix(string $schemaPrefix)
    {
        if (empty($this->dbSchemaPrefix)){
            $this->dbSchemaPrefix = $schemaPrefix;
        }
    }

    /**
     * Uses 'pdo_mysql'
     */
    public function useMysqlDriver()
    {
        $this->pdoDriver = 'mysql';
    }

    /**
     * Users 'pdo_pgsql'
     */
    public function usePgSqlDriver()
    {
        $this->pdoDriver = 'pgsql';
    }

    /**
     * Creates an connection handler
     * @return DBLayer
     * @throws DBLayerIncompleteConfigException
     * @throws DBLayerOpenConnectionException
     */
    public function connect() : DBLayer
    {
        if (empty($this->dbName)){
            throw new DBLayerIncompleteConfigException("Missed 'dbname'");
        }

        if (empty($this->dbHost)){
            throw new DBLayerIncompleteConfigException("Missed 'dbHost");
        }

        if (empty($this->dbPassword)){
            throw new DBLayerIncompleteConfigException("Missed 'dbPassword'");
        }

        if (empty($this->dbPort)){
            throw new DBLayerIncompleteConfigException("Missed 'dbPort'");
        }

        if (empty($this->dbUserName)){
            throw new DBLayerIncompleteConfigException("Missed 'dbUserName'");
        }

        $databaseConnector = new DBLayer(false);
        $databaseConnector->SetDBSettings([
            "db_name" => $this->dbName,
            "db_host" => $this->dbHost,
            "db_port" => $this->dbPort,
            "db_user" => $this->dbUserName,
            "db_pass" => $this->dbPassword,
            "db_prefix" => $this->dbSchemaPrefix,
            "db_debug" => false
        ]);

        $connectionResult = $databaseConnector->OpenConnection();

        if ($connectionResult === true){
            return $databaseConnector;
        }
        else{
            throw new DBLayerOpenConnectionException("Failed while create connection handler!");
        }

    }
}


?>