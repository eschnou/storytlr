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
class VimeoItem extends SourceItem {

	protected $_prefix 	= 'vimeo';
	
	public function getType() {
		return SourceItem::VIDEO_TYPE;
	}
	
	public function getTitle() {
		return $this->_data['title'];
	}
	
	public function setTitle($title) {
		$db = Zend_Registry::get('database');
		$sql = "UPDATE `vimeo_data` SET `title`=:title "
			 . "WHERE source_id = :source_id AND id = :item_id ";
		$data 		= array("source_id" 	=> $this->getSource(),
							"item_id"		=> $this->getID(),
							"title"			=> $title);
							
 		return $db->query($sql, $data);
	}
	
	public function getDescription() {
		return $this->_data['caption'];
	}
	
	public function setDescription($description) {
		$db = Zend_Registry::get('database');
		$sql = "UPDATE `vimeo_data` SET `caption`=:description "
			 . "WHERE source_id = :source_id AND id = :item_id ";
		$data 		= array("source_id" 	=> $this->getSource(),
							"item_id"		=> $this->getID(),
							"description"	=> $description);
							
 		return $db->query($sql, $data);
	}
	
	public function getPreamble() {
		if ($this->_data['type'] == 'favorite') {
			return "Liked the video: ";
		} else {
			return "Uploaded the video: ";
		}
	}
	
		
	public function getLink() {
		return $this->_data['url'];
	}
	
	public function getVideoUrl($format='vimeo') {
		if ($format='vimeo') {
			return $this->_data['url'];
		}
	}
	
	public function getImageUrl($size=ImageItem::SIZE_THUMBNAIL) {
		return $this->_data['thumbnail'];
	}	
	
	public function getEmbedCode() {
		return false; 
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
		$item['Caption']			= $this->_data['caption'];
		$item['Tags']				= $this->_data['tags'];
		$item['Date']				= $this->_data['published'];		
		return $item;
	}
}