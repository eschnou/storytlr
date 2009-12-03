<?php
require_once('Stuffpress/AuthException.php');

class Stuffpress_Cookie {
	private $created;
	private $userid;
	private $version;
	private $td;
	private $cookie;

	private $cypher     = 'blowfish';
	private $mode       = 'cfb';
	private $key 		= 'fkmwelkj4iu47urjwljf9jfjsda';

	private $cookiename = 'USERAUTH';
	private $myversion  = '1';
	private $expiration = '21600';
	private $warning 	= '7200';
	private $glue = '|';


	public function __construct($userid = false) {
		if (Zend_Registry::isRegistered("configuration")) {
			$config = Zend_Registry::get("configuration");
			if (isset($config->security->cookie)) {
				$this->key = $config->security->cookie;
			}	
		} 
		
		$this->td = mcrypt_module_open ($this->cypher, '', $this->mode, '');
		
		if($userid) {
			$this->userid = $userid;
			return;
		}
		else {
			if(array_key_exists($this->cookiename, $_COOKIE)) {
				$buffer = $this->_unpackage($_COOKIE[$this->cookiename]);
			}
			else {
				throw new Stuffpress_AuthException("No Cookie");
			}
		}
	}
	
	public function set($remember=false) {
		// Attempt to fetch the path and host from the config
		$config = Zend_Registry::get("configuration");
		$host	= $config->web->host;
		$expire = $remember ? time()+60*60*24*15 : 0;
		
		// Send the cookie
		$cookie = $this->_package();
		setcookie($this->cookiename, $cookie, $expire, "/", ".$host");
	}
	
	public function logout() {
		//	Attempt to fetch the path and host from the config
		$config = Zend_Registry::get("configuration");
		$host	= $config->web->host;
		$path	= $config->web->path;
		setcookie($this->cookiename, null, 0, "/", ".$host");
	}
	
	public function validate() {
		if(!$this->version || !$this->created || !$this->userid) {
			throw new Stuffpress_AuthException("Malformed cookie");
		}
		if ($this->version != $this->myversion) {
			throw new Stuffpress_AuthException("Version mismatch");
		}
		if (time() - $this->created > $this->expiration) {
			throw new Stuffpress_AuthException("Cookie expired");
		} 
		else if ( time() - $this->created > $this->warning) {
			$this->_reissue();
			$this->set();
		}
		
		return $this->userid;
	}

	private function _package() {
		$parts = array($this->myversion, time(), $this->userid);
		$cookie = implode($this->glue, $parts);
		return $this->_encrypt($cookie);
	}
	
	private function _unpackage($cookie) {
		$buffer = $this->_decrypt($cookie);
		list($this->version, $this->created, $this->userid) = explode($this->glue, $buffer);
		if($this->version != $this->myversion ||
		!$this->created ||
		!$this->userid)
		{
			throw new Stuffpress_AuthException();
		}
	}
	private function _encrypt($plaintext) {
		$iv = mcrypt_create_iv (mcrypt_enc_get_iv_size ($this->td), MCRYPT_RAND);
		mcrypt_generic_init ($this->td, $this->key, $iv);
		$crypttext = mcrypt_generic ($this->td, $plaintext);
		mcrypt_generic_deinit ($this->td);
		return $iv.$crypttext;
	}
	
	private function _decrypt($crypttext) {
		$ivsize = mcrypt_get_iv_size($this->cypher, $this->mode);
		$iv = substr($crypttext, 0, $ivsize);
		$crypttext = substr($crypttext, $ivsize);
		mcrypt_generic_init ($this->td, $this->key, $iv);
		$plaintext = mdecrypt_generic ($this->td, $crypttext);
		mcrypt_generic_deinit ($this->td);
		return $plaintext;
	}
	
	private function _reissue() {
		$this->created = time();
	}
}