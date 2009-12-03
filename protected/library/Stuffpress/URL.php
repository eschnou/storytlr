<?php class Stuffpress_URL {

	public static function validate($url)
	{
		$pattern = '/^http:\/\//';
		return preg_match($pattern, $url);
	}
	
}