<?php namespace zblues\framework;

class Registry extends Singleton
{
    private $_registry;
    private $_configFile;
    private $_config;

    public function __destruct() 
    {
        foreach($this->_registry as $key => $reg)
            $this->_registry[$key] = null;
    }

  
    public static function set($key, $func)
    {
        $r = self::getInstance();
        $r->_registry[$key] = $func;
    }

    public static function get($key)
    {
        $r = self::getInstance();
        if(!isset($r->_registry[$key])) return null;

        if(is_callable($r->_registry[$key])) return $r->_registry[$key]($r);
        else return $r->_registry[$key];
    }
    
    public static function setConfigFile($configFilename)
    {
        if(!file_exists($configFilename)) return;
        
        $r = self::getInstance();
        $r->_configFile = $configFilename;
        $r->_config = parse_ini_file($configFilename);
    }
    
    public static function getConfig($key=null)
    {
        $r = self::getInstance();
        if(empty($key)) return $r->_config;
        else return isset($r->_config[$key]) ? $r->_config[$key] : null;
    }
}