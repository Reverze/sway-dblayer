<?php


class DBLayer
{
    private $db_settings = null;

    private $pdo = null;
    private $pdo_statement = null;
    private $pdo_result = null;
    private $pdo_result_array = null;
    private $pdo_result_execute = null;

    private $bind_count = 1;

    /**
     * Initialize an object
     */
    public function __construct()
    {

    }

    /**
     * Clears primary fields
     */
    private function __clear_field()
    {
        $this->pdo_result = null;
        $this->pdo_result_array = null;
        $this->pdo_statement = null;
        $this->pdo_result_execute = null;
    }

    private function setDatabaseSettings($array)
    {
        if (is_array($array)){
            $required_keys = array('db_name', 'db_user', 'db_pass', 'db_port', 'db_host', 'db_debug', 'db_prefix');
            $missing_keys = 0;

            foreach ($required_keys as $key)
                if (!isset($array[$key]))
                    $missing_keys++;

            if ($missing_keys > 0)
                throw new DBLayerIncompleteConfigException('The array not have all keys');

            $this->db_settings = $array;

            return true;

        }
        else{
            throw new DBLayerInvalidArrayConfigException('The argument passed to function ' . __FUNCTION__ . ' called $array not represent an array');
        }

        return false;
    }

    public function __call(string $methodName, array $methodArguments)
    {
        switch ($methodName){
            case 'openConnection':
            case 'OpenConnection':
                return $this->openDatabaseConnection(isset($methodArguments[0]) ? $methodArguments[0] : 'mysql');
                break;
            case 'setDBSettings':
            case 'SetDBSettings':
            case 'setSettings':
                return $this->setDatabaseSettings($methodArguments[0]);
                break;
        }
    }

    /**
     * @param string $driver
     * @return bool
     * @throws DBLayerOpenConnectionException
     * @throws DBLayerPortNotNumericException
     */
    private function openDatabaseConnection(string $driver = 'mysql')
    {
        $db_name = $this->db_settings['db_name'];
        $db_host = $this->db_settings['db_host'];
        $db_pass = $this->db_settings['db_pass'];
        $db_port = $this->db_settings['db_port'];
        $db_user = $this->db_settings['db_user'];
        $db_debug = $this->db_settings['db_debug'];

        if (!is_numeric($db_port))
            throw new DBLayerPortNotNumericException("Port is not numeric. Value: " . $db_port);

        $sth = $driver . ':host=' . $db_host . ';dbname=' . $db_name . ';port=' . $db_port . ';encoding=utf8';

        try {
            $this->pdo = new PDO($sth, $db_user, $db_pass);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(PDO::MYSQL_ATTR_INIT_COMMAND, 'SET NAMES UTF8');
            $this->pdo->exec('SET NAMES utf8');

            return true;
        }
        catch (PDOException $e) {
            throw new DBLayerOpenConnectionException("Connection cannot open connection. " . $e->getMessage());
        }

        return false;
    }

    public function Query($statement)
    {
        $this->__clear_field();
        $query = str_replace("%pr%", $this->db_settings['db_prefix'], $statement);
        $st = $this->pdo->query($query);

        if ($st === false)
            return false;

        if ($st instanceof PDOStatement){
            $this->pdo_statement = $st;
            $this->pdo_result_array = $st->fetchAll(PDO::FETCH_ASSOC);
        }

        return $this;
    }

    public function Run()
    {
        $numPassedArgs = func_num_args();

        if ($numPassedArgs) {
            $sqlQuery = null;
            $bindsArgsCounter = 0;

            for ($argumentPointer = 0; $argumentPointer < $numPassedArgs; $argumentPointer++) {
                if (gettype(func_get_arg($argumentPointer)) === 'string') {
                    $sqlQuery = func_get_arg($argumentPointer);
                    break;
                }
            }

            if (strlen($sqlQuery) > 0)
                $this->Prepare($sqlQuery);

            for ($argumentPointer = 0; $argumentPointer < $numPassedArgs; $argumentPointer++) {
                if (is_array(func_get_arg($argumentPointer))) {
                    if (isset(func_get_arg($argumentPointer)[0]) &&
                        isset(func_get_arg($argumentPointer)[1])) {
                        $this->bind(func_get_arg($argumentPointer)[0], func_get_arg($argumentPointer)[1]);
                    }
                }
            }

            return $this->Exec();
        }
        else {
            throw new SWEmptyException("No arguments passed to function");
        }

        return $this;
    }

    public function Exec($statement = null)
    {
        if ($this->pdo_statement instanceof PDOStatement AND $statement === null){
            $this->pdo_result = null;
            $ex = $this->pdo_statement->execute();

            try{
                $this->pdo_result_array = $this->pdo_statement->fetchAll(PDO::FETCH_ASSOC);
            }
            catch (PDOException $eww){
            }
            $this->pdo_result= $ex;

            $this->bind_count = 1;

            return $this;
        }
        else{
            if ($statement !== null){
                $query = str_replace("%pr%", $this->db_settings['db_prefix'], $statement);
                $res = $this->pdo->exec($query);
                $this->pdo_result = $res;
                $this->bind_count = 1;
                return $this;
            }
        }

        $this->bind_count = 1;
        $this->pdo_result = false;
        return $this;
    }

    public function Prepare($statement, $driver_options = array())
    {
        $query = str_replace("%pr%", $this->db_settings['db_prefix'], $statement);
        try{
            $st = $this->pdo->prepare($query, $driver_options);
            if ($st === false){
                $this->pdo_result = false;
                return $this;
            }

            if ($st instanceof PDOStatement){
                $this->__clear_field();
                $this->pdo_statement = $st;
                return $this;
            }
        }
        catch (PDOException $e){
            $this->pdo_result = false;
            return $this;
        }

        $this->pdo_result = false;
        return $this;
    }

    public function bindColumn($column, $param, $type = null, $maxlen = null)
    {
        if ($this->pdo_statement instanceof PDOStatement){
            $this->pdo_result = $this->pdo_statement->bindCloumn($column, $param, $type, $maxlen);
            return $this;
        }
        else{
            $this->pdo_result = false;
            return $this;
        }
    }

    public function bindParam($parameter, $variable, $data_type = PDO::PARAM_STR, $length = null, $driver_options = null)
    {
        if ($this->pdo_statement instanceof PDOStatement){
            $this->pdo_result = $this->pdo_statement->bindParam($parameter, $variable, $data_type, $length, $driver_options);
            return $this;
        }
        else{
            $this->pdo_result = false;
            return $this;
        }
    }

    public function bindValue($parameter, $value, $data_type = PDO::PARAM_STR)
    {
        if ($this->pdo_statement instanceof PDOStatement){
            $this->pdo_result = $this->pdo_statement->bindValue($parameter, $value, $data_type);
            return $this;
        }
        else{
            $this->pdo_result = false;
            return $this;
        }
    }

    public function bind($value, $data_type = PDO::PARAM_STR)
    {
        $res = $this->bindValue($this->bind_count, $value, $data_type);
        $this->bind_count++;
        return $this;
    }

    public function columnCount()
    {
        if ($this->pdo_statement instanceof PDOStatement){
            $this->pdo_result = $this->pdo_statement->columnCount();
            return $this;
        }
        else{
            $this->pdo_result = false;
            return $this;
        }
    }

    public function assoc($firstIndex = null, $secondIndex = null, $thirdIndex = null)
    {
        if (is_int($firstIndex) or is_string($firstIndex) && $secondIndex === null
            && $thirdIndex === null)
            return ($this->count() ? $this->fetchAll(PDO::FETCH_ASSOC)->Result()[$firstIndex] : array());
        else if ((is_int($firstIndex) or is_string($firstIndex)) &&
            (is_int($secondIndex) or is_string($secondIndex))  && $thirdIndex === null)
            return ($this->count() ? $this->fetchAll(PDO::FETCH_ASSOC)->Result()[$firstIndex][$secondIndex] : array());
        else if ((is_int($firstIndex) or is_string($firstIndex)) && (is_int($secondIndex) or is_string($secondIndex))
            && (is_int($thirdIndex) or is_string($thirdIndex)))
            return ($this->count() ? $this->fetchAll(PDO::FETCH_ASSOC)->Result()[$firstIndex][$secondIndex][$thirdIndex] : array());
        else if (is_callable($firstIndex) && $secondIndex === null && $thirdIndex === null)
            return $firstIndex(($this->count() ? $this->fetchAll(PDO::FETCH_ASSOC)->Result() : array()));
        else
            return ($this->count() ? $this->fetchAll(PDO::FETCH_ASSOC)->Result() : array());
    }

    public function affected()
    {
        if ($this->count() > 0)
            return true;
        else
            return false;
    }

    public function fetch($fetch_style = PDO::FETCH_ASSOC, $cursor_orientation = PDO::FETCH_ORI_NEXT, $cursor_offset = 0)
    {
        if ($this->pdo_statement instanceof PDOStatement){
            $this->pdo_result = $this->pdo_statement->fetch($fetch_style, $cursor_orientation, $cursor_offset);
            return $this;
        }
        else{
            $this->pdo_result = false;
            return $this;
        }
    }

    public function fetchAll($fetch_style = PDO::FETCH_ASSOC)
    {
        if ($this->pdo_statement instanceof PDOStatement){
            $this->pdo_result =  $this->pdo_result_array;
            return $this;
        }
        else{
            $this->pdo_result = false;
            return $this;
        }
    }

    public function rowCount()
    {
        if ($this->pdo_statement instanceof PDOStatement){
            $this->pdo_result =  $this->pdo_statement->rowCount();
            return $this;
        }
        else{
            $this->pdo_result = false;
            return $this;
        }
    }

    public function count()
    {
        if ($this->pdo_statement instanceof PDOStatement){
            return $this->pdo_statement->rowCount();
        }
        else {
            return 0;
        }
    }

    public function nextRowset()
    {
        if ($this->pdo_statement instanceof PDOStatement){
            $this->pdo_result = $this->nextRowset ();
            return $this;
        }
        else{
            $this->pdo_result = false;
            return $this;
        }
    }

    public function quote($string, $parameter_type = PDO::PARAM_STR)
    {
        $this->pdo_result = $this->pdo->quote($string, $parameter_type);
        return $this;
    }

    public function errorCode()
    {
        if ($this->pdo_statement instanceof PDOStatement){
            $this->pdo_result =  $this->pdo_statement->errorCode();
            return $this;
        }
        if ($this->pdo instanceof PDO){
            $this->pdo_result = $this->pdo->errorCode();
            return $this;
        }
    }

    public function errorInfo()
    {
        if ($this->pdo_statement instanceof PDOStatement){
            $this->pdo_result = $this->pdo_statement->errorInfo();
            return $this;
        }
        if ($this->pdo instanceof PDO){
            $this->pdo_result = $this->pdo->errorInfo();
            return $this;
        }
    }

    public function setAttribute($attribute, $value)
    {
        if ($this->pdo_statement instanceof PDOStatement){
            $this->pdo_result =  $this->pdo_statement->setAttribute($attribute, $value);
            return $this;
        }
        if ($this->pdo instanceof PDO){
            $this->pdo_result = $this->pdo->setAttribute($attribute, $value);
            return $this;
        }
    }

    public function getAttribute($attribute)
    {
        if ($this->pdo_statement instanceof PDOStatement){
            $this->pdo_result = $this->pdo_statement->getAttribute($attribute);
            return $this;
        }
        if ($this->pdo instanceof PDO){
            $this->pdo_result = $this->pdo->getAttribute($attribute);
            return $this;
        }
    }

    public function rollBack()
    {
        if ($this->pdo instanceof PDO){
            $this->pdo_result = $this->pdo->rollBack();
            return $this;
        }
        $this->pdo_result = false;
        return $this;
    }

    public function fetchColumn($column_numer = 0)
    {
        if ($this->pdo_statement instanceof PDOStatement){
            $this->pdo_result = $this->pdo_statement->fetchColumn($column_numer);
            return $this;
        }
        $this->pdo_result = false;
        return $this;
    }

    public function fetchObject($className = "stdClass", $ctor_args = array())
    {
        if ($this->pdo_statement instanceof PDOStatement){
            $this->pdo_result = $this->pdo_statement->fetchObject($className, $ctor_args);
            return $this;
        }
        $this->pdo_result = false;
        return $this;
    }

    public function isEmpty()
    {
        if ($this->pdo_statement instanceof PDOStatement){
            $res = $this->pdo_result_array;
            if (is_array($res) AND count($res) > 0){
                $this->pdo_result = false;
                return $this;
            }
            else{
                $this->pdo_result = false;
                return $this;
            }
        }

        if (is_numeric($this->pdo_result)){
            if ($this->pdo_result > 0){
                $this->pdo_result = false;
                return $this;
            }
            else{
                $this->pdo_result = true;
                return $this;
            }
        }
    }

    public function Result()
    {
        if ($this->pdo_result !== null){
            return $this->pdo_result;
        }
        return $this;
    }

    public function Get($keys, $only_string = false)
    {
        if ($this->pdo_statement instanceof PDOStatement){
            $array = $this->pdo_result_array;
            $key_arr = array();
            $d = str_replace(' ','', $keys);
            $key_arr = explode(",", $d);

            if (is_array($array)){
                if (count($array) === 0){
                    $this->pdo_result = false;
                    return $this;
                }
                else if (count($array) === 1 AND count($key_arr) === 1){
                    $this->pdo_result = $array[0][$key_arr[0]];
                    return $this;
                }
                else if (count($array) === 1 AND count($key_arr) > 1){
                    $object = array();
                    foreach($key_arr as $c)
                        $object[$c] = $array[0][$c];

                    $this->pdo_result = $object;
                    return $this;
                }
                else if (count($array) > 1 AND $only_string === false){
                    $object_output = array();

                    //input
                    /* [0] = array ( key => val, key => val, key => val)
                     * [1] = array ( key => val, key => val, key => val)
                     */

                    //output
                    /* [0] = array ( key => val)
                     * [1] = array ( key => val)
                     */

                    foreach ($array as $arr){
                        $ob = array();

                        foreach ($key_arr as $c)
                            if (isset($arr[$c]))
                                $ob[$c] = $arr[$c];

                        array_push($object_output, $ob);
                    }
                    $this->pdo_result = $object_output;
                    return $this;
                }
            }
        }
        else {
            $this->pdo_result = false;
            return $this;
        }
        return $this;
    }


}


?>
