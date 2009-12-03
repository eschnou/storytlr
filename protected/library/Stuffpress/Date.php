<?php class Stuffpress_Date {

	//TODO Could result in timezone issues
	public static function ago($time, $format, $limit=5) {
		$now 		= time();
		$delta 		= $now - $time;
		$seconds 	= $delta;
		$minutes 	= floor($delta/60);
		$hours   	= floor($delta/3600);
		$days    	= floor($delta/86400);

		if($days>$limit) {
			$result = "on ".date($format, $time);
		}
		else if($days>1) {
			$result = "$days days ago";
		}
		else if($days >0) {
			$result = "$days day ago";
		}
		else if($hours>1) {
			$result = "$hours hours ago";
		}
		else if($hours>0) {
			$result = "$hours hour ago";
		}
		else if($minutes>1) {
			$result = "$minutes minutes ago";
		}
		else if($minutes>0) {
			$result = "$minutes minute ago";
		}
		else if($seconds>1) {
			$result = "$seconds seconds ago";
		}
		else if($seconds>0) {
			$result = "$seconds second ago";
		}
		else {
			$result = "right now";
		}

		return $result;
	}
	
	public static function localtime() {
		$config 			= Zend_Registry::get("configuration");
		$server_timezone 	= $config->web->timezone;
		$current_timezone	= date_default_timezone_get();
		
		date_default_timezone_set($server_timezone);
		$time	= time();
		date_default_timezone_set($current_timezone);
		
		return $time;
	}
	
	public static function strToTimezone($string, $timezone) {
		$current_timezone = date_default_timezone_get();
		
		date_default_timezone_set($timezone);
		$time = strtotime($string);
		date_default_timezone_set($current_timezone);
		
		return $time;
	}
	
	public static function date($string, $timestamp, $timezone) {
		$current_timezone = date_default_timezone_get();
		
		date_default_timezone_set($timezone);
		$result = date($string, $timestamp);
		date_default_timezone_set($current_timezone);
		
		return $result;
	}
}