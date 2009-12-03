<?php

class Stuffpress_Services_Blogsearch {

	public static function ping($title, $url, $rss) {
		$title = urlencode($title);
		$url  = "http://blogsearch.google.com/ping?name=$title&url=$url&changesURL=$rss";

		$curl = curl_init();
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);		
		curl_setopt($curl, CURLOPT_URL,$url);  
		curl_setopt($curl, CURLOPT_USERAGENT,'Storytlr/1.0');
	
		$response = curl_exec($curl);
		$http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		curl_close ($curl);		
		
		if ($http_code != 200) {
				Zend_Registry::get('logger')->log("Blogsearch service return http code $http_code", Zend_Log::ERR);
		}
	}
}