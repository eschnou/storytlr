<?php

class Stuffpress_Services_Twitter {
	
	private $_username;
	
	private $_password;
	
	public function __construct($username, $password) {
		$this->_username = $username;
		$this->_password = $password;
	}
	
	public function sendTweet($tweet) {
		$url  = "http://twitter.com/statuses/update.json?source=storytlr";
		$fields = array('status'=>urlencode($tweet));  
		$fields_string = '';
		foreach($fields as $key=>$value) { $fields_string .= $key.'='.$value.'&'; }  
		rtrim($fields_string,'&');  
		
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL,$url);  
		curl_setopt($curl, CURLOPT_POST,count($fields));  
		curl_setopt($curl, CURLOPT_POSTFIELDS,$fields_string);
		curl_setopt($curl, CURLOPT_HEADER, false);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_USERPWD, $this->_username.":".$this->_password); 
		curl_setopt($curl, CURLOPT_USERAGENT,'Storytlr/1.0');
	
		$response = curl_exec($curl);
		$http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		curl_close ($curl);

		if ($http_code != 200) {
			throw new Stuffpress_Exception("Twitter API returned http status $http_code for url: $url.", $http_code);
		}
	}
}