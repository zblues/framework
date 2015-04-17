<?php namespace zblues\framework;

class Registry
{
  private static $instance = null;
  private $_registry;
  
  private function __construct() {}
  
  public static function getInstance() 
  {
    if(empty(self::$instance)) { 
      self::$instance = new self(); 
#Util::msLog(__CLASS__ . " created!");
    }
    return self::$instance;
  }
  
  public function __destruct() 
  {
    foreach($this->_registry as $key => $reg)
      $this->_registry[$key] = null;
#Util::msLog(__CLASS__ . " destructed!");
  }
  
  public function set($key, $func)
  {
    $this->_registry[$key] = $func;
  }
  
  public function get($key)
  {
    if(!isset($this->_registry[$key])) return null;
    
    if(is_callable($this->_registry[$key])) return $this->_registry[$key](self::$instance);
    else return $this->_registry[$key];
  }
}