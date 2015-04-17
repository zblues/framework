<?php namespace zblues\framework;

class Application 
{
  protected $appName;
  protected $reg;
  protected $menu;
  protected $req;
  protected $config;
  protected $isWebApp;
  private $_controllerName, $_actionName;
  private $_controllerFile;
  private $_controller;
  private $_errorControllerName = 'error';
  private $_templates, $_vars = array();
  
  public function __construct( $baseAppPath, $appName="app" )
  {
    $this->reg = Registry::getInstance();

    $configFilename = $baseAppPath . '/config/' . $appName . '.ini';
		//if(file_exists($configFilename))
			$config = $this->config = parse_ini_file($configFilename);
		//else $config = $this->config = null;
		
    $this->reg->set('config', function() use ($config) {
      return $config;
    });

    $this->reg->set('db', function() use ($config) {
      return Database::getInstance( $config['host'], $config["dbname"], $config["user"], $config["password"] );
    });

    $this->reg->set('session', function($reg) {
      return Session::getInstance($reg);
    });

    $this->reg->set('auth', function($reg) {
      return Auth::getInstance($reg);
    });

		$this->reg->set('menu', function($reg) {
      return Menu::getInstance($reg);
    });

    $this->reg->set('req', function($reg) {
      return Request::getInstance($reg);
    });

    $this->reg->set('mail', function($reg) {
      return Mail::getInstance($reg);
    });
    
    /*
    $this->reg->set('message', function() use ($this->reg) {
      return new Message($this->reg);
    });
    */
    

    $this->appName = $appName;
    $this->isWebApp = false;

  }
  
  public function __destruct()
  {
    //$this->reg = null;
  }

  public function getConfig()
  {
    return $this->config;
  }
  
  public function getRegistry()
  {
    return $this->reg;
  }
  
  // 웹 응용인 경우 call
  public function prepareWebApp()
  {
    $this->isWebApp = true;
    
    // User Request 분석
    $this->req = $this->reg->get('req');
#Util::msLog('['. __METHOD__ .']');
    $this->req->checkUserRequest();

    // menu 초기화
		$this->menu = $this->reg->get('menu');
    $this->menu->setMenuId( $this->req->getControllerName(), $this->req->getActionName() );
  }  
 
  public function run()
  {

    if($this->isWebApp) {
			$this->reg->get('session')->start( $this->reg->get('config')['session_domain'] );
			
			// 사이트 연동되는 경우 처리
			// scode : 연동되는 사이트 코드, sname : 사이트명
			if(!empty($_REQUEST['scode']) && !empty($_REQUEST['sname'])) {
				$_SESSION['scode'] = base64_decode($_REQUEST['scode']);
				$_SESSION['sname'] = $_REQUEST['sname'];
			}
		}
#Util::msLog(__METHOD__);
    // controller 로딩
    $this->_controller = $this->loadController( $this->req->getControllerName() );
		if(empty($this->_controller)) {
Util::msLog('[' . __METHOD__ . '] 심각한 에러 : ' . $this->req->getControllerName());
			$this->_error('심각한 에러', $this->req->getControllerName() . ' 콘트롤러가 존재하지 않습니다.');
			exit;
		}
#Util::msLog('[' . __METHOD__ . '] Controller loaded');
    if($this->isWebApp) {
      $this->_controller->defineTemplate( $this->_templates );
      //$this->_controller->assignVariable( $this->_vars );
      $this->_controller->assignVariable( array_merge($this->_vars, array(
        'topMenu' => $this->menu->getMenu(),
        'tmid' => $this->menu->getTopMenuId(),
        'smid' => $this->menu->getSubMenuId(),
        'head' => $this->menu->getHeadInfo(),
        'controllerName' => $this->req->getControllerName(),
        'actionName' => $this->req->getActionName()
      )));
    }
#if($this->req->getControllerName()!='help')
#Util::msLog( '['. __METHOD__ .']' . $this->req->getControllerName() . '::' . $this->req->getActionName());

    if(method_exists($this->_controller, $this->req->getActionName() . 'Action')) {
#Util::msLog('[' . __METHOD__ . '] Call action : ' . $this->req->getActionName());
      $this->_controller->{$this->req->getActionName() . 'Action'}();
    } else {
      if(method_exists($this->_controller, 'indexAction')) $this->_controller->indexAction();
      else {
        Util::jsAlertAndGo("", "/index/error404?controller=".$this->req->getControllerName() . "&action={$this->req->getActionName()}");
        return;
      }
    } 
  }
  
  // 공용으로 사용하는 템플릿 파일 정의  
  public function defineTemplate($templates)
  {
    $this->_templates = $templates;
  }
  
  // 공용으로 사용하는 템플릿 파일 정의  
  public function assignVariable($vars)
  {
    $this->_vars = $vars;
  }

  public function getControllerFile($name)
  {
    return $this->config['base_dir'] . $this->config['app_dirname'] . '/' . $this->config['controller_dirname'] . '/' . $name . 'Controller.php';
  }
  
  public function loadController($name)
  {
#Util::msLog('['. __METHOD__ .']');
		$controllerClassName = $name . 'Controller';
		if(class_exists($controllerClassName))
			return new $controllerClassName();
		else return null; //new defaultController();
  }
  
  private function _error($title, $msg)
  {
		/* $this->_controller = $this->loadController($this->_errorControllerName);
    $this->_controller->indexAction($title, $msg); */
		
		$view = new View('error/fatal', array('title'=>$title, 'msg'=>$msg));
		$view->display();
  }
}
