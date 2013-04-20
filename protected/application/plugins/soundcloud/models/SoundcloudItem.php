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
class SoundcloudItem extends SourceItem {

	protected $_prefix 	= 'soundcloud';
	
	public function toArray() {
		return $this->_data;
	}
	
	public function getPreamble() {
		return "Liked the track: ";
	}
	
	public function getTitle() {
		return $this->_data['title'];
	}
	
	public function setTitle($title) {
		$db = Zend_Registry::get('database');
		$sql = "UPDATE `soundcloud_data` SET `title`=:title "
			 . "WHERE source_id = :source_id AND id = :item_id ";
		$data 		= array("source_id" 	=> $this->getSource(),
							"item_id"		=> $this->getID(),
							"title"			=> $title);
							
 		return $db->query($sql, $data);
	}
	
	public function getDescription() {
		return $this->_data['description'];
	}
	
	public function setDescription($description) {
		$db = Zend_Registry::get('database');
		$sql = "UPDATE `soundcloud_data` SET `description`=:description "
			 . "WHERE source_id = :source_id AND id = :item_id ";
		$data 		= array("source_id" 	=> $this->getSource(),
							"item_id"		=> $this->getID(),
							"description"	=> $description);
							
 		return $db->query($sql, $data);
	}
	
	
	public function getType() {
		return SourceItem::AUDIO_TYPE;
	}
		
	public function getLink() {
		return $this->_data['permalink_url'];
	}
	
	public function getAudioUrl() {
		return $this->_data['stream_url'] . "?client_id=YOUR_CLIENT_ID";
	}
		
	public function getImageUrl($size=ImageItem::SIZE_THUMBNAIL) {		
		return $this->_data['artwork_url'];
	}
	
	public function getEmbedCode($width=350, $height=250) {
		$track = $this->_data['track_id'];
		$embed = '<iframe width="100%" height="166" scrolling="no" frameborder="no" src="https://w.soundcloud.com/player/?url=http%3A%2F%2Fapi.soundcloud.com%2Ftracks%2F' . $track . '&amp;color=ff6600&amp;auto_play=false&amp;show_artwork=true"></iframe>';
		return $embed;
	}
	
	public function getRssTitle() {
		$title = "Liked the track {$this->_data['title']}";
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