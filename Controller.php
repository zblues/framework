<?php namespace zblues/framework;

class Controller 
{
  protected $reg;
  protected $auth;
  public $config;
  public $view;
  protected $model;
  
  function __construct()
  {
    $this->reg = Registry::getInstance();
    $this->auth = $this->reg->get('auth');
    $this->config = $this->reg->get('config');
#Util::msLog('['. __METHOD__ .'] ' . $this->_getCallerDir());
    $this->view = new View();
    $this->model = Model::loadModel( $this->reg->get('req')->getControllerName() );
  }
  
  function __destruct()
  {
    $this->view = null;
    $this->model = null;
  }
  
  public function setControllerModel($model)
  {
    $this->model = $model;
  }

  public function defineTemplate($arg)
  {
    if(empty($arg)) return;
		
		if(is_array($arg)) $this->view->define($arg);
    else $this->view->define($arg, func_get_arg(1));
  }
  
  public function assignVariable($arg)
  {
    if(empty($arg)) return;
		
		if(is_array($arg)) $this->view->assign($arg);
    else $this->view->assign($arg, func_get_arg(1));
  }

	public function getSecureServerPath()
	{
		return $this->config['secure_server_name'];
	}
	
	public function getServerPath()
	{
		return $this->config['server_name'];
	}
	
	private function _getCallerDir() {
		 $reflector = new ReflectionClass(get_class($this));
		 return dirname(dirname($reflector->getFileName()));
	}
}