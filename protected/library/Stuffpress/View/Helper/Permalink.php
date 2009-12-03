<?php

class Stuffpress_View_Helper_Permalink
{

	public function permalink($title)
	{
		$title = strip_tags($title);
		$title = strtolower($title);
		$title = trim($title);
		$title = preg_replace('/[^a-z0-9_-]/', '_', $title);
		$title = urlencode($title);
		return "$title.html";
	}
	
}