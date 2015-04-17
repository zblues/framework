<?php namespace zblues/framework;

class Mail
{
  protected $reg;
  protected $db;
  
  public static function getInstance($reg)
  {
    static $instance = null;
    if($instance === null)
    {
      $instance = new static($reg);
    }
    return $instance;
  }
  
  protected function __construct($reg)
  {
    $this->reg = $reg;
    $this->db = $reg->get('db');
  }
  
  function __destruct()
  {
    //self::$instance = null;
  }
  
  private function _mailEncode($param)
  {
    return "=?UTF-8?B?".base64_encode($param)."?=";
  }
  
  public function getFromString($fromUserNo)
  {
    if(!isset($fromUserNo) || empty($fromUserNo)) return '';

    $user = $this->db->selectOne("SELECT * FROM user WHERE no = '{$fromUserNo}'");
    if(empty($user)) return '';
    if(empty($user['email'])) return '';

    return  $this->_mailEncode($user['name']) . "<" . $user['email'] . ">";
  }
  
  public function getToString($toUserNo)
  {
    if(!isset($toUserNo) || empty($toUserNo)) return '';

    $user = $this->db->selectOne("SELECT * FROM user WHERE no = '{$toUserNo}'");
    if(empty($user)) return '';
    if(empty($user['email'])) return '';

    return  $this->_mailEncode($user['name']) . "<" . $user['email'] . ">";
  }
  
  // 시스템이 자동으로 보내는 메일 : 가입, 탈퇴, 주문 등
  public function systemMail($toUserNo, $title, $contents, $attachments=array())
  {
    $toArr = array();
    // 모든 회원에게 보내는 경우
    if($toUserNo == '_all_') {
      // 메일 수신 동의자만 선택
      $user_list = $this->db->select("SELECT * FROM user WHERE user_kind='일반' AND email_agree='1'");
      for($i=0; $i<counut($user_list); $i++){
        $toArr[$i]['To'] = $this->_mailEncode($user_list[$i]['name']) . "<" . $user_list[$i]['email'] . ">";
      }
    } 
    // 한사람에게 보내는 메일인 경우
    else {
      $toUserNoArr = explode(",", $toUserNo);
      for($i=0; $i<count($toUserNoArr); $i++) {
        $user = $this->db->selectOne("SELECT * FROM user WHERE no = " . $toUserNoArr[$i] );
        if($user) {
          $toArr[$i]['To'] = $this->_mailEncode($user['name']) . "<" . $user['email'] . ">";
        } else {
          // 사업문의인 경우 $user_no는 이메일 주소가 포함되어 있음
          $toArr[$i]['To'] = $toUserNo;
        }
      }
    }

    $mailHeaders['From'] = $this->_mailEncode("아르떼이") . "<methodsoft@arttei.com>";

    for($i=0; $i<count($toArr); $i++) {
      $ret = $this->mail("아르떼이", $toArr[$i]['To'], $title, $contents, $mailHeaders, $attachments);
    }
    return true;
  }
	
	public function rawMail($senderName, $senderMail, $receiverName, $receiverMail, $title, $contents, $attachments = array(), $extraParams = '', $skin='default')
	{
		$mailHeaders['From'] = $this->_mailEncode($senderName) . "<{$senderMail}>";
		$to = $this->_mailEncode($receiverName) . "<{$receiverMail}>";
		
		return $this->mail($senderName, $to, $title, $contents, $mailHeaders, $attachments, $extraParams, $skin);
	}
  
  public function mail($senderName, $to,  $subject, $message, $headers = array(), $attachments = array(), $extraParams = '', $skin='default')
  {
    $config = $this->reg->get('config');
    
    $enc_subject =  $this->_mailEncode($subject);
    
    // Define the boundray we're going to use to separate our data with.
    $mimeBoundary = '==mimeBoundary_' . md5(time());

    // Define attachment-specific headers
    $headers['MIME-Version'] = '1.0';
    $headers['Content-Type'] = 'multipart/mixed; boundary="' . $mimeBoundary . '"';

    // Convert the array of header data into a single string.
    $headersString = '';
    foreach($headers as $header_name => $header_value) {
      if(!empty($headersString)) {
        $headersString .= "\r\n";
      }
      $headersString .= $header_name . ': ' . $header_value;
    }

    // Message Body
    $messageString  = '--' . $mimeBoundary;
    $messageString .= "\r\n";
    //$messageString .= 'Content-Type: text/plain; charset="iso-8859-1"';
    $messageString .= 'Content-Type: text/html; charset="UTF-8"';
    $messageString .= "\r\n";
    //$messageString .= 'Content-Transfer-Encoding: 7bit';
    $messageString .= 'Content-Transfer-Encoding: 8bit';
    $messageString .= "\r\n";
    $messageString .= "\r\n";

#Util::msLog('['. __METHOD__ .'] ' . "{$config['server_name']}/data/mail/{$skin}");
		$skinContents = file_get_contents("{$config['server_name']}/common/mail/{$skin}/mail.html");
		$skinContents = str_replace(array('{SERVER_NAME}', '{COMPANY_NAME}', '{SKIN}'), array($config['server_name'], $senderName, $skin), $skinContents);
		$messageString .= sprintf($skinContents, $subject, $subject, $message);
    
		$messageString .= "\r\n";
    $messageString .= "\r\n";

    // Add attachments to message body
    foreach($attachments as $localFilename => $attachment_filename) {
      $encFilename = $this->_mailEncode($attachment_filename);
      if(is_file($localFilename)) {
        #echo "$localFilename 파일이 존재함";
        $messageString .= '--' . $mimeBoundary;
        $messageString .= "\r\n";
        $messageString .= 'Content-Type: application/octet-stream; name="' . $encFilename . '"';
        $messageString .= "\r\n";
        $messageString .= 'Content-Description: ' . $encFilename;
        $messageString .= "\r\n";

        $fp = @fopen($localFilename, 'rb'); // Create pointer to file
        $fileSize = filesize($localFilename); // Read size of file
        $data = @fread($fp, $fileSize); // Read file contents
        $data = chunk_split(base64_encode($data)); // Encode file contents for plain text sending

        $messageString .= 'Content-Disposition: attachment; filename="' . $encFilename . '"; size=' . $fileSize.  ';';
        $messageString .= "\r\n";
        $messageString .= 'Content-Transfer-Encoding: base64';
        $messageString .= "\r\n\r\n";
        $messageString .= $data;
        $messageString .= "\r\n\r\n";
      } else {
        #echo "$localFilename 파일없음";
      }
    }

    // Signal end of message
    $messageString .= '--' . $mimeBoundary . '--';
#Util::msLog('['. __METHOD__ .'] ' . $messageString);
    // Send the e-mail.
    return mail($to, $enc_subject, $messageString, $headersString, $extraParams);
  }
}