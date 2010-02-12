<?php

abstract class Stuffpress_Controller_Action extends Zend_Controller_Action
{	
    
	protected function getStatusMessages() {
		$registry = Zend_Registry::getInstance();
		if (!$registry->isRegistered("status_messages")) {
			return;
		}
		
		return $registry->get("status_messages");
	}
	
	protected function getErrorMessages() {
		$registry = Zend_Registry::getInstance();
		if (!$registry->isRegistered("error_messages")) {
			return;
		}
		
		return $registry->get("error_messages");
	}
	
	protected function addStatusMessage($message) {
		$registry = Zend_Registry::getInstance();
		if ($registry->isRegistered("status_messages")) {
			$status =$registry->get("status_messages");
		}
		else {
			$status = array();
		}
		
		$status[] = $message;
		
		$registry->set("status_messages", $status);
	}
	
	protected function addErrorMessage($message) {
		$registry = Zend_Registry::getInstance();
		if ($registry->isRegistered("error_messages")) {
			$errors =$registry->get("error_messages");
		}
		else {
			$errors = array();
		}
		
		$errors[] = $message;
		
		$registry->set("error_messages", $errors);
	}
	
	protected function getStaticUrl() {
		$config = Zend_Registry::get("configuration");
		$host	= $config->web->host;
		$path	= $config->web->path;
		return trim("http://{$host}{$path}", '/');
	}
	
	protected function getHostname() {
		$config = Zend_Registry::get("configuration");
		$host	= $config->web->host;
		return trim($host, '/');
	}
	
	protected function getUrl($username, $path) {
		$config = Zend_Registry::get("configuration");
		$domain	= trim($config->web->host, " /");
		$root   = trim($config->web->path, " /");
		$url 	= "http://";
		
		// Single user behavior
		if (isset($config->app->user) && ($config->app->user == $username)) {
			$url .= $domain;
		} 
		// Multi user behavior
		else {
			$url .= $username. "." . $domain;
		}
		
		// Add the rest
		$url .= "/" . $root . "/" . trim($path, " /");
		
		return $url;
	}
}
