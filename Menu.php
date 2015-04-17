<?php namespace zblues/framework;

class Menu
{
  protected $reg;
  public $menu;
  public $tmid, $smid;
  
  public static function getInstance($reg)
  {
    static $instance = null;
    if($instance === null)
    {
      $instance = new static($reg);
    }
    return $instance;
  }
  
  private function __construct( $reg )
  {
    $this->reg = $reg;
    $this->loadMenuConfig();
  }
  
  function __destruct()
  {
    //self::$instance = null;
  }
  
  public function loadMenuConfig($configFile='menu.php')
  {
    $config = $this->reg->get('config');
    include $config['base_dir'] . $config['app_dirname'] . '/config/' . $configFile;
    $this->menu = isset($menu) ? $menu : null; // $menu는 위의 include 파일에 정의되어 있음
  }
  
  // 사용자가 접근한 메뉴 리턴
  public function setMenuId($controllerName, $actionName) 
  {

    if(isset($_REQUEST['tmid'])) $tmid = $_REQUEST['tmid'];
    else if($controllerName=="board")
    {
      $code = isset($_REQUEST['code']) ? $_REQUEST['code'] : '';
      if($code=="notice" || $code=="cafe" || $code=="gallery" || $code=="free")
        $tmid = "community";
      else if($code=="inquiry" || $code=="parents" || $code=="childearing")
        $tmid = "customer";
      else if($code=="faq" || $code=="qna")
        $tmid = "help";
      else $tmid = $code;
    } 
    else if($controllerName=="index")
    {
      if($actionName=='business' || $actionName=='sitemap' || $actionName=='guide')
        $tmid = "help";
      else $tmid = $controllerName;
    } 
    else if($controllerName=="member")
    {
      if($actionName=='expert') $tmid = 'company';
      else $tmid = $controllerName;
    }
    else $tmid = $controllerName;

    if(isset($_REQUEST['smid'])) $smid = $_REQUEST['smid'];
    else if($controllerName=="board") $smid = isset($_REQUEST['code']) ? $_REQUEST['code'] : '';
    else $smid = $actionName;
    
    $this->tmid = $tmid;
    $this->smid = $smid;
#Util::msLog("[". __METHOD__ ."] $tmid $smid");
  }
  
  public function getMenu()
  {
    return $this->menu;
  }
  
  public function getTopMenuId()
  {
    return $this->tmid;
  }
  
  public function getSubMenuId()
  {
    return $this->smid;
  }
  
  // HTML header의 title, keywords, description 정보
  public function getHeadInfo()
  {
    $head['title'] = isset($this->menu[$this->tmid]['subMenu'][$this->smid]['title']) ? 
      $this->menu[$this->tmid]['subMenu'][$this->smid]['title'] : $this->reg->get('config')['site_title'];
    
    $head['keywords'] = isset($this->menu[$this->tmid]['subMenu'][$this->smid]['keywords']) ? 
      $this->menu[$this->tmid]['subMenu'][$this->smid]['keywords'] : $this->reg->get('config')['site_keywords'];
    
    $head['description'] = isset($this->menu[$this->tmid]['subMenu'][$this->smid]['description']) ? 
      $this->menu[$this->tmid]['subMenu'][$this->smid]['description'] : $this->reg->get('config')['site_description'];
    
    return $head;
  }
}