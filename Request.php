<?php namespace zblues\framework;

class Request extends Singleton
{
    private $_siteId;
    private $_controllerName;
    private $_actionName;

    public function checkUserRequest()
    {
        #Util::msLog("[".__METHOD__."] request_uri : " . $_SERVER['REQUEST_URI'] );
        $urlInfo = parse_url($_SERVER['REQUEST_URI']); // REQUEST_URI ex : /controller/action?a=1&b=2
        $urlArr = explode('/', $urlInfo['path']);
        #print_r($urlArr); echo count($urlArr) . '<br>';
        if(count($urlArr)>=2) 
        {
            //$siteId = empty($urlArr[1]) ? 'online' : $urlArr[1];
            $controllerName = empty($urlArr[1]) ? 'index' : $urlArr[1];
            $actionName     = empty($urlArr[2]) ? 'index' : $urlArr[2];
        } 
        else {
            //$siteId = 'online';
            $controllerName = $actionName = 'index';
        }

        #Util::msLog("[".__METHOD__."] : controller = $controllerName, action = $actionName");
        //$this->_siteId = $siteId;
        $this->_controllerName = $controllerName;
        $this->_actionName = $actionName;
    }

    public function getSiteName()
    {
        return $this->_siteName;
    }

    public function getControllerName()
    {
        return $this->_controllerName;
    }

    public function getActionName()
    {
        return $this->_actionName;
    }
}