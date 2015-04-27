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
        #Util::msLog(__CLASS__ . " destructed!");
    }

    public function set($key, $func)
    {
        $this->_registry[$key] = $func;
    }

    public function get($key)
    {
        if(!isset($this->_registry[$key])) return null;

        //if(is_callable($this->_registry[$key])) return $this->_registry[$key](self::$instance);
        if(is_callable($this->_registry[$key])) return $this->_registry[$key]($this);
        else return $this->_registry[$key];
    }

    public function setConfigFile($configFilename)
    {
        if(!file_exists($configFilename)) return;
        
        $this->_configFile = $configFilename;
        $this->_config = parse_ini_file($configFilename);
    }
    
    public function getConfig($key=null)
    {
        if(empty($key)) return $this->_config;
        else return isset($this->_config[$key]) ? $this->_config[$key] : null;
    }
}