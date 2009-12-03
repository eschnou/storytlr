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
class PicasaItem extends SourceItem implements ImageItem {

	protected $_prefix 	= 'picasa';
	
	protected $_preamble = 'Took the picture: ';

	public function getType() {
		return SourceItem::IMAGE_TYPE;
	}
	
	public function getTitle() {
		return $this->_data['title'];
	}
	
	public function setTitle($title) {
		$db = Zend_Registry::get('database');
		$sql = "UPDATE `picasa_data` SET `title`=:title "
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
		$sql = "UPDATE `picasa_data` SET `description`=:description "
			 . "WHERE source_id = :source_id AND id = :item_id ";
		$data 		= array("source_id" 	=> $this->getSource(),
							"item_id"		=> $this->getID(),
							"description"	=> $description);
							
 		return $db->query($sql, $data);
	}
	
	public function getLink() {
		return $this->_data['link'];
	}

	public function getImageUrl($size=ImageItem::SIZE_MEDIUM) {
		switch ($size) {
			case ImageItem::SIZE_THUMBNAIL:
				$ext="?imgmax=72";
				break;
			case ImageItem::SIZE_SMALL:
				$ext="?imgmax=288";
				break;
			case ImageItem::SIZE_MEDIUM:
				$ext="?imgmax=512";
				break;
			case ImageItem::SIZE_LARGE:
				$ext="?imgmax=800";
				break;												
			case ImageItem::SIZE_ORIGINAL:
				$ext = "";
				break;
		}
		
		return "{$this->_data['url']}$ext";
	}
	
	public function getRssBody() {
		$link = $this->_data['link'];
		$url  = $this->_data['url'];
		$desc = $this->_data['description'];
		$html = "<a href='$link'><img src='$url?imgmax=288' /></a><p>$desc</p>";
		return $html;
	}
	
	public function getBackup() {
		$item = array();
		$item['Link']				= $this->getLink();
		$item['Title']				= $this->_data['title'];
		$item['Description']		= $this->_data['description'];
		$item['Date']				= $this->_data['taken_at'];		
		return $item;
	}
}