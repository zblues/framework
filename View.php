<?php namespace zblues\framework;

class View
{
    private $_tpl;
    protected $viewFile;

    function __construct($viewFile='', $assignArgs=array(), $defineArgs=array())
    {
        $this->loadTPL(); // template engine Template_ loading
        
        $this->viewFile = $viewFile;
        $this->define($viewFile, $viewFile . '.tpl');
        $this->assign($assignArgs);
        $this->define($defineArgs);
    }

    function __destruct()
    {
        $this->_tpl = null;
    }

    // 자동으로 define()을 호출함
    // viewFile 예 : index/index, user/list, common/layout
    // 추후 display를 사용하고 
    public function renderURI($viewFile)
    {
        $this->define($viewFile, $viewFile . '.tpl');
        $this->_tpl->print_($viewFile);
    }

    public function render($name)
    {
        if(empty($name)) return;
        
        $this->_tpl->print_($name);
    }

    // tpl wrapper function
    public function assign($arg)
    {
        if(empty($arg)) return;
        
        if(is_array($arg)) $this->_tpl->assign($arg);
        else $this->_tpl->assign($arg, func_get_arg(1));
    }

    // tpl wrapper function
    public function define($arg)
    {
        if(empty($arg)) return;
        
        $config = Registry::get('config');
        if(is_array($arg)) {
            foreach($arg as $key=>$val) {
                //$arg[$key] = $config['site_id'] . '/' . $val;
                $arg[$key] = $val;
            }
            $this->_tpl->define($arg);
        }
        else $this->_tpl->define($arg, func_get_arg(1));
    }

    // tpl wrapper function
    public function setScope($scopeName=null)
    {
        $this->_tpl->setScope($scopeName);
    }

    public function loadTPL()
    {
        $config = Registry::get('config');
        $this->_tpl = new \Template_($config['base_dir'] . $config['app_dirname'] . '/' . $config['view_dirname']);
    }

    public function getViewPath()
    {
        $config = Registry::get('config');
        return $config['base_dir'] . $config['app_dirname'] . '/' . $config['view_dirname'] . '/';
    }
}