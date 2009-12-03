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
class GooglereaderItem extends SourceItem {

	protected $_prefix 	= 'googlereader';
	
	protected $_preamble = 'Shared the story: ';
	
	public function getType() {
		return SourceItem::LINK_TYPE;
	}
	
	public function getTitle() {
		return $this->_data['title'];
	}
	
	public function setTitle($title) {
		$db = Zend_Registry::get('database');
		$sql = "UPDATE `googlereader_data` SET `title`=:title "
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
		$sql = "UPDATE `googlereader_data` SET `link`=:link "
			 . "WHERE source_id = :source_id AND id = :item_id ";
		$data 		= array("source_id" 	=> $this->getSource(),
							"item_id"		=> $this->getID(),
							"link"			=> $link);
							
 		return $db->query($sql, $data);
	}
	
	public function getDescription() {
		return $this->_data['note'];
	}
	
	public function setDescription($description) {
		$db = Zend_Registry::get('database');
		$sql = "UPDATE `googlereader_data` SET `note`=:description "
			 . "WHERE source_id = :source_id AND id = :item_id ";
		$data 		= array("source_id" 	=> $this->getSource(),
							"item_id"		=> $this->getID(),
							"description"	=> $description);
							
 		return $db->query($sql, $data);
	}
	
	public function getBackup() {
		$item = array();
		$item['Link']			= $this->_data['link'];
		$item['Title']			= $this->_data['title'];
		$item['Note']			= $this->_data['note'];
		$item['Date']			= $this->_data['published'];		
		return $item;
	}
}