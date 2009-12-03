<?php

class Stuffpress_View_Helper_Base
{

	public function base()
	{	
		return $this;			
	}
	
	public function serviceUrl() {
		$config = Zend_Registry::get("configuration");
		$host	= $config->web->host;
		$path	= $config->web->path;
		return trim("http://{$host}{$path}", '/');
	}
	
	public function staticUrl() {
		return $this->serviceUrl();
	}
	
	// Returns the full URL to the current page
	public function thisUrl() {
		$host	= Zend_Controller_Front::getInstance()->getRequest()->get('SERVER_NAME');
		$base	= Zend_Controller_Front::getInstance()->getRequest()->getBaseUrl();
		return trim("http://{$host}{$base}", '/');
	}
	
	// Returns the domain name of the public page of the user being logged in. If CNAME=false, then do not
	// use the user cname if specified
	public function myDomain($cname=true) {
		$application = Stuffpress_Application::getInstance();
		return $application->getPublicDomain($cname);
	}
	
	public function host() {
		/* Config based */
		$config = Zend_Registry::get("configuration");
		return $config->web->host;
	}
	
	public function path() {
		/* Config based */
		$config = Zend_Registry::get("configuration");		
		return $config->web->path;
	}

}