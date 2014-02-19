<?php

$changes = array(
    // 'source' => 'destination',
);

// SQL
$database = array(
    "type" => "mysql",
    "host" => "localhost",
    "database" => "DATABASE",
    "user" => "USERNAME",
    "pass" => "PASSWORD"
);
$pdo = new SimplePDO($database);


$tables = sql_all('SHOW TABLES');

foreach($tables as $i=>$table)
{
    $columns = sql_all('SHOW COLUMNS FROM '.$table,"field");
    foreach($columns as $column)
    {
        foreach($changes as $source=>$dest)
        {
            // Voir si la colonne contient la chaine recherchée
            if(sql_first("SELECT * FROM `".$table."` WHERE `".$column."` LIKE '%".$source."%'"))
            {
                // Faire la modification complexe pour les lignes JSON
                $json = sql_all("SELECT * FROM `".$table."` WHERE `".$column."` LIKE 'a:%'");
                foreach($json as $line)
                {
                    $source_j = $line->$column;
                    $j = unserialize($line->$column);
                    if($j)
                    {
                        $j = recursive_array_replace($source,$dest,$j);
                        $j = serialize($j);
                        
                        sql_pexecute("UPDATE `".$table."` SET `".$column."`=? WHERE `".$column."`=?",array($j,$source_j));
                    }
                }
                
                // Faire ensuite la modification pour les autres lignes.
                sql_execute("UPDATE `".$table."` SET `".$column."` = replace(`".$column."`, '".$source."', '".$dest."') WHERE `".$column."` NOT LIKE 'a:%' AND `".$column."` LIKE '%".$source."%'");
            }
            
            
        }
       
    }
}   


function recursive_array_replace($find, $replace, $data) { 
    if (is_array($data)) { 
        foreach ($data as $key => $value) { 
            if (is_array($value) || is_object($value)) { 
                $data[$key] = recursive_array_replace($find, $replace, $value); 
            } else { 
                $data[$key] = str_replace($find, $replace, $value); 
            } 
        } 
    }
    else if(is_object($data))
    {
        foreach ($data as $key => $value) { 
            if (is_array($value || is_object($value))) { 
                $data->$key = recursive_array_replace($find, $replace, $value); 
            } else { 
                $data->$key = str_replace($find, $replace, $value); 
            } 
        } 
    } else { 
        $data = str_replace($find, $replace, $data); 
    } 
    return $data;
}





class SimplePDO{	

    /*****************************
      ******* VARIABLES
       *****************************/
    private $_config,$_pdo,$_statement,$_cache_prepare=array();
    public $db;
    
    /*****************************
      ******* CONSTRUCT
       *****************************/
    
    function __construct($config)
    {
        // Connexion 
        try 
        {
            $this->_pdo = new PDO($config['type'].':host='.$config['host'].';dbname='.$config['database'], $config['user'], $config['pass'], array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'));
            
            $this->_config = $config;
            $this->db = $config['database'];
            
            // Configuration
            
            $this->_pdo->setAttribute(PDO::ATTR_CASE,PDO::CASE_LOWER);
            
            $this->_pdo->setAttribute(PDO::ATTR_ORACLE_NULLS,PDO::NULL_TO_STRING);
            
        } 
        catch (PDOException $e) 
        {
            print "Error : " . $e->getMessage() . "<br/>";
            die();
        }
    }
    
    
    /*****************************
      ******* PUBLIC
       *****************************/
    public function execute($sql,array $params=array())
    {

        $is_prepare = $this->_is_prepare($sql);
        
        
        if($is_prepare)
        {
            $res = $sql->execute($params);
        }
        else
        {
            $res = $this->_pdo->exec($sql);
        }
       
        if($res === false)
        {
            $error = $is_prepare ? $sql->errorInfo() : $this->_pdo->errorInfo();
            $sql = $is_prepare ? $sql->queryString : $sql;
            echo $error[2];
        }
        return $res;
        
    }
    
    public function query(&$sql,array $params=array())
    {
        $is_prepare = $this->_is_prepare($sql);
        
        if($is_prepare = $this->_is_prepare($sql))
        {
            $res = $sql->execute($params);
        }
        else
        {
            $res = $this->_pdo->query($sql);
        }
        if($res === false)
        {
            $sql = $is_prepare ? $sql->queryString : $sql;
            $error = $this->_pdo->errorInfo();
            echo $error[2];
        }
        return $res;
        
    }
    
    
    public function fetch_first($sql,$col=null)
    {
        $statement = $this->query($sql);
        return $this->_get_first($statement,$col);
    }
    
    public function fetch_all($sql,$col=null)
    {
        $statement = $this->query($sql);
        return $this->_get_all($statement,$col);
    }
    
    
    
    public function prepare($sql,$fwdonly = false)
    {
        if(isset($this->_cache_prepare[$sql]))
        {
            return $this->_cache_prepare[$sql];
        }
        else
        {
            try
            {
                $this->_cache_prepare[$sql] = $this->_pdo->prepare($sql, array(PDO::ATTR_CURSOR => ($fwdonly ? PDO::CURSOR_FWDONLY : PDO::CURSOR_SCROLL)));
                return $this->_cache_prepare[$sql];
            }
            catch(PDOException $e)
            {
                $error = $sql->errorInfo() ;
                $sql = $sql;
                echo $error[2];
                return false;
            }
        }
    }
   
    public function prepare_first(PDOStatement $statement,array $params=array(),$col=null)
    {
        $this->query($statement,$params);
        return $this->_get_first($statement,$col);
    }
    public function prepare_all(PDOStatement $statement,array $params=array(),$col=null)
    {
        $this->query($statement,$params);
        return $this->_get_all($statement,$col);
    }
    
    
    
    
    
    
    public function clear($var)
    {
        return $this->_pdo->quote($var);
    }

    public function lastid($name=null)
    {
        return $this->_pdo->lastInsertId($name);
    }
    
    public function close()
    {
        $this->_pdo = null;
    }
    
    // Supprime tout ce qui n'est pas des lettres ou des _
    static function columnClear($column)
    {
        return preg_replace('[^a-zA-Z_]', '', $column);
    }
    
    
    // Importer un sql dump dans un fichier
    public function import_file($path)
    {
        // Nécessite :
        //      - Avoir un mot de passe utilisateur non vide
        //      - Avoir le chemin de mysql dans le PATH
        exec("mysql -u ".$this->_config['user']." -p".$this->_config['pass']." -h ".$this->_config['host']." -D ".$this->_config['database']." < ".$path);
    }
    
    /*****************************
      ******* PRIVATE
       *****************************/
    private function _is_prepare($sql)
    {
        return !is_string($sql) && is_object($sql) && get_class($sql) == 'PDOStatement';
    }
    
    
    private function _get_first(PDOStatement $statement,$col=null)
    {
       if($statement)
        {
            $line = $statement->fetch(PDO::FETCH_ASSOC);
            if($col == null)
            {
                return $line;
            }
            else if(isset($line[$col]))
            {
                return $line[$col];
            }
        }
        return null;
    }
    private function _get_all(PDOStatement $statement,$col=null)
    {
        if($statement)
        {
            $lines = $statement->fetchAll(PDO::FETCH_ASSOC);
            if($col == null)
            {
                return $lines;
            }
            else if(isset($lines[0][$col]))
            {
                $array = array();
                foreach($lines as $line)
                {
                    $array[] = $line[$col];
                }
                return $array;
            }
        }
        return array();
    }
    
    
}


/*****************************
  ******* SQL
   *****************************/

function sql_execute($sql)
{
    global $pdo;
    return $pdo->execute($sql);
}

function sql_first($sql,$col=null)
{
    global $pdo;
    return $pdo->fetch_first($sql,$col);
}
function sql_all($sql,$col=null)
{
    global $pdo;
    return $pdo->fetch_all($sql,$col);
}

////////// PREPARE //////////
function sql_prepare($sql,$fwdonly = false)
{
    global $pdo;
    return $pdo->prepare($sql,$fwdonly);
}

function sql_pexecute($statement, $params=array(),$fwdonly = false)
{
    global $pdo;
    if(is_string($params))
    {
        $params = array($params);
    }
    
    if(is_string($statement))
    {
        $statement = $pdo->prepare($statement,$fwdonly);
    }
    return $pdo->execute($statement,$params);
}

function sql_pfirst($statement, $params=array(), $col=null,$fwdonly = false)
{
    global $pdo;
    if(is_string($params))
    {
        $params = array($params);
    }
    
    if(is_string($statement))
    {
        $statement = $pdo->prepare($statement,$fwdonly);
    }
    return $pdo->prepare_first($statement,$params,$col);
}
function sql_pall($statement, $params=array(), $col=null,$fwdonly = false)
{
    global $pdo;
    if(is_string($params))
    {
        $params = array($params);
    }
    
    if(is_string($statement))
    {
        $statement = $pdo->prepare($statement,$fwdonly);
    }
    return $pdo->prepare_all($statement,$params,$col);
}

////////// GLOBAl //////////
function sql_clear($var)
{
    global $pdo;
    return $pdo->clear($var);
}
function sql_lastid($name=null)
{
    global $pdo;
    return $pdo->lastid($name);
}
function sql_close()
{
    global $pdo;
    return $pdo->close();
}
function sql_column_clear($column)
{
    global $pdo;
    return $pdo->columnClear($column);
}
function sql_database()
{
    global $pdo;
    return $pdo->db;
}
function sql_import_file($path)
{
    global $pdo;
    return $pdo->import_file($path);
}