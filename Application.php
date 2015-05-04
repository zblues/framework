<?php namespace zblues\framework;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class Application 
{
    protected $appName;
    protected $log;
    protected $isWebApp;
    private $_controllerObj;
    private $_defaultDefineTemplate = array();
    private $_defaultAssignVariable = array();

    public function __construct( $baseAppPath, $appName="app", $isWebApp=false )
    {
        // Config 설정
        Registry::setConfigFile($baseAppPath . '/config/' . $appName . '.ini');
        $config = Registry::getConfig();
        
        // Logger 설정 : debug, info, notice, warning, error, critical, alert, emergency
        $log = new Logger($appName);
        $log->pushHandler(new StreamHandler($config['base_dir'] . $config['data_dirname'] . '/log/app.log'), Logger::ERROR, false);
            
        // Registry::get('config')로도 호출 가능
        Registry::set('config', function() use ($config) {
            return $config;
        });
        
        // Registry::get('log')로 호출 가능
        Registry::set('log', function() use ($log) {
            return $log;
        });

        Registry::set('db', function() use ($config) {
            $db = Database::getInstance();
            $db->connect($config['host'], $config["dbname"], $config["user"], $config["password"]);
            return $db;
        });

        Registry::set('session', function() use ($config) {
            $session = Session::getInstance();
            $session->setLifetime( $config['max_lifetime'] );
            return $session;
        });

        Registry::set('auth', function() {
            return Auth::getInstance();
        });

        Registry::set('menu', function() use ($config) {
            $menu = Menu::getInstance();
            $menu->setMenuFile($config['base_dir'] . $config['app_dirname'] . '/config/menu.php');
            $menu->setDefaultHeadInfo(array(
                'site_title' => isset($config['site_title']) ? $config['site_title'] : '',
                'site_keywords' => isset($config['site_keywords']) ? $config['site_keywords'] : '',
                'site_description' => isset($config['site_description']) ? $config['site_description'] : ''
            ));
            return $menu;
        });
        
        Registry::set('router', function() {
            return Router::getInstance();
        });

        Registry::set('mail', function() use ($config) {
            $mail = Mail::getInstance();
            $mail->setConfig(array(
                'serverName'=>$config['server_name'], 
                'skinPath'=>$config['base_dir'] . $config['pub_dirname'] . '/common/mail'
            ));
            return $mail;
        });

        /*
        Registry::set('message', function() use ($this->reg) {
            return new Message($this->reg);
        });
        */

        $this->appName = $appName;
        $this->isWebApp = $isWebApp;
        $this->log = $log;
    }

    public function __destruct()
    {
        $this->log = null;
    }

    // 웹 응용인 경우 call
    public function prepareWebApp()
    {
        if($this->isWebApp == false) return;

        // 라우터 콘텍스트 정리
        $rContext = Registry::get('router')->get($_SERVER['PHP_SELF']);
        if(empty($rContext) || empty($rContext['controller'])) $rContext['controller'] = 'index';
        if(empty($rContext) || empty($rContext['action'])) $rContext['action'] = 'index';
        Registry::set('routerContext', function() use($rContext) { return $rContext; });

        // menu 초기화
        Registry::get('menu')->setMenuId( Registry::get('routerContext')['controller'], Registry::get('routerContext')['action'] );
        
        // controller 로딩
        $this->_controllerObj = $this->loadController( Registry::get('routerContext')['controller'] );
        if(empty($this->_controllerObj)) {
Registry::get('log')->addError('[' . __METHOD__ . '] 콘트롤러가 존재하지 않습니다. => ' . Registry::get('routerContext')['controller']);
            $this->display('common/layout', array('title'=>'에러', 'msg'=>Registry::get('routerContext')['controller'] . ' 콘트롤러가 존재하지 않습니다.'), array('action' => 'index/error.tpl'));
            exit;
        }
#Util::msLog('[' . __METHOD__ . '] Controller loaded');

        if(!method_exists($this->_controllerObj, Registry::get('routerContext')['action'] . 'Action')) {
            if(method_exists($this->_controllerObj, 'indexAction')) $this->_controllerObj->indexAction();
            else {
                $this->display('common/layout', array('title'=>'에러', 'msg'=> '액션이 존재하지 않습니다. => ' . Registry::get('routerContext')['controller'] . '.' . Registry::get('routerContext')['action']), array('action' => 'index/error.tpl'));
                //Util::jsAlertAndGo("", "/index/error404?controller=". Registry::get('routerContext')['controller'] . "&action={Registry::get('routerContext')['action']}");
                return;
            }
        } 
    }  

    public function run()
    {
        if($this->isWebApp) {
            Registry::get('session')->start( Registry::get('config')['session_domain'] );
            
            $this->prepareWebApp();
            $this->_controllerObj->defineTemplate( $this->_defaultDefineTemplate );
            $this->_controllerObj->assignVariable( $this->_defaultAssignVariable );
        }

        $this->_controllerObj->{Registry::get('routerContext')['action'] . 'Action'}();
    }

    // 공용으로 사용하는 템플릿 파일 정의  
    public function setDefineTemplate($templates)
    {
        if(empty($templates) || !is_array($templates)) return;
        $this->_defaultDefineTemplate = $templates;
    }

    // 공용으로 사용하는 템플릿 파일 정의  
    public function setAssignVariable($vars)
    {
        if(empty($vars) || !is_array($vars)) return;
        $this->_defaultAssignVariable = $vars;
    }
    
/* 사용하지 않으면 삭제할 것
    public function getControllerFile($name)
    {
        $c = Registry::get('config');
        return $c['base_dir'] . $c['app_dirname'] . '/' . $c['controller_dirname'] . '/' . $name . 'Controller.php';
    }
*/
    public function loadController($name)
    {
#Util::msLog('['. __METHOD__ .']');
        $controllerClassName = $name . 'Controller';
        if(class_exists($controllerClassName))
            return new $controllerClassName($name, new View);
        else return null; //new defaultController();
    }

    // $viewFile = 'common/error'
    private function display($viewFile, $assignParams=array(), $defineParams=array())
    {
        $view = new View($viewFile, $assignParams, $defineParams);
        $view->define( $this->_defaultDefineTemplate );
        $view->assign( $this->_defaultAssignVariable );
        $view->render($viewFile);
    }
}
