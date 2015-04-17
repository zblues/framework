<?php namespace zblues\framework;

class Model
{
  protected $reg;
  protected $db;
	protected $config;
  
  protected $sp;  // search parameter
  protected $paging;  // 페이징 오프젝트
  protected $curPage; // 현재 페이지
  
  public static function getInstance()
  {
    static $instance = null;
    if($instance === null)
    {
      $instance = new static();
    }
    return $instance;
  }
  
  protected function __construct()
  {
    $this->reg = Registry::getInstance();
    $this->db = $this->reg->get('db');
		$this->config = $this->reg->get('config');
    $this->sp = array('sp'=>'', 'arg'=>array(), 'ord'=>array());
  }
  
  function __destruct()
  {
    $this->paging = null;
  }
  
  public static function loadModel($name)
  {
    $modelClassName = $name . 'Model';
		if(class_exists($modelClassName))
			return $modelClassName::getInstance();
		else return null;
		
    /*
    $config = Registry::getInstance()->get('config');
    $modelFile = $config['base_dir'] . $config['app_dirname'] . '/' . $config['model_dirname'] . '/' . $modelClassName . '.php';
    if(file_exists($modelFile)) 
    {
      include_once $modelFile;
      return $modelClassName::getInstance();
    } else  return null;
		*/
  }
	
	////////////////////////////////////////
  // DB 관련
  ////////////////////////////////////////
	
	public function selectDB($sql, $array = array())
	{
		return $this->db->select($sql, $array);
	}
	
	public function selectOneDB($sql, $array = array())
	{
		return $this->db->selectOne($sql, $array);
	}
	
	public function insertDB($tableName, $data)
	{
		return $this->db->insert($tableName, $data);
	}
	
	public function updateDB($tableName, $data, $where)
	{
		return $this->db->update($tableName, $data, $where);
	}
	
	public function deleteDB($tableName, $where)
	{
		return $this->db->delete($tableName, $where);
	}
  
  ////////////////////////////////////////
  // 검색 파라미터 관련
  ////////////////////////////////////////
  
  public function getPagingObj()
  {
    return $this->paging;
  }
  
  // 현재 페이지
  public function getCurPage()
  {
    if(!isset($this->curPage))
      $this->curPage = empty($_REQUEST['p']) ? 1 : $_REQUEST['p'];

    return $this->curPage;
  }
  
  // 요청사항 파싱 : 먼저 폼에서 넘어온 sp를 체크하고, 다음으로 params 파라미터를 체크함
	// public function parseSP()
  public function parseSP( $params=array() )
  {
    $this->sp['enc'] = $this->sp['sp'] = isset($_REQUEST['sp']) ? $_REQUEST['sp'] : ''; // 검색 파라미터
    if($this->sp['enc']!='') {
      $searchParamDec = base64_decode($this->sp['enc']);
#Util::msLog('['.  __METHOD__ .']' . $searchParamDec);
      $searchParamList = explode("&",$searchParamDec);
      foreach($searchParamList as $param) {
        list($key, $val) = explode("=", $param);
        if($key!='_ord_') $this->sp['arg'][$key] = $val;
        else {
					$ordList = explode('|',$val);
					foreach($ordList as $ordVal) {
						list($ordKey, $ordOp) = explode('-', $ordVal);
						$this->sp['ord'][] = array('key'=>$ordKey, 'op'=>$ordOp);
					}
				}
      }
    } else $this->sp = array('sp'=>'', 'enc'=>'', 'arg'=>array());
	
		if(!empty($params) && is_array($params)) {
      foreach($params as $param)
        if(isset($_REQUEST[$param])) $this->sp['arg'][$param] =  $_REQUEST[$param];
    }
		$this->_setSP();
#Util::msLog($this->sp['arg']);
    return $this->sp;
  }
  
  // $sp에 $searchParams
	// $params : 예제 array('a'=>1, 'b'=>2)
  public function setSP( $searchParams=array(), $orderParams=array() )
  {
    $this->sp = array('arg'=>array(),'ord'=>array(),'enc'=>'');
		
		if(!is_array($searchParams) || count($searchParams)==0) 
			return array('arg'=>array(),'ord'=>array(),'enc'=>'');
			
		foreach($searchParams as $col => $val)
      $this->sp['arg'][$col] = $val;

		if(is_array($orderParams) && count($orderParams)>0) {
			$ord = array();
			foreach($orderParams as $ordVal) $ord[] = $ordVal['key'].'-'.$ordVal['op'];
      $this->sp['arg']['_ord_'] = implode('|', $ord);
		}
		
    return $this->_setSP();
  }
  
  public function setOrd( $ord )
  {
    $this->sp['ord'] = $ord;

    return $this->_setSP();
  }
  
  // $sp['sp']를 새로 인코딩함
  private function _setSP()
  {
    $args = array();
    foreach($this->sp['arg'] as $key => $val) $args[] = $key.'='.$val;
    if(!empty($this->sp['ord'])) $args[] = '_ord_='.$val;
    $this->sp['enc'] = $this->sp['sp'] = base64_encode( implode('&',$args) );
		return $this->sp;
  }
  
	public function getSP($colName=null)
  {
    if(empty($this->sp) || empty($this->sp['arg'])) $this->parseSP();
    
    if(empty($colName)) return $this->sp;
    else if(empty($this->sp['arg'][$colName])) return $this->sp;
		else return $this->sp['arg'][$colName];
  }
  
  public function getOrd()
  {
    return $this->sp['ord'];
  }
  
  
  
  
  // DB 에러
  public function getErrorMsg()
  {
    return $this->db->getErrorMsg();
  }

  // 업로드하여 저장된 실제 디렉토리 : data/~ => base_dir/data/~
  public function getRealUploadPath($path='')
  {
    $conf = $this->reg->get('config');
		return $conf['base_dir'] . $path;
  }
  
  // 웹으로 접근 가능한 패스 반환 : base_dir/data/~ -> /data/~
  public function getSrcPath($path='')
  {
    if($path=='') return '';
		
		$conf = $this->reg->get('config');
    $path = str_replace($conf['base_dir'], '',  $path);
		if($path[0]!='/') $path = '/' . $path;
		return $path;
  }
	
	
	
	
	// 기타 모델 유틸리티
	public function getUniqueCode($tableName, $colName, $len=4, $charSet='ABCDEFGHIJKLMNOPQRSTUVWXYZ')
	{
		while(1) {
			$code = Util::makeCode($len, $charSet);
			$cnt = $this->db->rowCount("SELECT * FROM {$tableName} WHERE {$colName} = '{$code}'");
			if($cnt==0) break;
		}
		return $code;
	}
}