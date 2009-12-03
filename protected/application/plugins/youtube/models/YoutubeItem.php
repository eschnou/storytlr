<?php
/*
 *    Copyright 2008-2009 Laurent Eschenauer and Alard Weisscher
 *
 *  Licensed under the Apache License, Version 2.0 (the "License");
 *  you may not use this file except in compliance with the License.
 *  You may obtain a copy of the License at
 *
 *      http://www.apache.org/licenses/LICENSE-2.0
 *
 *  Unless required by applicable law or agreed to in writing, software
 *  distributed under the License is distributed on an "AS IS" BASIS,
 *  WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 *  See the License for the specific language governing permissions and
 *  limitations under the License.
 *  
 */
class YoutubeItem extends SourceItem {

	protected $_prefix 	= 'youtube';
	
	public function toArray() {
		$this->_data['video_id'] = $this->getVideoID();
		return $this->_data;
	}
	
	public function getPreamble() {
		if ($this->_data['type'] == 'favorite') {
			return "Liked the video: ";
		} else {
			return "Uploaded the video: ";
		}
	}
	
	public function getTitle() {
		return $this->_data['title'];
	}
	
	public function setTitle($title) {
		$db = Zend_Registry::get('database');
		$sql = "UPDATE `youtube_data` SET `title`=:title "
			 . "WHERE source_id = :source_id AND id = :item_id ";
		$data 		= array("source_id" 	=> $this->getSource(),
							"item_id"		=> $this->getID(),
							"title"			=> $title);
							
 		return $db->query($sql, $data);
	}
	
	public function getDescription() {
		return $this->_data['content'];
	}
	
	public function setDescription($description) {
		$db = Zend_Registry::get('database');
		$sql = "UPDATE `youtube_data` SET `content`=:description "
			 . "WHERE source_id = :source_id AND id = :item_id ";
		$data 		= array("source_id" 	=> $this->getSource(),
							"item_id"		=> $this->getID(),
							"description"	=> $description);
							
 		return $db->query($sql, $data);
	}
	
	
	public function getType() {
		return SourceItem::VIDEO_TYPE;
	}
	
	public function getVideoID() {
		if ($this->_data['video_id']) {
			return $this->_data['video_id'];
		} else {
			return str_replace("http://gdata.youtube.com/feeds/api/videos/", "", $this->_data['uri']);
		}
	}
	
	public function getLink() {
		return $this->_data['link'];
	}
	
	public function getVideoUrl($format='youtube') {
		if ($format='youtube') {
			return "http://www.youtube.com/v/{$this->getVideoID()}";
		}
	}
	
	public function getImageUrl($size=ImageItem::SIZE_THUMBNAIL) {
		switch ($size) {
			case ImageItem::SIZE_THUMBNAIL:
				$f="2.jpg";
				break;
			case ImageItem::SIZE_SMALL:
				$f="0.jpg";
				break;
			case ImageItem::SIZE_MEDIUM:
				return false;
				break;
			case ImageItem::SIZE_LARGE:
				return false;
				break;												
			case ImageItem::SIZE_ORIGINAL:
				return false;
				break;
		}
		
		return "http://i.ytimg.com/vi/{$this->getVideoID()}/$f";
	}
	
	public function getEmbedCode($width=350, $height=250) {
		$embed  = '<object width="'. $width . '" height="' . $height . '">';
		$embed .= ' <param name="movie" value="http://www.youtube-nocookie.com/v/' . $this->getVideoID() . '&fs=1&rel=0&showinfo=0"></param>';
		$embed .= ' <param name="allowFullScreen" value="true"></param>';
		$embed .= ' <param name="allowscriptaccess" value="always"></param>';
		$embed .= ' <embed 	src="http://www.youtube-nocookie.com/v/' . $this->getVideoID() . '&fs=1&rel=0&showinfo=0"'; 
		$embed .= ' 	type="application/x-shockwave-flash"';
		$embed .= '		allowscriptaccess="always" ';
		$embed .= '     allowfullscreen="true" ';
		$embed .= '     width="' . $width . '"'; 
		$embed .= '     height="' . $height . '">';
		$embed .= ' </embed>';
		$embed .= '</object>';
		return $embed;
	}
	
	public function getRssTitle() {
		if ($this->_data['type'] == 'favorite') {
			$title = "Liked the video {$this->_data['title']}";
		} else {
			$title = "Posted a video ({$this->_data['title']})";	
		}
		return $title;
	}
	
	public function getBackup() {
		$item = array();
		$item['Link']				= $this->getLink();
		$item['Title']				= $this->_data['title'];
		$item['Date']				= $this->_data['published'];		
		return $item;
	}
}