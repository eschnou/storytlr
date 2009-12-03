<?php

class Stuffpress_Permalink {

	public static function story($id, $title) {
		$title = Stuffpress_Permalink::slug($title);
		return "$id-$title.html";
	}
	
	public static function entry($source_id, $item_id, $title) {
		if ($title && strlen($title) > 0) {
			$title = Stuffpress_Permalink::slug($title);
			return "$title-{$source_id}-{$item_id}.html";
		} else {
			return "{$source_id}-{$item_id}.html";
		}
	}

	public static function slug($title) {
		$title = (string) $title;
		$title = strtolower($title);
		$title = trim($title);
		$title = substr($title, 0, 75);
		$title = Stuffpress_Permalink::removeaccents($title);
		$title = preg_replace( '/[^a-z0-9- ]/', '', $title); // remove all non-alphanumeric characters except for spaces and hyphens
		$title = str_replace( ' ', '-', $title);
		return $title;
	}

	public static function removeaccents($string)
	{
  		return strtr($string,
  					"ŠŒŽšœžŸ¥µÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝßàáâãäåæçèéêëìíîïðñòóôõöøùúûüýÿ",
  					"SOZsozYYuAAAAAAACEEEEIIIIDNOOOOOOUUUUYsaaaaaaaceeeeiiiionoooooouuuuyy");
	}
}