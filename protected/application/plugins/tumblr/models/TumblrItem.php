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
class TumblrItem extends SourceItem {

	protected $_prefix 	= 'tumblr';

	public function getTitle() {
		switch ($this->_data['type']) {
			case 'regular':
				$title = $this->_data['regular_title'];
				break;
			case 'quote':
				$title = $this->_data['quote_text'];
				break;
			case 'link':
				$title = $this->_data['link_text'];
				break;
			case 'conversation':
				$title = $this->_data['conversation_title'];
				break;
			case 'photo':
				$title = $this->_data['photo_caption'];
				break;				
			case 'video':
				$title = $this->_data['video_caption'];
				break;	
			case 'audio':
				$title = $this->_data['audio_caption'];
				break;												
		}
		
		return strip_tags($title);
	}
	
	public function setTitle($title) {
		switch ($this->_data['type']) {
			case 'regular':
				$field = 'regular_title';
				break;
			case 'quote':
				$field = 'quote_text';
				break;
			case 'link':
				$field = 'link_text';
				break;
			case 'conversation':
				$field = 'conversation_title';
				break;
			case 'photo':
				$field = 'photo_caption';
				break;				
			case 'video':
				$field = 'video_caption';
				break;	
			case 'audio':
				$field = 'audio_caption';
				break;												
		}
		
		
		$db = Zend_Registry::get('database');
		$sql = "UPDATE `tumblr_data` SET `$field`=:title "
			 . "WHERE source_id = :source_id AND id = :item_id ";
		$data 		= array("source_id" 	=> $this->getSource(),
							"item_id"		=> $this->getID(),
							"title"			=> $title);
							
 		return $db->query($sql, $data);
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
				return 'Posted a sound bite: ';		
			case SourceItem::VIDEO_TYPE:
				return 'Posted a video: ';						
			default:
				return '';											
		}
	}

	public function getContent() {
		
		if ($this->getType() != SourceItem::BLOG_TYPE) {
			throw new Stuffpress_Exception("Content not available for this data type");
		}
		
		switch ($this->_data['type']) {
			case 'regular':
				return $this->_data['regular_body'];
				break;
			case 'quote':
				return $this->_data['quote_text'];
				break;
			case 'conversation':
				return $this->_data['conversation_text'];
				break;
		}		
	}
	
	public function setContent($content) {

		if ($this->getType() != SourceItem::BLOG_TYPE) {
			throw new Stuffpress_Exception("Content cannot be assigned to this data type");
		}
		
		switch ($this->_data['type']) {
			case 'regular':
				$field = 'regular_body';
				break;
			case 'quote':
				$field = $this->_data['quote_text'];
				break;
			case 'conversation':
				$field = 'conversation_text';
				break;											
		}

		$db = Zend_Registry::get('database');
		$sql = "UPDATE `tumblr_data` SET `$field`=:content "
			 . "WHERE source_id = :source_id AND id = :item_id ";
		$data 		= array("source_id" 	=> $this->getSource(),
							"item_id"		=> $this->getID(),
							"content"		=> $content);
							
 		return $db->query($sql, $data);
	}
	
	public function getLink() {
		if ($this->getType() == SourceItem::LINK_TYPE) {
			return $this->_data['link_url'];
		} else {
			return $this->_data['url'];
		}
	}
	
	public function setLink($link) {
		if ($this->getType() != SourceItem::LINK_TYPE) {
			throw new Stuffpress_Exception("Link is not a valid field for this data type");
		}
		
		$db = Zend_Registry::get('database');
		$sql = "UPDATE `tumblr_data` SET `link_url`=:link "
			 . "WHERE source_id = :source_id AND id = :item_id ";
		$data 		= array("source_id" 	=> $this->getSource(),
							"item_id"		=> $this->getID(),
							"link"			=> $link);
							
 		return $db->query($sql, $data);
	}
	
	public function getDescription() {
		switch ($this->_data['type']) {
			case 'link':
				return $this->_data['link_description'];
				break;
			case 'photo':
				return $this->_data['photo_note'];
				break;
			case 'audio':
				return $this->_data['audio_note'];
				break;
			case 'video':
				return $this->_data['video_note'];
				break;
			default:
				throw new Stuffpress_Exception("Content not available for this data type");
				break;
		}		
	}
	
	public function setDescription($description) {
		switch ($this->_data['type']) {
			case 'link':
				$field = 'link_description';
				break;
			case 'photo':
				$field = 'photo_note';
				break;
			case 'audio':
				$field = 'audio_note';
				break;
			case 'video':
				$field = 'video_note';
				break;
			default:
				throw new Stuffpress_Exception("Content not available for this data type");
				break;
		}		
		
		$db = Zend_Registry::get('database');
		$sql = "UPDATE `tumblr_data` SET `$field`=:description "
			 . "WHERE source_id = :source_id AND id = :item_id ";
		$data 		= array("source_id" 	=> $this->getSource(),
							"item_id"		=> $this->getID(),
							"description"	=> $description);
							
 		return $db->query($sql, $data);
	}
	
	public function getImageUrl($size=ImageItem::SIZE_MEDIUM) {
		if ($this->getType() == SourceItem::VIDEO_TYPE) {
			return Stuffpress_Services_Webparse::getImageFromEmbed($this->_data['video_player'], $size);
		}
		
		$matches = array();
		preg_match("/(?<base>.*)_\w*\.(?<ext>\w*)$/", $this->_data['photo_url'], $matches);
				
		switch ($size) {
			case ImageItem::SIZE_THUMBNAIL:
				return "{$matches['base']}_75sq.{$matches['ext']}";	
				break;
			case ImageItem::SIZE_SMALL:
				return "{$matches['base']}_250.{$matches['ext']}";	
				break;
			case ImageItem::SIZE_MEDIUM:
				return $this->_data['photo_url'];
				break;
			case ImageItem::SIZE_LARGE:
				return $this->_data['photo_url'];
				break;												
			case ImageItem::SIZE_ORIGINAL:
				return false;
				break;
		}
	}
	
	public function getAudioUrl() {

		$matches = array();
		if (preg_match("/(http:\/\/.*?\.mp3)/", $this->_data['audio_player'], $matches)) {
			return $matches[0];
		} else {
			return false;
		}
		
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
	
	public function getAudioPlayer($width=0, $height=0) {
		$player =  $this->_data['audio_player'];
		
		if ($width>0) {
			$player = preg_replace('/width="(\d+)"/', 'width="' . $width . '"', $player);
		}
		
		if ($height>0) {
			$player = preg_replace('/height="(\d+)"/', 'height="' . $height . '"', $player);
		}
		
		return $player;
	}
	
	public function getVideoPlayer($width=0, $height=0) {
		$player =  $this->_data['video_player'];
		
		if ($width>0) {
			$player = preg_replace('/width="(\d+)"/', 'width="' . $width . '"', $player);
		}
		
		if ($height>0) {
			$player = preg_replace('/height="(\d+)"/', 'height="' . $height . '"', $player);
		}
		
		return $player;
	}

	public function getType() {
		switch ($this->_data['type']) {
			case 'regular':
				return SourceItem::BLOG_TYPE;
				break;
			case 'quote':
				return SourceItem::BLOG_TYPE;
				break;
			case 'link':
				return SourceItem::LINK_TYPE;
				break;
			case 'conversation':
				return SourceItem::BLOG_TYPE;
				break;
			case 'photo':
				return SourceItem::IMAGE_TYPE;
				break;				
			case 'video':
				return  SourceItem::VIDEO_TYPE;
				break;	
			case 'audio':
				return  SourceItem::AUDIO_TYPE;
				break;												
		}	
	}
	
	public function getBackup() {
		$item = array();
		$item['Link']				= $this->getLink();
		$item['Title']				= $this->getTitle();
		$item['Content']			= $this->getContent();
		$item['Date']				= $this->_data['date'];
		return $item;
	}
}