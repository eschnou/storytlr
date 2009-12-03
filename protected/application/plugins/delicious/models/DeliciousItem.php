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

class DeliciousItem extends SourceItem {

	protected $_prefix 	 = 'delicious';
	
	protected $_preamble = 'Bookmarked the page: ';
	
	public function toArray() {
		$this->_data['tags'] = $this->getTags();
		return $this->_data;
	}
	
	public function getType() {
		return SourceItem::LINK_TYPE;
	}
	
	public function getTitle() {
		return $this->_data['title'];
	}
	
	public function setTitle($title) {
		$db = Zend_Registry::get('database');
		$sql = "UPDATE `delicious_data` SET `title`=:title "
			 . "WHERE source_id = :source_id AND id = :item_id ";
		$data 		= array("source_id" 	=> $this->getSource(),
							"item_id"		=> $this->getID(),
							"title"			=> $title);
							
 		return $db->query($sql, $data);
	}
	
	public function getLink() {
		return $this->_data['link'];
	}
	
	public function setLink($link) {
		$db = Zend_Registry::get('database');
		$sql = "UPDATE `delicious_data` SET `link`=:link "
			 . "WHERE source_id = :source_id AND id = :item_id ";
		$data 		= array("source_id" 	=> $this->getSource(),
							"item_id"		=> $this->getID(),
							"link"			=> $link);
							
 		return $db->query($sql, $data);
	}
	
	public function getDescription() {
		return $this->_data['description'];
	}
	
	public function setDescription($description) {
		$db = Zend_Registry::get('database');
		$sql = "UPDATE `delicious_data` SET `description`=:description "
			 . "WHERE source_id = :source_id AND id = :item_id ";
		$data 		= array("source_id" 	=> $this->getSource(),
							"item_id"		=> $this->getID(),
							"description"	=> $description);
							
 		return $db->query($sql, $data);
	}
	
	public function getBackup() {
		$item = array();
		$item['Link']				= $this->_data['link'];
		$item['Title']				= $this->_data['title'];
		$item['Subject']			= $this->_data['subject'];
		$item['Description']		= $this->_data['description'];
		$item['Date']				= $this->_data['dateposted'];
		return $item;
	}
	
}