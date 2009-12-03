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
class StuffpressItem extends SourceItem {

	protected $_prefix 	= 'stuffpress';
	
	public function getType() {
		switch ($this->_data['type']) {
			case 'blog':
				return SourceItem::BLOG_TYPE;
				break;
			case 'link':
				return SourceItem::LINK_TYPE;
				break;
			case 'image':
				return SourceItem::IMAGE_TYPE;
				break;				
			case 'status':
				return  SourceItem::STATUS_TYPE;
				break;	
			case 'audio':
				return  SourceItem::AUDIO_TYPE;
				break;	
			case 'video':
				return  SourceItem::VIDEO_TYPE;
				break;													
		}
	}
	
	public function getPreamble() {
		switch ($this->getType()) {
			case SourceItem::STATUS_TYPE:
				return '';
			case SourceItem::BLOG_TYPE:
				return 'Blogged about: ';
			case SourceItem::LINK_TYPE:
				return 'Shared the link: ';
			case SourceItem::IMAGE_TYPE:
				return 'Posted the picture: ';
			case SourceItem::AUDIO_TYPE:
				return 'Posted the sound bite: ';	
			case SourceItem::VIDEO_TYPE:
				return 'Posted the video: ';								
			default:
				return '';											
		}
	}

	public function getBackup() {
		$item = array();
		$item['Link']				= $this->getLink();
		$item['Title']				= $this->_data['title'];
		$item['Content']			= $this->_data['text'];
		$item['Date']				= $this->_data['published'];		
		return $item;
	}
	
	public function getImageUrl($size=ImageItem::SIZE_MEDIUM) {
		if ($this->getType() == SourceItem::VIDEO_TYPE) {
			return Stuffpress_Services_Webparse::getImageFromEmbed($this->_data['embed'], $size);
		}
		else if ($this->getType() != SourceItem::IMAGE_TYPE) {
			return false;
		}
		
		// Get the file key
		$key = $this->_data['file'];
		
		// Get the file
		$files = new Files();
		$file  = $files->getFileFromKey($key);
		$name  = urlencode($file['name']); 
		
		// Get the root url
		$config = Zend_Registry::get("configuration");
		$host	= $config->web->host;
		$path	= $config->web->path;
		$url    = trim("http://{$host}{$path}", '/');
		
		switch ($size) {
			case ImageItem::SIZE_THUMBNAIL:
				return "$url/image/thumbnail/$key/$name";
				break;
			case ImageItem::SIZE_SMALL:
				return "$url/image/small/$key/$name";
				break;
			case ImageItem::SIZE_MEDIUM:
				return "$url/image/medium/$key/$name";
				break;
			case ImageItem::SIZE_LARGE:
				return "$url/image/large/$key/$name";
				break;												
			case ImageItem::SIZE_ORIGINAL:
				return false;
				break;
		}
	}
	
	public function getAudioUrl($format='mp3') {
		if ($this->getType() != SourceItem::AUDIO_TYPE) {
			return false;
		}
		
		// If we have a URL, eturn directly
		if (strlen($this->_data['url']) > 0) {
			return $this->_data['url'];
		}
		
		// Get the file key
		$key = $this->_data['file'];
		
		// Get the file
		$files = new Files();
		$file  = $files->getFileFromKey($key);
		$name  = urlencode($file['name']); 
		
		// Get the root url
		$host = trim(Zend_Registry::get("host"), '/');

		// Return the final URL
		return "http://$host/file/view/key/$key/$name";
	}
	
	public function getEmbedCode($width=0, $height=0) {
		switch ($this->_data['type']) {
			case 'audio':
				return $this->getAudioPlayer($width, $height);
			case 'video':
				return $this->getVideoPlayer($width, $height);
			default:
				throw new Stuffpress_Exception("Content not available for this data type");
				break;
		}	
	}
	
	public function getVideoPlayer($width=0, $height=0) {
		$player =  $this->_data['embed'];
		
		if ($width>0) {
			$player = preg_replace('/width="(\d+)"/', 'width="' . $width . '"', $player);
		}
		
		if ($height>0) {
			$player = preg_replace('/height="(\d+)"/', 'height="' . $height . '"', $player);
		}
		
		return $player;
	}
	
	public function getStatus() {
		return $this->getTitle();
	}
	
	public function setStatus($status) {		
		$db = Zend_Registry::get('database');	
		$sql = "UPDATE `stuffpress_data` SET `title`=:title "
			 . "WHERE source_id = :source_id AND id = :item_id ";
		$data 		= array("source_id" 	=> $this->getSource(),
							"item_id"		=> $this->getID(),
							"title"			=> $status);
		
 		return $db->query($sql, $data);
	}
	
	public function getTitle() {
		return $this->_data['title'];
	}
	
	public function setTitle($title) {	
		$db = Zend_Registry::get('database');	
		$sql = "UPDATE `stuffpress_data` SET `title`=:title "
			 . "WHERE source_id = :source_id AND id = :item_id ";
		$data 		= array("source_id" 	=> $this->getSource(),
							"item_id"		=> $this->getID(),
							"title"			=> $title);		
 		return $db->query($sql, $data);
	}
	
	public function getContent() {
		return $this->_data['text'];
	}
	
	public function setContent($text) {		
		$db = Zend_Registry::get('database');	
		$sql = "UPDATE `stuffpress_data` SET `text`=:text "
			 . "WHERE source_id = :source_id AND id = :item_id ";
		$data 		= array("source_id" 	=> $this->getSource(),
							"item_id"		=> $this->getID(),
							"text"			=> $text);
		
 		return $db->query($sql, $data);
	}
	
	public function getLink() {
		if ($this->getType() == SourceItem::LINK_TYPE) {
			return $this->_data['link'];	
		} else {	
			// Get the root url
			$host 	   = trim(Zend_Registry::get("host"), '/');
			$source_id = $this->getSource();
			$item_id   = $this->getID();
			return "http://$host/entry/$source_id/$item_id" ;
		}
	}
	
	public function setLink($link) {		
		$db = Zend_Registry::get('database');	
		$sql = "UPDATE `stuffpress_data` SET `link`=:link "
			 . "WHERE source_id = :source_id AND id = :item_id ";
		$data 		= array("source_id" 	=> $this->getSource(),
							"item_id"		=> $this->getID(),
							"link"			=> $link);
		
 		return $db->query($sql, $data);
	}
	
	public function getDescription() {
		return $this->getContent();
	}
	
	public function setDescription($description) {
		$this->setContent($description);
	}
	
	public function getFile() {
		return $this->_data['file'];
	}

}