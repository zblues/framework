<?php namespace zblues\framework;

/*
  사용법
    $session = new Session($reg);
    $session->start();
  주의사항
    Application의 run() 메쏘드에서 call 함
*/
class Session
{
  protected $reg;
  
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
    
    // set session lifetime : 해당 시간 동안 사용자의 동작이 없으면 세션의 데이터를 삭제함
    ini_set("session.gc_maxlifetime", $this->reg->get('config')['max_lifetime']);
    
    $session_hash = 'sha512';
    if (in_array($session_hash, hash_algos())) 
    {
      ini_set('session.hash_function', $session_hash);
    }
    
    // How many bits per character of the hash.
    // The possible values are '4' (0-9, a-f), '5' (0-9, a-v), and '6' (0-9, a-z, A-Z, "-", ",").
    ini_set('session.hash_bits_per_character', 5);

    // Force the session to only use cookies, not URL variables.
    ini_set('session.use_only_cookies', 1);
  }
  
  function __destruct()
  {
    //self::$instance = null;
  }
  
  public function start($domain = null, $session_name = '_ms_', $lifetime = 0, $path = '/', $secure = false) 
  {
    session_name($session_name);

    // Set the parameters : lifetime, path, domain, secure, httponly
    session_set_cookie_params($lifetime, $path, $domain, $secure, true); 

    //header('P3P: CP="IDC DSP COR CURa ADMa OUR IND PHY ONL COM STA"');
		//ini_set('session.cookie_domain', $domain);
		session_start();
    
    if($this->isValidSession())
    {
#Util::msLog("[" . __METHOD__ . "] 세션에 이상이 없는 사용자입니다.");
      /*
      if($this->isDoubtfulUser())
      {      
Util::msLog("[" . __METHOD__ . "] 의심되는 사용자입니다.");
        //$_SESSION = array();
        session_unset();
        $_SESSION['IPADDR'] = $_SERVER['REMOTE_ADDR'];
        $_SESSION['USER_AGENT'] = $_SERVER['HTTP_USER_AGENT'];
        $this->regenerateSession();
      } 
      */
      $_SESSION['EXPIRES'] = time() + $this->reg->get('config')['max_lifetime'];
#Util::msLog("[" . __METHOD__ . "] session_id = " . session_id() . ", ipaddr = ".$_SESSION['IPADDR'].", USER_AGENT = ".$_SESSION['USER_AGENT'].", EXPIRES = " . $_SESSION['EXPIRES']);      
    }
    else
    {
      $this->stop();
#Util::msLog("[" . __METHOD__ . "] 세션에 이상이 있으므로 세션을 중지합니다.");      

      session_name($session_name);
			session_set_cookie_params($lifetime, $path, $domain, $secure, true); 
			//header('P3P: CP="IDC DSP COR CURa ADMa OUR IND PHY ONL COM STA"');
			//ini_set('session.cookie_domain', $domain);
			session_start();
      
      $_SESSION['IPADDR'] = $_SERVER['REMOTE_ADDR'];
      $_SESSION['USER_AGENT'] = $_SERVER['HTTP_USER_AGENT'];
    }
 #Util::msLog($_SESSION);

  }
  
  public function stop()
  {
    //$_SESSION = array();
    session_unset();
    session_destroy();
  }
  
  public function isValidSession()
  {
    if( isset($_SESSION['OBSOLETE']) && !isset($_SESSION['EXPIRES']) ){
#Util::msLog("[" . __METHOD__ . "] 1");  
      return false;
		}

    if(isset($_SESSION['EXPIRES']) && $_SESSION['EXPIRES'] < time()) {
#Util::msLog("[" . __METHOD__ . "] 2");  
			return false;
		}
      
    if($this->isDoubtfulUser()) {
#Util::msLog("[" . __METHOD__ . "] 3"); 
			return false;
		}

    return true;
  }
  
  public function isDoubtfulUser()
  {
    if(!isset($_SESSION['IPADDR']) || !isset($_SESSION['USER_AGENT'])) {
#Util::msLog("[" . __METHOD__ . "] 1");  
      return true;
		}

    if ($_SESSION['IPADDR'] != $_SERVER['REMOTE_ADDR']) {
#Util::msLog("[" . __METHOD__ . "] 2");  
      return true;
		}

    if( $_SESSION['USER_AGENT'] != $_SERVER['HTTP_USER_AGENT']) {
#Util::msLog("[" . __METHOD__ . "] 3");  
			return true;
		}

    return false;
  }
  
  public function regenerateSession()
  {
    // If this session is obsolete it means there already is a new id
    if(isset($_SESSION['OBSOLETE']) && $_SESSION['OBSOLETE'] == true)
      return;

    // Set current session to 10 seconds
    $_SESSION['OBSOLETE'] = true;
    $_SESSION['EXPIRES'] = time() + 10;

    // Create new session without destroying the old one
    session_regenerate_id(false);

    // Grab current session ID and close both sessions to allow other scripts to use them
    $newSession = session_id();
    session_write_close();

    // Set session ID to the new one, and start it back up again
    session_id($newSession);
    session_start();

    // Now we unset the obsolete values for the session we want to keep
    unset($_SESSION['OBSOLETE']);
  }
}