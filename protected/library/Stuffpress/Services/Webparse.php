<?php

class Stuffpress_Services_Webparse {

	public static function getImages($link) {
		$matches = array();
		//$host	 = Stuffpress_Services_Webparse::getHost($link);
		$content = file_get_contents($link);
		if (preg_match_all('/<img.*?src\s*=\s*(?:\"([^\"]+)\"|\'([^\']+)\').*?>/i', $content, $matches, PREG_SET_ORDER)) {
			$images = array();
			foreach($matches as $i) {
				$url = $i[1];
				
				// If not an absolute, we skip
				if (!(substr($url, 0, 5) == "http:")) {
					continue;
				}
				
				// If already there, we skip it
				if (in_array($url, $images)) {
					continue;
				}

				// Extract caption
				preg_match("/.*alt\s*=\s*[\"|\']?(?<alt>[^\"\']+)/", $i[0], $m_alt);
				
				// Extract width
				preg_match("/.*width\s*=\s*[\"|\']?(?<width>\d+)/", $i[0], $m_width);

				// Extract height
				preg_match("/.*height\s*=\s*[\"|\']?(?<height>\d+)/", $i[0], $m_height);
				
				// If big enough, we keep it !
				if (isset($m_width['width']) && $m_width['width'] > 150) {
					$image['url']    = $url;
					$image['alt']    = @$m_alt['alt'];
					$image['height'] = @$m_height['height'];
					$image['width']  = @$m_width['width'];
					$images[] = $image;
				}
			}
			return $images;
		} else {
			return false;
		}
	}
	
	public static function getImageFromEmbed($embed,$size=ImageItem::SIZE_THUMBNAIL) {
		$matches = array();
		if (preg_match("/http\:\/\/www\.youtube\.com\/v\/(?<id>[\w_-]+)/", $embed, $matches)) {
			return Stuffpress_Services_Webparse::getThumbnailYoutube($matches['id'], $size);
		} 
		else if (preg_match("/http\:\/\/vimeo.com\/moogaloop\.swf\?clip_id=(?<id>\d+)/", $embed, $matches)) {
			return Stuffpress_Services_Webparse::getThumbnailVimeo($matches['id'], $size);
		} 
		else if (preg_match("/http\:\/\/www.viddler.com\/player\/(?<id>[\w_-]+)/", $embed, $matches)) {
			return Stuffpress_Services_Webparse::getThumbnailViddler($matches['id'], $size);
		} 		
		else {
			return false;
		}
	}

	
	
	public static function getTypeFromLink($link) {
		if (preg_match("/http:\/\/www\.youtube\.com\/watch\?v\=(?<id>[\w_-]+)/", $link)) {
			return SourceItem::VIDEO_TYPE;
		}
		else if (preg_match("/http:\/\/vimeo\.com/", $link)) {
			return SourceItem::VIDEO_TYPE;
		} 
		else if (preg_match("/http:\/\/www\.flickr\.com/", $link)) {
			return SourceItem::IMAGE_TYPE;
		} 
		else if (preg_match("/http:\/\/picasaweb\.google\.com/", $link)) {
			return SourceItem::IMAGE_TYPE;
		} 
		else {
			return SourceItem::LINK_TYPE;
		}		
	}
	
	public static function getEmbedFromLink($link, $width=0, $height=0) {
		$matches = array();
		if (preg_match("/http:\/\/www\.youtube\.com\/watch\?v\=(?<id>[\w_-]+)/", $link, $matches)) {
			return Stuffpress_Services_Webparse::getEmbedYoutube($matches['id'], $width, $height);
		} 
		else if (preg_match("/http:\/\/vimeo\.com\/(?<id>\w+)/", $link, $matches)) {
			return Stuffpress_Services_Webparse::getEmbedVimeo($matches['id'], $width, $height);
		} 
		else {
			return false;
		}		
	}
	
	public static function getThumbnailYoutube($id,$size=ImageItem::SIZE_THUMBNAIL) {
		switch ($size) {
			case ImageItem::SIZE_THUMBNAIL:
				$f="2.jpg";
				break;
			case ImageItem::SIZE_SMALL:
				$f="0.jpg";
				break;
			case ImageItem::SIZE_MEDIUM:
				$f="0.jpg";
				break;
			case ImageItem::SIZE_LARGE:
				$f="0.jpg";
				break;												
			case ImageItem::SIZE_ORIGINAL:
				$f="0.jpg";
				break;
		}
		
		return "http://i.ytimg.com/vi/$id/$f";
	}
	
	public static function getThumbnailViddler($id,$size=ImageItem::SIZE_THUMBNAIL) {
		switch ($size) {
			case ImageItem::SIZE_THUMBNAIL:
				$f="0";
				break;
			case ImageItem::SIZE_SMALL:
				$f="1";
				break;
			case ImageItem::SIZE_MEDIUM:
				$f="2";
				break;
			case ImageItem::SIZE_LARGE:
				$f="2";
				break;												
			case ImageItem::SIZE_ORIGINAL:
				$f="2";
				break;
		}
		
		return "http://cdn-thumbs.viddler.com/thumbnail_{$f}_{$id}.jpg";
	}
	
	public static function getThumbnailVimeo($id,$size=ImageItem::SIZE_THUMBNAIL) {
		return false;
	}
	
	public static function getEmbedYoutube($id, $width=0, $height=0) {
		$width  = $width ? $width : 425;
		$height = $height ? $height : 344;
		$format = '<object width="%2$d" height="%3$d"><param name="movie" value="http://www.youtube.com/v/%1$s&hl=en&fs=1"></param><param name="allowFullScreen" value="true"></param><param name="allowscriptaccess" value="always"></param><embed src="http://www.youtube.com/v/%1$s&hl=en&fs=1" type="application/x-shockwave-flash" allowscriptaccess="always" allowfullscreen="true" width="%2$d" height="%3$d"></embed></object>';
		$embed  = sprintf($format, $id, $width, $height); 
		return $embed;
	}
	
	public static function getEmbedVimeo($id, $width=0, $height=0) {
		$width  = $width ? $width : 400;
		$height = $height ? $height : 225;
		$format = '<object width="%2$d" height="%3$d"><param name="allowfullscreen" value="true" /><param name="allowscriptaccess" value="always" /><param name="movie" value="http://vimeo.com/moogaloop.swf?clip_id=%1$s&amp;server=vimeo.com&amp;show_title=1&amp;show_byline=1&amp;show_portrait=0&amp;color=426975&amp;fullscreen=1" /><embed src="http://vimeo.com/moogaloop.swf?clip_id=%1$s&amp;server=vimeo.com&amp;show_title=1&amp;show_byline=1&amp;show_portrait=0&amp;color=426975&amp;fullscreen=1" type="application/x-shockwave-flash" allowfullscreen="true" allowscriptaccess="always" width="%2$d" height="%3$d"></embed></object>';
		$embed  = sprintf($format, $id, $width, $height); 
		return $embed;
	}
	
	public static function getHost($link) {
		$matches = array();
		
		if (preg_match('/http:\/\/(?<host>[\w|\.|-]+)[$|\/.*]/', $link, $matches)) {
			return $matches['host'];
		} else {
			return false;
		}
	}
}