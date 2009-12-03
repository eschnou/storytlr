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
class SeesmicItem extends SourceItem {

	protected $_prefix 	= 'seesmic';
	
	protected $_preamble = 'Recorded the video: ';
	
	public function getType() {
		return SourceItem::VIDEO_TYPE;
	}
	
	public function getTitle() {
		return $this->_data['title'];
	}
	
	public function setTitle($title) {
		$db = Zend_Registry::get('database');
		$sql = "UPDATE `seesmic_data` SET `title`=:title "
			 . "WHERE source_id = :source_id AND id = :item_id ";
		$data 		= array("source_id" 	=> $this->getSource(),
							"item_id"		=> $this->getID(),
							"title"			=> $title);
							
 		return $db->query($sql, $data);
	}
	
	public function getDescription() {
		return $this->_data['note'];
	}
	
	public function setDescription($description) {
		$db = Zend_Registry::get('database');
		$sql = "UPDATE `seesmic_data` SET `note`=:description "
			 . "WHERE source_id = :source_id AND id = :item_id ";
		$data 		= array("source_id" 	=> $this->getSource(),
							"item_id"		=> $this->getID(),
							"description"	=> $description);
							
 		return $db->query($sql, $data);
	}
	
	public function getLink() {
		return $this->_data['url_player'];
	}
	
	public function getVideoUrl($format='flv') {
		if ($format = 'flv') {
			return $this->_data['url_flv'];
		}
	}
	
	public function getVideoID() {
		return $this->_data['video_id'];
	}
	
	public function getEmbedCode() {
		return false; 
	}
	
	public function getImageUrl($size=ImageItem::SIZE_THUMBNAIL) {
		return "http://t.seesmic.com/thumbnail/{$this->_data['platform_id']}_th3.jpg";
	}
	
	public function getBackup() {
		$item = array();
		$item['Link']				= $this->getLink();
		$item['Title']				= $this->_data['title'];
		$item['Date']				= $this->_data['created_at'];		
		return $item;
	}
}