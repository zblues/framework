<?php namespace zblues/framework;

/*
  $_FILES => 기본: name, type, tmp_name, error, size
          => 확장: no(uploaded_file.no), uploaded, uploaded_name, uploaded_dir, realpath, errormsg
          
  사용법
    $a = new Upload( 'userFile' );
    $a->setUploadDir( '/some/where/' );
    $a->setAllowedExt( array('jpg','png','gif') );
    $a->setDoSave(true);
    $a->setUseAutoRename(true);
    $a->upload();
    for($i=0; $i<count($a->getUploadedFileCount()); $i++) {
      if($a->isUploaded($i)==false) echo $a->getErrorMsg($i);
      else {
        echo $a->getName($i);
        echo $a->getSize($i);
        echo $a->getUploadedName($i);
        echo $a->getUploadedDir($i);
        echo $a->getRealPath($i);
      }
    }
*/

class Upload
{
  var $baseDir; 			// $config['base_dir'];
	var $userFileName;
  var $files = array();
  var $uploadDir;			// 업로드 폴더 = $baseDir의 상대위치(/로 시작하고 /로 끝남). 추후 uploaded_file.uploaded_dir에 저장됨
  var $allowedExt;		// 허용되는 확장자
  var $maxSize;				// 허용되는 최대 크기
  var $doSave;        // true인 경우 uploaded_file 테이블에 저장함. 기본값 true
  var $useAutoRename; // true인 경우 uniqid()를 이용해 파일명을 저장함. 기본값 true
  var $isImage;       // 파일확장자로 판단하여 이미지인 경우 true 설정. upload()를 호출한 후 제대로 값이 설정됨
  
  var $db;
  
  public function __construct( $userFileName, $reg )
  {
    if(empty($userFileName) || empty($_FILES) || !isset($_FILES[$userFileName]) || empty($reg)) return null;
    $this->userFileName = $userFileName;
    
		$this->db = $reg->get('db');
    $config = $reg->get('config');
		
#Util::msLog('['.  __METHOD__ .']');
    $this->_adjustFiles($userFileName);
#Util::msLog($this->files);
    // set default value
		$this->baseDir = $config['base_dir'];
    $this->uploadDir = $config['data_dirname'] . '/upload/';
    $this->allowedExt = array("swf","txt","doc","docx","xls","xlsx","ppt","pptx","hwp","pdf","jpg","jpeg","gif","png");
    $this->maxSize = 15*1024*1024; // 15MB
    $this->doSave = true;
    $this->useAutoRename = true;
  }
	
	public function __destruct()
	{
		$this->files = null;
	}

  public function upload()
  {
    if(empty($this->files)) return false;
    
    for($i=0; $i<count($this->files); $i++) {
      $ret = $this->_uploadFile($this->files[$i]);
      // 업로드에 성공한 경우 DB에 입력함
      if($ret==UPLOAD_ERR_OK && $this->doSave==true) $this->save($i);
    }
		return true;
  }
  
  // uploaded_file 테이블에 저장
  public function save($idx)
  {
    if(empty($this->db)) return;
    
    $setArr = array(
      'name' => $this->files[$idx]['name'], 
      'uploaded_name' => $this->files[$idx]['uploaded_name'], 
      'uploaded_dir' => $this->files[$idx]['uploaded_dir'],
      'idate' => date('Y-m-d H:i:s')
    );
#Util::msLog($setArr);
    $ret = $this->db->insert('uploaded_file',$setArr);
    if($ret===false) {
      $this->files[$idx]['error'] = UPLOAD_ERR_PARTIAL;
      $this->files[$idx]['errormsg'] = 'DB에 기록하는 도중 에러가 발생했습니다. => ' . $this->db->getErrorMsg();
#Util::msLog("save to db => " . $this->files[$idx]['errormsg']);
    }
    else $this->files[$idx]['no'] = $this->db->lastInsertId();
#Util::msLog("save to db => " . $this->files[$idx]['no']);
  }
  
  // /로 끝나는 업로드 디렉로리 패스
  public function setBaseDir( $baseDir )
  {
    if(empty($baseDir)) return;
    
    $this->baseDir = $baseDir;
		if(substr($baseDir,0,1) != '/') $this->baseDir = '/' . $this->baseDir;
    if(substr($baseDir,-1) != '/') $this->baseDir .= '/';
  }
	
	// /로 끝나는 업로드 디렉로리 패스
  public function setUploadDir( $uploadDir )
  {
    if(empty($uploadDir)) return;
Util::msLog("[" . __METHOD__ ."] uploadDir = {$uploadDir}");
    $this->uploadDir = $uploadDir;
		if(substr($uploadDir,0,1) != '/') $this->uploadDir = '/' . $this->uploadDir;
    if(substr($uploadDir,-1) != '/') $this->uploadDir .= '/';
		
		if(!file_exists($this->baseDir . $uploadDir)) mkdir($this->baseDir . $uploadDir);
  }
  
  public function setAllowedExt( $allowedExt )
  {
    if(empty($allowedExt) || !is_array($allowedExt)) return;
    
    unset($this->allowedExt);
    $this->allowedExt = $allowedExt;
  }
  
  public function setMaxSize( $maxSize )
  {
    if(empty($maxSize)) return;
    
    $this->maxSize = $maxSize;
  }
  
  public function setDoSave( $doSave )
  {
    if(isset($doSave)) $this->doSave = $doSave;
  }
  
  public function setUseAutoRename( $useAutoRename )
  {
    if(isset($useAutoRename)) $this->useAutoRename = $useAutoRename;
  }
  
  /*
    $this->files[0]['name']
    $this->files[0]['type']
    $this->files[0]['size']
    $this->files[0]['tmp_name']
    $this->files[0]['error']
  */
  private function _adjustFiles($userFileName)
  {
    if(!is_array($_FILES[$userFileName]['name'])) {
      if($_FILES[$userFileName]["error"] == UPLOAD_ERR_NO_FILE) return;
      else $this->files[0] = $_FILES[$userFileName];
      return;
    }
    for($i=0,$no=0; $i<count($_FILES[$userFileName]['name']); $i++) {
      if($_FILES[$userFileName]["error"][$i] == UPLOAD_ERR_NO_FILE) continue;
      foreach(array_keys($_FILES[$userFileName]) as $key) {
        $this->files[$no][$key] = $_FILES[$userFileName][$key][$i];
      }
      $no++;
    }
  }
  
  // 실제 파일업로드 처리 루틴
  // 리턴값 : 에러인 경우 에러메세지, 성공인 경우 빈문자열;
  private function _uploadFile(&$file)
  {
    $file['uploaded'] = false; // 최종 업로드 성공인 경우 true로 설정함
    
    // 업로드를 하지 않은 경우(UPLOAD_ERR_NO_FILE)는 _adjustFiles에서 이미 처리했음
    
    // 업로드 에러
    if($file["error"] != UPLOAD_ERR_OK) {
      $file['errormsg'] = "파일 업로드 에러 : ". $this->_getUploadErrMsg($file["error"]);
      return $file["error"]; 
    }
    
    // 파일명 분석
    $filename = preg_replace('/ +/', '_', trim($file['name']));
    $filename = explode(".", $filename); 
    $ext = strtolower( $filename[count($filename)-1] ); 
    unset($filename[count($filename)-1]); 
    $filename = substr( implode(".", $filename), 0, 96); //파일명 크기 제한

    // 확장자 허용 체크
    if(!in_array($ext, $this->allowedExt)) {
      $file['errormsg'] = "{$ext}는 허용되지 않는 파일 타입입니다."; 
      return UPLOAD_ERR_EXTENSION;
    }
    
    // 파일크기 체크
    if($file["size"] > $this->maxSize) {
      $file['errormsg'] = "파일의 크기가 허용치 보다 큽니다.";
      return UPLOAD_ERR_FORM_SIZE;
    }
    
    $this->isImage = ($ext=='jpg' || $ext=='jpeg' || $ext=='png' || $ext=='gif') ? true : false;
    
    // 저장할 파일이름 설정
    $uniqid = uniqid();
    $file['uploaded_name'] = ($this->useAutoRename) ? ($uniqid . '.' . $ext) : ($filename . '.' . $ext);
    $file['uploaded_dir'] = $this->uploadDir;
    $file['realpath'] = $this->baseDir . $file['uploaded_dir'] . $file['uploaded_name']; // 저장되는 full path 저장
    $file['thumbnail_name'] = ($this->useAutoRename) ? ($uniqid . '_thumb.' . $ext) : ($filename . '_thumb.' . $ext); // 자동으로 썸네일 파일명을 계산해 둠
Util::msLog('['. __METHOD__ .'] ' . "{$this->baseDir}, {$file['uploaded_dir']}, {$file['uploaded_name']}");
    if(move_uploaded_file($file["tmp_name"], $file['realpath'])) {
      $file['uploaded'] = true; // 업로드 성공
#Util::msLog("useAutoRename = {$this->useAutoRename}");
#Util::msLog($file);
      return UPLOAD_ERR_OK;
    }
    else {
      $file['errormsg'] = $file['name'] . "의 임시파일을 대상 폴더로 저장하는 중 에러가 발생했습니다.";
      return UPLOAD_ERR_CANT_WRITE;
    }
  }
  
  private function _getUploadErrMsg($code) 
  { 
    switch ($code) { 
      case UPLOAD_ERR_INI_SIZE: 
          $message = "The uploaded file exceeds the upload_max_filesize directive in php.ini"; 
          break; 
      case UPLOAD_ERR_FORM_SIZE: 
          $message = "The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form"; 
          break; 
      case UPLOAD_ERR_PARTIAL: 
          $message = "The uploaded file was only partially uploaded"; 
          break; 
      case UPLOAD_ERR_NO_FILE: 
          $message = "No file was uploaded"; 
          break; 
      case UPLOAD_ERR_NO_TMP_DIR: 
          $message = "Missing a temporary folder"; 
          break; 
      case UPLOAD_ERR_CANT_WRITE: 
          $message = "Failed to write file to disk"; 
          break; 
      case UPLOAD_ERR_EXTENSION: 
          $message = "File upload stopped by extension"; 
          break; 

      default: 
          $message = "Unknown upload error"; 
          break; 
    } 
    return $message; 
  } 
  
    
  public function isUploaded($idx=0)
  {
    if(empty($this->files)) return false;
#Util::msLog('idx = ' . $idx);
#Util::msLog($this->files);
    if(empty($this->files[$idx]['uploaded'])) return false;
    return $this->files[$idx]['uploaded'];
  }
  
  public function getErrorMsg($idx=0)
  {
		return isset($this->files[$idx]['errormsg']) ? $this->files[$idx]['errormsg'] : '';
  }
  
  public function isImage()
  {
    if(empty($this->isImage)) return false;
    return $this->isImage;
  }
  
  public function getFileInfo($col='', $idx=0)
  {
    if(empty($this->files)) return '';
    
    if($col=='') return $this->files[$idx];
		else return $this->files[$idx][$col];
  }
  
  public function getUploadedFileCount()
  {
    $cnt = empty($this->files) ? 0 : count($this->files);
Util::msLog('['. __METHOD__ .'] ' . $cnt);
    return $cnt;
  }

}