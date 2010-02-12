<?php

class Stuffpress_Application {

    protected static $_application = null;
    
    public $user	= null;
    
    public $role	= 'guest';

    public static function getInstance()
    {
        if (self::$_application === null) {
            self::init();
        }

        return self::$_application;
    }
    
    protected static function init()
    {
        self::setInstance(new self());
    }
    
    public static function setInstance(Stuffpress_Application $application)
    {
        if (self::$_application !== null) {
            require_once 'Stuffpress/Exception.php';
            throw new Stuffpress_Exception('Application is already initialized');
        }

        self::$_application = $application;
    }
    
    public static function _unsetInstance()
    {
        self::$_registry = null;
    }
    
    public function getPublicDomain($cname=true) {
		$config = Zend_Registry::get("configuration");
		
		$user = $this->user;
		
		// No user logged in... no url. We should not get here.
		if (!$user) {
			throw new Stuffpress_Exception("Unexpected request to base->myUrl()");
		} 
		
		// If CNAME on and user has CNAME.. return it
		if ($cname && $user->domain) {
			return $user->domain;
		}
		
		// If a single user install and config user matches logged in one, we return the service URL
		if ($config->app->user && ($config->app->user == $user->username)) {
			$host = trim($config->web->host, " /");
			$path = trim($config->web->path, " /");
			return "$host/$path/";
		}
		
		// Otherwise, rebuild the URL
		return $user->username . "." . $config->web->host;
    }
 
}
?>