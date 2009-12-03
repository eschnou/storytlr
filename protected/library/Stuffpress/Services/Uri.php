<?php

class Stuffpress_Services_Uri {

	public static function create($host, $path, $query=false) {
		
		$path = trim($path," /");
		$host = trim($host," /");
		
		if (is_array($query) && count($query) > 0) {
			$params = array();
			foreach($query as $key => $value) {
				$params[] = urlencode($key) . "=" . urlencode($value);
			}
			$args = implode("&", $params);
		} else {
			$args = "";
		}
		
		return "http://$host/$path?$args"; 
	}
}