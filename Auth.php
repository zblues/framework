<?php namespace zblues\framework;

class Auth
{
    const SITE_ADMIN =   0b10000000; // 128 관리자
    const SITE_MEMBER =  0b01000000; //  64 멤버
    const SITE_GUEST =   0b11111111; // 255 비로그인

    const SITE_TEACHER = 0b00100000; //  32 미술치료사
    const SITE_WRITER =  0b00010000; //  16 작가
    const SITE_DEVELOP = 0b00001000; //   8 개발자
    const SITE_SALES =   0b00000100; //   4 영업
    const SITE_COMPANY = 0b00000010; //   2 기관

    const USER_ADMIN =   0b11111111; // 255 관리자
    const USER_MEMBER =  0b01000000; //  64 멤버
    const USER_GUEST =   0b00000001; //   1 비로그인 : 00000000으로 설정되면 SITE_GUEST 권한에 접근이 안됨

    const USER_TEACHER = 0b01100000; //  96 멤버 + 미술치료사
    const USER_WRITER =  0b01010000; //  80 멤버 + 작가
    const USER_DEVELOP = 0b01001000; //  72 멤버 + 개발자
    const USER_SALES =   0b01000100; //  68 멤버 + 영업
    const USER_COMPANY = 0b01000010; //  66 멤버 + 기관
  
    private $_siteAuth; // 사이트 접근 권한
  
    public static function getInstance()
    {
        static $instance = null;
        if(is_null($instance))
        {
            $instance = new static();
        }
        return $instance;
    }
  
    protected function __construct()
    {
        $this->_siteAuth = self::SITE_GUEST;
    }
  
    function __destruct()
    {
        //self::$instance = null;
    }
  
    public static function getSiteAuthName($auth)
    {
        if($auth==self::SITE_ADMIN) return 'SITE_ADMIN';
        else if($auth==self::SITE_MEMBER) return 'SITE_MEMBER';
        else if($auth==self::SITE_GUEST) return 'SITE_GUEST';
        else if($auth==self::SITE_TEACHER) return 'SITE_TEACHER';
        else if($auth==self::SITE_WRITER) return 'SITE_WRITER';
        else if($auth==self::SITE_DEVELOP) return 'SITE_DEVELOP';
        else if($auth==self::SITE_SALES) return 'SITE_SALES';
        else return '-';
    }

    public static function getSiteAuthKorName($auth)
    {
        if($auth==self::SITE_ADMIN) return '관리자';
        else if($auth==self::SITE_MEMBER) return '회원';
        else if($auth==self::SITE_GUEST) return '손님';
        else if($auth==self::SITE_TEACHER) return '교사';
        else if($auth==self::SITE_WRITER) return '작가';
        else if($auth==self::SITE_DEVELOP) return '개발자';
        else if($auth==self::SITE_SALES) return '영업';
        else return '-';
    }

    public static function getUserAuthName($auth)
    {
        if($auth==self::USER_ADMIN) return 'USER_ADMIN';
        else if($auth==self::USER_MEMBER) return 'USER_MEMBER';
        else if($auth==self::USER_GUEST) return 'USER_GUEST';
        else if($auth==self::USER_TEACHER) return 'USER_TEACHER';
        else if($auth==self::USER_WRITER) return 'USER_WRITER';
        else if($auth==self::USER_DEVELOP) return 'USER_DEVELOP';
        else if($auth==self::USER_SALES) return 'USER_SALES';
        else return '-';
    }

    public function setAuth($arg)
    {
        if(is_array($arg)) 
        {
            foreach($arg as $auth)
            {
                $this->_siteAuth |= $auth;
            }
        } 
        else
        {
            $this->_siteAuth = $arg;
        }
    }

    public function hasAuth($siteAuth)
    {
        #Util::msLog('[hasAuth] site = ' . decbin($siteAuth) . ', user = ' . decbin($this->getUserAuth()) . ' =>' . ((int)$siteAuth & (int)$this->getUserAuth()) . '<br>');
        return (  ((int)$siteAuth & (int)$this->getUserAuth()) > 0  ) ? 1 : 0;
    }
      
    public function haveAuth($siteAuth)
    {
        $this->hasAuth($siteAuth);
    }

    public function checkAuth($siteAuth, $returnUrl='/', $msg='', $siteLevel=1)
    {
        if(empty($msg)) $msg = "해당 서비스를 사용할 수 있는 계정으로 로그인 해 주세요.";

        if( !$this->hasAuth($siteAuth) ){
            Util::jsAlertAndGo($msg, $returnUrl);
            return false;
        }
        if( $this->getUserLevel() < $siteLevel) {
            Util::jsAlertAndGo($msg, '사용자 레벨이 낮습니다. ' . $returnUrl);
            return false;
        }
        return true;
    }

    public function getUserAuth()
    {
        if(isset($_SESSION['auth']) )
        {
            return isset($_SESSION['auth']['user_auth']) ? $_SESSION['auth']['user_auth'] : Auth::USER_GUEST;
        }
        else return Auth::USER_GUEST;
    }

    // 가장 낮은 레벨 : 1
    public function getUserLevel()
    {
        if(isset($_SESSION['auth']) )
        {
          return isset($_SESSION['auth']['user_level']) ? $_SESSION['auth']['user_level'] : 1;
        }
        else return 1;
    }

    public function isLoginUser()
    {
        return ( isset($_SESSION['auth']) && $_SESSION['auth']['logged_in'] ) ? true : false;
    }

    public function isSameUser( $userNo )
    {
        if(!$this->isLoginUser()) return false;

        return ( $_SESSION['auth']['no']==$userNo ) ? true : false;
    }

    public function isAdministrator()
    {
        if( !isset($_SESSION['auth']) ) return false;
        if( $_SESSION['auth']['user_auth'] & Auth::SITE_ADMIN ) return true;
        else return false;
    }
  
}