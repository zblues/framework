<?php namespace zblues\framework;

use \PDO;

class Database extends Singleton
{
    private $_dbh;
    private $_errorInfo = array();

    public function connect($host, $dbname, $user, $password)
    {
        $this->_dbh = new PDO('mysql:host='.$host.';dbname='.$dbname, $user, $password);
    }
  
    # closes the database connection when object is destroyed.
    public function __destruct()
    {
        $this->connection = null;
        //self::$instance = null;
        //Util::msLog(__METHOD__);
    }

    public function rowCount($sql, $array = array(), $fetchMode = PDO::FETCH_ASSOC)
    {
        $sth = $this->_dbh->prepare($sql);
        foreach ($array as $key => $value) {
            $sth->bindValue(":$key", $value);
        }

        $sth->execute();

        return $sth->rowCount();
    }

    public function select($sql, $data = array(), $fetchMode = PDO::FETCH_ASSOC, $printQuery=0)
    {
        $sth = $this->_dbh->prepare($sql);
        foreach ($data as $key => $value) {
            $sth->bindValue(":$key", $value);
        }
        if($printQuery) Util::msLog('['. __METHOD__ .'] ' . $this->interpolateQuery($sql,$data)); 
        $sth->execute();
        $ret = $sth->fetchAll($fetchMode);
            if($ret===false) $this->_errorInfo = $sth->errorInfo();
            return $ret;
    } 

    public function selectOne($sql, $data = array(), $fetchMode = PDO::FETCH_ASSOC, $printQuery=0)
    {
        $sth = $this->_dbh->prepare($sql);
        foreach ($data as $key => $value) {
          $sth->bindValue(":$key", $value);
        }
        if($printQuery) Util::msLog('['. __METHOD__ .'] ' . $this->interpolateQuery($sql,$data)); 
        $sth->execute();
        $ret = $sth->fetch($fetchMode);
            if($ret===false) $this->_errorInfo = $sth->errorInfo();
            return $ret;
    }

    public function insert($table, $data, $printQuery=0)
    {
        ksort($data);

        $fieldNames = implode('`, `', array_keys($data));
        $fieldValues = ':' . implode(', :', array_keys($data));

        $sth = $this->_dbh->prepare("INSERT INTO $table (`$fieldNames`) VALUES ($fieldValues)");
        if($sth===false) {
            $this->_errorInfo = $sth->errorInfo();
            return $sth;
        } 
#Util::msLog("INSERT INTO $table (`$fieldNames`) VALUES ($fieldValues)");
        foreach ($data as $key => $value) {
            $ret = $sth->bindValue(":$key", $value);
            if($ret===false) {
#Util::msLog("bindValue Error : $key -> /$value/");
            $this->_errorInfo = $sth->errorInfo();
            return $ret;
          }
        }
        if($printQuery) Util::msLog('['. __METHOD__ .'] ' . $this->interpolateQuery("INSERT INTO $table (`$fieldNames`) VALUES ($fieldValues)",$data));
        $ret = $sth->execute();
        if($ret==false) {
Util::msLog("[Database::insert] ERROR : " . $this->interpolateQuery("INSERT INTO $table (`$fieldNames`) VALUES ($fieldValues)",$data));
            $this->_errorInfo = $sth->errorInfo();
            return false;
        } else return true;
    }

    public function update($table, $data, $where, $printQuery=0)
    {
        ksort($data);

        $fieldDetails = NULL;
        foreach($data as $key=> $value) {
            $fieldDetails .= "`$key`=:$key,";
        }
        $fieldDetails = rtrim($fieldDetails, ',');

        $sth = $this->_dbh->prepare("UPDATE $table SET $fieldDetails WHERE $where");
#echo "UPDATE $table SET $fieldDetails WHERE $where <br>" ;   
#var_dump($data);
        foreach ($data as $key => $value) {
          $ret = $sth->bindValue(":$key", $value);
#echo (($ret==true) ? "true" : "false") . " $key => $value";
        }
        if($printQuery) Util::msLog('['. __METHOD__ .'] ' . $this->interpolateQuery("UPDATE $table SET $fieldDetails WHERE $where",$data));
        $ret = $sth->execute();
        if($ret===false) {
Util::msLog("[Database::update] ERROR : " . $this->interpolateQuery("UPDATE $table SET $fieldDetails WHERE $where",$data));
            $this->_errorInfo = $sth->errorInfo();
            return false;
        } else return true;
    }

    public function delete($table, $where, $printQuery=0)
    {
        $sql = "DELETE FROM $table WHERE $where";
        if($printQuery) Util::msLog('['. __METHOD__ .'] ' . $this->interpolateQuery("DELETE FROM $table WHERE $where"));
        $ret = $this->exec($sql);
            if($ret===false) $this->_errorInfo = $this->errorInfo();
            return $ret;
    }

    public function getLastInsertId()
    {
        $sql = "SELECT LAST_INSERT_ID() as lastId";
        $ret = $this->selectOne($sql);
        return $ret['lastId'];
    }

    // DB 에러
    public function getErrorMsg()
    {
        //$errInfo = $this->errorInfo();
        return $this->_errorInfo[2];
    }

    public function interpolateQuery($query, $params) 
    {
        $keys = array();

        # build a regular expression for each parameter
        foreach ($params as $key => $value) {
            if (is_string($key)) 
                $keys[] = '/:'.$key.'/';
            else
                $keys[] = '/[?]/';
          
            if(is_numeric($value))
                $values[] = intval($value);
            else
                $values[] = '"'.$value .'"';
        }

        $query = preg_replace($keys, $values, $query, 1, $count);
        #trigger_error('replaced '.$count.' keys');

        return $query;
    }
  
}