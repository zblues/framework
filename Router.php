<?php namespace zblues\framework;

// phalcon의 라우팅 기능과 유사하게 구현
// 참조: http://docs.phalconphp.com/en/latest/reference/routing.html
class Router extends Singleton
{
	private $_prefix = '/index.php';
    private $_routes = array();
	
	protected function __construct()
    {
        parent::__construct();
        
        $this->add('', array('controller'=>'index', 'action'=>'index'));
        $this->add('/(\w+)/(\w+)', array('controller'=>1, 'action'=>2));
    }
    
    public function add($pattern, $args) 
    {
        $pattern = '/^' . str_replace('/', '\/', $this->_prefix . $pattern) . '$/';
		$this->_routes[$pattern] = $args;
	}
	
	// URI = REQUEST_METHOD :// SERVER_NAME REQUEST_URI
    // REQUEST_URI = PHP_SELF ? QUERY_STRING
    public function get($url=null)
    {
        if(empty($url)) $url = $_SERVER['PHP_SELF'];
        $found = 0;
        foreach ($this->_routes as $pattern => $args) {
#Registry::get('log')->addDebug($pattern . ' : ' . $url);
            if (preg_match($pattern, $url, $params)) {
                $retArgs = $args;
                // 숫자를 파싱한 값으로 치환
                foreach($retArgs as $name=>$arg) {
                    if(isset($params[$arg])) $retArgs[$name] = $params[$arg];
                }
                $found = 1;
                break;
			}
		}

        return $found ? $retArgs : null;
    }
    

}

/* 예제
Router::add('blog/(\w+)/(\d+)', array(
    'controller' => 'blog',
    'action'     => 'read',
    'category'   => 1,
    'no'         => 2
));
Router::get($_SERVER['PHP_SELF']);
// if url was http://example.com/blog/php/312 you'd get back : category = blog, no = 312
*/