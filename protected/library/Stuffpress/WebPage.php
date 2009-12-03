<?php

class Stuffpress_WebPage {
	
	private $_url;
	
	private $_title;

	private $_content;
	
	public function __construct($url) {
		if (!Stuffpress_URL::validate($url)) {
			throw new Stuffpress_Exception("Invalid URL to process: $url");
		}
		
		$this->_url 	= $url;
		$this->_content = $this->fetchPage($url);
		$this->_title   = $this->fetchTitle($this->_content);
	}
	
	public function getTitle() {
		return $this->_title;
	}
	
	public function getURL() {
		return $this->_url;
	}

	private function fetchTitle($content) {
		$matches = array();
		if (preg_match("/\<title\>(.*)\<\/title\>/i", $content, $matches)) {
			return $matches[1];
		} else {
			return '';
		}
	}
	
	private function fetchPage($url) {
		$curl 	= curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_HEADER, false);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($curl, CURLOPT_USERAGENT,'Storytlr/1.0');
	
		$response = curl_exec($curl);
		$http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		curl_close ($curl);

		if ($http_code != 200) {
			throw new Stuffpress_Exception("WebPage returned status $http_code for url: $url", $http_code);
		}
		
		return $response;
	}
}
?>