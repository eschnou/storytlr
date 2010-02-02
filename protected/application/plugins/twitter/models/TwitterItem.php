<?php
/*
 *  Copyright 2008-2009 Laurent Eschenauer and Alard Weisscher
 *  Copyright 2010 John Hobbs
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
class TwitterItem extends SourceItem {

	protected $_prefix 	= 'twitter';
	
	public function toArray() {
		$this->_data['tweet'] = $this->getStatus();
		return $this->_data;
	}
	
	public function getType() {
		if ($this->_data['photo_service']) {
			return SourceItem::IMAGE_TYPE;			
		} else {
			return SourceItem::STATUS_TYPE;
		}
	}
	
	public function getStatus() {
		$status = htmlspecialchars($this->_data['text']);
		
		if ($this->_data['photo_service'] == 'twitpic') {	
			$status = preg_replace("/http:\/\/twitpic\.com\/\w+/", "", $status);
		} elseif ($this->_data['photo_service'] == 'phodroid') {	
			$status = preg_replace("/http:\/\/phodroid\.com\/\w+/", "", $status);
		}
		
		return $status; 
	}
	
	public function setStatus($status) {
		$db = Zend_Registry::get('database');
		
		$sql = "UPDATE `twitter_data` SET `text`=:status "
			 . "WHERE source_id = :source_id AND id = :item_id ";
		
		$data 		= array("source_id" 	=> $this->getSource(),
							"item_id"		=> $this->getID(),
							"status"		=> $status);
							
 		$stmt 	= $db->query($sql, $data);

 		return;
	}
	
	public function getTitle() {
		return $this->getStatus();
	}
	
	public function setTitle($title) {
		$this->setStatus($title);
	}
	
	public function getDescription() {
		return $this->_data['note'];
	}
	
	public function setDescription($note) {
		$db = Zend_Registry::get('database');
		$sql = "UPDATE `twitter_data` SET `note`=:note "
			 . "WHERE source_id = :source_id AND id = :item_id ";
		$data 		= array("source_id" 	=> $this->getSource(),
							"item_id"		=> $this->getID(),
							"note"			=> $note);
							
 		return $db->query($sql, $data);
	}
	
	public function getImageUrl($size=ImageItem::SIZE_MEDIUM) {
		if ($this->_data['photo_service'] == 'twitpic') {	
			switch ($size) {
				case ImageItem::SIZE_THUMBNAIL:
					return "http://twitpic.com/show/thumb/{$this->_data['photo_key']}.jpg";
					break;
				case ImageItem::SIZE_SMALL:
					return "http://twitpic.com/show/thumb/{$this->_data['photo_key']}.jpg";
					break;
				case ImageItem::SIZE_MEDIUM:
					return "http://twitpic.com/show/large/{$this->_data['photo_key']}.jpg";
					break;
				case ImageItem::SIZE_LARGE:
					return "http://twitpic.com/show/large/{$this->_data['photo_key']}.jpg";
					break;												
				case ImageItem::SIZE_ORIGINAL:
					return "http://twitpic.com/show/large/{$this->_data['photo_key']}.jpg";
					break;
			}
		}
		elseif  ($this->_data['photo_service'] == 'phodroid') {	
			return "http://s.phodroid.com/{$this->_data['photo_key']}.jpg";
		}
		elseif ( $this->_data['photo_service'] == 'brizzly' ) {
			switch ($size) {
				default:
				case ImageItem::SIZE_THUMBNAIL:
				case ImageItem::SIZE_SMALL:
					return "http://pics.brizzly.com/thumb_sm_{$this->_data['photo_key']}.jpg";
					break;
				case ImageItem::SIZE_MEDIUM:
				case ImageItem::SIZE_LARGE:
					return "http://pics.brizzly.com/thumb_lg_{$this->_data['photo_key']}.jpg";
					break;
				case ImageItem::SIZE_ORIGINAL:
					return "http://pics.brizzly.com/{$this->_data['photo_key']}.jpg";
					break;
			}
		}
		else {
			return false;
		}
	}
	
	public function getLink() {
		if ($this->getType() == SourceItem::IMAGE_TYPE) {
			if ($this->_data['photo_service'] == 'twitpic') {	
				return "http://twitpic.com/{$this->_data['photo_key']}";
			} elseif  ($this->_data['photo_service'] == 'phodroid') {	
				return "http://phodroid.com/{$this->_data['photo_key']}";
			} elseif  ($this->_data['photo_service'] == 'brizzly') {
				return "http://brizzly.com/pic/{$this->_data['photo_key']}";
			}
		}
		
		$properties = new SourcesProperties(array(Stuffpress_Db_Properties::KEY => $this->_data['source_id']));
		$username   = $properties->getProperty('username');
		$url 		= "http://twitter.com/$username/statuses/{$this->_data['twitter_id']}";
		
		return $url;
	}
	
	public function getRssContent() {
		$url  = $this->getPhotoURL('-');
		$link = $this->getLink();
		$title = $this->_data['title'];
		$html = "<a href='$link'><img src='$url' alt='$title'></a>";
		return $html;
	}
	
	public function getBackup() {
		$item = array();
		$item['Id']					= $this->_data['twitter_id'];
		$item['Tweet']				= $this->_data['text'];
		$item['Source']				= $this->_data['source'];
		$item['Truncated']			= $this->_data['truncated'];
		$item['In reply to status']	= $this->_data['in_reply_to_status'];
		$item['In reply to user']	= $this->_data['in_reply_to_user_id'];
		$item['Timestamp']			= $this->_data['created_at'];
		return $item;
	}
}