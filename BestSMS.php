<?php
/*
  이 파일은 BestSMS (bestsms.kr 알티컴즈)에서 제공받은 파일을 기반으로 작성한 클래스이다.
  서버로 보내기 위해 UTF-8을 EUC-KR로 변환해야 함.
  SMS 기술 담당자: 김경민 대리 0505-299-1302
*/

class BestSMS
{
  public static function systemSMS($toNumber,$msgData)
  {
    if(empty($toNumber) || empty($msgData)) return;
    
    $fromNumber = '025553430'; // 추후 아르떼이 대표전화로 수정할 것
    $subject = '아르떼이 시스템 통지';
    //$toNumber = '01062459769'; // 테스트기간 동안 사용
    
    self::sendSMS($fromNumber, $toNumber, $subject, '[아르떼이]'.$msgData);
  }
  
  public static function sendSMS($fromnumber,$tonumber,$subject,$msgdata) 
  {
		//BestSMS 배포한 siteKey,conKey,conIV(하드코딩해주세요.)
		$siteKey="";
		$AESKey="1231231231231231";
		$AESIV="1231231231231231";
		//bestSMS.kr 사용자 아이디 (webPost계정에 한함)
		$loginid="methodsoft1";
		//bestSMS.kr 사용자 비밀번호 (webPost계정에 한함)
		$loginpass="methodsoft1";
		//bestSMS.kr 계정구분값 B(WebPost계정)
		$authtype="B";
		//bestSMS.kr 사이트발급키 ("")
		$sitekey="";
		//현재시간
		$servertime=date("YmdH");  //오늘날짜
		//신청하신 도메인
		$domain="methodsoft.co.kr";
		$goUrl = "211.238.12.189";
		//$hashKey = $this->webroSha($loginid.$loginpass.$authtype.$sitekey.$servertime.$domain);
		//$hashKey = sha1($loginid.$loginpass.$authtype.$sitekey.$servertime.$domain);
		$hashKey = $loginid.$loginpass.$authtype.$sitekey.$servertime.$domain;
		$certkey = base64_encode($hashKey);
		$certkey = $loginid."|".$certkey;
		//$encrypTer = new StringEncrypter($AESKey,$AESIV);	
		$fromnumber = base64_encode($fromnumber);
		$tonumber = base64_encode($tonumber);
		$subject = base64_encode( iconv('UTF-8','EUC-KR',$subject) );
		$msgdata = base64_encode( iconv('UTF-8','EUC-KR',$msgdata) );
		
		//SMS 직접전송 http://bestsms.kr/smsSend.do
		//LMS 직접전송 http://bestsms.kr/smsSend.do
		$resAesValue = $certkey."|endauth|".$fromnumber."|endfromnumber|".$tonumber."|endtonumber|".$subject."|endsubject|".$msgdata;
		
		//$resEncrypTer = $encrypTer->encrypt($resAesValue);
		$resEncrypTer = base64_encode($resAesValue);
		$resData = $resEncrypTer;
		//echo $resData;
		$data = str_replace(" ","","reqValue=".$resData);

		//echo $data."<BR>";
		//echo strlen($resData);
		return self::httpSocketConnection($goUrl, "POST", "/NsmsSendNoHash.do", $data);
	}

	static function httpSocketConnection($host, $method, $path, $data)
  {
    $method = strtoupper($method);        
    
    if ($method == "GET")
    {
        $path.= '?'.$data;
    }    
    
    $filePointer = fsockopen($host, 8080, $errorNumber, $errorString);
    
    if (!$filePointer) 
    {
        throw new Exception("Chyba spojeni $errorNumber $errorString");
    }

    $requestHeader = $method." ".$path."  HTTP/1.1\r\n";
    $requestHeader.= "Host: ".$host."\r\n";
    $requestHeader.= "User-Agent:      Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1) Gecko/20061010 Firefox/2.0\r\n";
    $requestHeader.= "Content-Type: application/x-www-form-urlencoded\r\n";

    if ($method == "POST")
    {
        $requestHeader.= "Content-Length: ".strlen($data)."\r\n";
    }
    
    $requestHeader.= "Connection: close\r\n\r\n";
    
    if ($method == "POST")
    {
        $requestHeader.= $data;
    }            

    fwrite($filePointer, $requestHeader);
    
    $responseHeader = '';
    $responseContent = '';
//echo $requestHeader;
    do 
    {
        $responseHeader.= fread($filePointer, 1); 
    }
    while (!preg_match('/\\r\\n\\r\\n$/', $responseHeader));
    
    
    if (!strstr($responseHeader, "Transfer-Encoding: chunked"))
    {
        while (!feof($filePointer))
        {
            $responseContent.= fgets($filePointer, 128);
        }
    }
    else 
    {
        while ($chunk_length = hexdec(fgets($filePointer))) 
        {
            $responseContentChunk = '';
            $read_length = 0;
            
            while ($read_length < $chunk_length) 
            {
                $responseContentChunk .= fread($filePointer, $chunk_length - $read_length);
                $read_length = strlen($responseContentChunk);
            }

            $responseContent.= $responseContentChunk;
            
            fgets($filePointer);
        }
    }
    return chop($responseContent);
  }
  
  
  function str2blks_SHA1($str) 
  {
    $nblk = ((strlen($str) + 8) >> 6) + 1;
    for($i=0; $i < $nblk * 16; $i++) $blks[$i] = 0;
    for($i=0; $i < strlen($str); $i++) {
      $blks[$i >> 2] |= ord(substr($str, $i, 1)) << (24 - ($i % 4) * 8);
    }
    $blks[$i >> 2] |= 0x80 << (24 - ($i % 4) * 8);
    $blks[$nblk * 16 - 1] = strlen($str) * 8;
    return $blks;
  }
  
  function safeAdd($x, $y) 
  {
    $lsw = ($x & 0xFFFF) + ($y & 0xFFFF);
    $msw = ($x >> 16) + ($y >> 16) + ($lsw >> 16);
    return ($msw << 16) | ($lsw & 0xFFFF);
  }
  
  function rol($num, $cnt) 
  {
    return ($num << $cnt) | $this->zeroFill($num, 32 - $cnt);
  }
  
  function zeroFill($a, $b) 
  {
    $bin = decbin($a);
    if (strlen($bin) < $b) $bin = 0;
    else $bin = substr($bin, 0, strlen($bin) - $b);
    for ($i=0; $i < $b; $i++) {
      $bin = "0".$bin;
    }
    return bindec($bin);
  }
  
  function ft($t, $b, $c, $d) {
    if($t < 20) return ($b & $c) | ((~$b) & $d);
    if($t < 40) return $b ^ $c ^ $d;
    if($t < 60) return ($b & $c) | ($b & $d) | ($c & $d);
    return $b ^ $c ^ $d;
  }
  
  function kt($t) 
  {
    if ($t < 20) {
      return 1518500249;
    } else if ($t < 40) {
      return 1859775393;
    } else if ($t < 60) {
      return -1894007588;
    } else {
      return -899497514;
    }
  }
  
  function webroSha($str) {
    //echo $str."<BR>";
    $x = $this->str2blks_SHA1($str);
    $a =  1732584193;
    $b = -271733879;
    $c = -1732584194;
    $d =  271733878;
    $e = -1009589776;
    for($i = 0; $i < sizeof($x); $i += 16) {
      $olda = $a;
      $oldb = $b;
      $oldc = $c;
      $oldd = $d;
      $olde = $e;
      for($j = 0; $j < 80; $j++) {
        if($j < 16) $w[$j] = $x[$i + $j];
        else $w[$j] = $this->rol($w[$j - 3] ^ $w[$j - 8] ^ $w[$j - 14] ^ $w[$j - 16], 1);
        $t = $this->safeAdd($this->safeAdd($this->rol($a, 5), $this->ft($j, $b, $c, $d)), $this->safeAdd($this->safeAdd($e, $w[$j]), $this->kt($j)));
        $e = $d;
        $d = $c;
        $c = $this->rol($b, 30);
        $b = $a;
        $a = $t;
      }
      $a = $this->safeAdd($a, $olda);
      $b = $this->safeAdd($b, $oldb);
      $c = $this->safeAdd($c, $oldc);
      $d = $this->safeAdd($d, $oldd);
      $e = $this->safeAdd($e, $olde);
    }
    return sprintf("%08s%08s%08s%08s%08s", dechex($a), dechex($b), dechex($c), dechex($d), dechex($e));
  }
}



/*
 * Description :
 * 
 *   This file contains a class which converts a UTF-8 string into a cipher string, and vice versa.
 *   The class uses 128-bit AES Algorithm in Cipher Block Chaining (CBC) mode with a UTF-8 key
 *   string and a UTF-8 initial vector string which are hashed by MD5. PKCS7 Padding is used
 *   as a padding mode and binary output is encoded by Base64. 
 * 
 * Since :
 * 
 *   2007.12.09
 * 
 * Author :
 * 
 *   JO Hyeong-ryeol (http://www.hyeongryeol.com/6)
 * 
 * Copyright :
 * 
 *   Permission to copy, use, modify, sell and distribute this software is granted provided this
 *   copyright notice appears in all copies. This software is provided "as is" without express
 *   or implied warranty, and with no claim as to its suitability for any purpose.
 *   
 *   Copyright (C) 2007 by JO Hyeong-ryeol.
 * 
 * $Id: StringEncrypter.php 69 2007-12-15 05:13:48Z JO Hyeong-ryeol $
 * 
 */


/**
 * This class converts a UTF-8 string into a cipher string, and vice versa.
 * It uses 128-bit AES Algorithm in Cipher Block Chaining (CBC) mode with a UTF-8 key
 * string and a UTF-8 initial vector string which are hashed by MD5. PKCS7 Padding is used
 * as a padding mode and binary output is encoded by Base64.
 * 
 */
class StringEncrypter
{
  const STRENCRYPTER_BLOCK_SIZE		= 16 ;	// 16 bytes

  private $key ;
  private $initialVector ;

  /**
   * Creates a StringEncrypter instance.
   * 
   * key: A key string which is hashed by MD5.
   *      It must be a non-empty UTF-8 string.
   * initialVector: An initial vector string which is hashed by MD5.
   *                It must be a non-empty UTF-8 string.
   */
  function __construct ($key, $initialVector)
  {
    if ( !is_string ($key) or $key == "" )
      throw new Exception ("The key must be a non-empty string.") ;

    if ( !is_string ($initialVector) or $initialVector == "" )
      throw new Exception ("The initial vector must be a non-empty string.") ;

    // Initialize an encryption key and an initial vector.
    $this->key = md5 ($key, TRUE) ;
    $this->initialVector = md5 ($initialVector, TRUE) ;
  }

  /**
   * Encrypts a string.
   * 
   * value: A string to encrypt. It must be a UTF-8 string.
   *        Null is regarded as an empty string.
   * return: An encrypted string.
   */
  public function encrypt ($value)
  {
    if ( is_null ($value) )
      $value = "" ;

    if ( !is_string ($value) )
      throw new Exception ("A non-string value can not be encrypted.") ;


    // Append padding
    $value = self::toPkcs7 ($value) ;

    // Encrypt the value.
    $output = mcrypt_encrypt (MCRYPT_RIJNDAEL_128, $this->key, $value, MCRYPT_MODE_CBC, $this->initialVector) ;

    // Return a base64 encoded string of the encrypted value.
    return base64_encode ($output) ;
  }

  /**
   * Decrypts a string which is encrypted with the same key and initial vector. 
   * 
   * value: A string to decrypt. It must be a string encrypted with the same key and initial vector.
   *        Null or an empty string is not allowed.
   * return: A decrypted string
   */
  public function decrypt ($value)
  {
    if ( !is_string ($value) or $value == "" )
      throw new Exception ("The cipher string must be a non-empty string.") ;


    // Decode base64 encoding. 
    $value = base64_decode ($value) ;

    // Decrypt the value.
    $output = mcrypt_decrypt (MCRYPT_RIJNDAEL_128, $this->key, $value, MCRYPT_MODE_CBC, $this->initialVector) ;

    // Strip padding and return.
    return self::fromPkcs7 ($output) ;
  }

  /**
   * Encodes data according to the PKCS7 padding algorithm. 
   * 
   * value: A string to pad. It must be a UTF-8 string.
   *        Null is regarded as an empty string.
   * return: A padded string
   */
  private static function toPkcs7 ($value)
  {
    if ( is_null ($value) )
      $value = "" ;

    if ( !is_string ($value) )
      throw new Exception ("A non-string value can not be used to pad.") ;


    // Get a number of bytes to pad.
    $padSize = self::STRENCRYPTER_BLOCK_SIZE - (strlen ($value) % self::STRENCRYPTER_BLOCK_SIZE) ;

    // Add padding and return.
    return $value . str_repeat (chr ($padSize), $padSize) ;
  }

  /**
   * Decodes data according to the PKCS7 padding algorithm. 
   * 
   * value: A string to strip. It must be an encoded string by PKCS7.
   *        Null or an empty string is not allowed.
   * return: A stripped string
   */
  private static function fromPkcs7 ($value)
  {
    if ( !is_string ($value) or $value == "" )
      throw new Exception ("The string padded by PKCS7 must be a non-empty string.") ;

    $valueLen = strlen ($value) ;

    // The length of the string must be a multiple of block size.
    if ( $valueLen % self::STRENCRYPTER_BLOCK_SIZE > 0 )
      throw new Exception ("The length of the string is not a multiple of block size.") ;


    // Get the padding size.
    $padSize = ord ($value{$valueLen - 1}) ;

    // The padding size must be a number greater than 0 and less equal than the block size.
    if ( ($padSize < 1) or ($padSize > self::STRENCRYPTER_BLOCK_SIZE) )
      throw new Exception ("The padding size must be a number greater than 0 and less equal than the block size.") ;

    // Check padding.
    for ($i = 0; $i < $padSize; $i++)
    {
      if ( ord ($value{$valueLen - $i - 1}) != $padSize )
        throw new Exception ("A padded value is not valid.") ;
    }

    // Strip padding and return.
    return substr ($value, 0, $valueLen - $padSize) ;
  }
}

