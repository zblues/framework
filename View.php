<?php namespace zblues/framework;

class View
{
  protected $reg; // Registry object
  private $_tpl;
	
	protected $uri;
  
  function __construct($uri='', $assignArgs=array(), $defineArgs=array())
  {
    $this->reg = Registry::getInstance();
    $this->loadTPL(); // template engine Template_ loading
		
		$this->uri = $uri;
		$this->define($uri, $uri . '.tpl');
		$this->assign($assignArgs);
		$this->define($defineArgs);
		
		if(!empty($this->reg->get('view.assign'))) $this->assign($this->reg->get('view.assign'));
		if(!empty($this->reg->get('view.define'))) $this->define($this->reg->get('view.define'));
  }
  
  function __destruct()
  {
    $this->_tpl = null;
  }
  
  // 자동으로 define()을 호출함
  // uri 예 : index/index, user/list, common/layout
	// 추후 display를 사용하고 
  public function renderURI($uri)
  {
		$this->define($uri, $uri . '.tpl');
    $this->_tpl->print_($uri);
  }
	
	public function display()
	{
		if(empty($this->uri)) return;
		$this->_tpl->print_($this->uri);
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
		
		$config = $this->reg->get('config');
#Util::msLog($config);
		if(is_array($arg)) {
			foreach($arg as $key=>$val) {
				//$arg[$key] = $config['site_id'] . '/' . $val;
				$arg[$key] = $val;
			}
			$this->_tpl->define($arg);
		}
    //else $this->_tpl->define($arg, $config['site_id'] . '/' . func_get_arg(1));
		else $this->_tpl->define($arg, func_get_arg(1));
  }
  
  // tpl wrapper function
  public function setScope($scopeName=null)
  {
    $this->_tpl->setScope($scopeName);
  }
  
  public function loadTPL()
  {
    $config = $this->reg->get('config');

	require_once 'Template_/Template_.class.php';
    $this->_tpl = new Template_($config['base_dir'] . $config['app_dirname'] . '/' . $config['view_dirname']);
  }
  
  public function getViewPath()
  {
    $config = $this->reg->get('config');
    return $config['base_dir'] . $config['app_dirname'] . '/' . $config['view_dirname'] . '/';
  }
}