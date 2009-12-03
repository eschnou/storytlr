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

abstract class SourceModel extends Stuffpress_Db_Table
{
	protected $_source;

	protected $_properties;
	
	protected $_prefix;
	
	protected $_search;
	
	protected $_actor_key = 'username';
	
	protected $_update_tweet = "Added %d new entries on my Lifestream %s"; 
	
	private $_data_table;

	public static function newInstance($service, $source=null) {
		$class = ucfirst($service)."Model";
		$instance = new $class($source);
		return $instance;
	}

	abstract public function getServiceName();

	abstract public function getServiceURL();

	abstract public function getServiceDescription();

	abstract public function importData();

	abstract public function updateData();

	public function __construct($source=null) {
		parent::__construct();
		if ($source) {
			$this->setSource($source);
		}
	}
	
	public function getServicePrefix() {
		return $this->_prefix;
	}

	public function getAccountName() {
		return $this->getProperty('username');
	}

	public function getTitle() {
		return $this->getServiceName();
	}
	
	public function getSource() {
		return $this->_source;
	}
	
	public function getSearchIndex() {
		return $this->_search;
	}

	public function setSource($source) {
		$this->_source = $source;
		$this->_properties = new SourcesProperties(array(Properties::KEY => $this->_source['id']));
		
		$this->setUser($this->_source['user_id']);
		
		if (isset($this->_data_table)) {
			$this->_data_table->setUser($this->_source['user_id']);
		}
	}
	
	public function getProperties() {
		return $this->_properties->getPropertiesArray();
	}
	
	public function getProperty($key, $default=false) {
			
		if (!$default) {
			$default = $this->getPropertyDefault($key);
		}
		
		if (!isset($this->_properties)) {
			return $default;
		}
		
		return $this->_properties->getProperty($key, $default);
	}
	
	public function getPropertyDefault($key) {
		$config = Zend_Registry::get("configuration");
		$prefix = $this->_prefix;
		
		if (isset($config->$prefix->default->$key)) {
				return 	$config->$prefix->default->$key;
		} else {
				return false;	
		}
	}
	
	public function setProperty($key, $value) {
		$this->_properties->setProperty($key, $value);
	}

	public function isImported() {
		return $_source['imported'];
	}

	public function setImported($bool) {
		$sources = new Sources();
		$sources->setImported($this->_source['id'], $bool);
	}

	public function isPublic() {
		return $this->_source['public'];
	}

	public function isEnabled() {
		return $this->_source['enabled'];
	}
	
	public function isStoryElement() {
		return false;
	}
	
	public function getID() {
		return $this->_source['id'];
	}
	
	public function getUserID() {
		return $this->_source['user_id'];
	}
	
	public function getLastUpdate() {
		return $this->_source['last_update'];
	}
	
	public function getItem($id) {
		return $this->fetchRow($this->select()->where('`id` = ?', $id));
	}
	
	public function deleteItems() {
		$where = $this->getAdapter()->quoteInto('source_id = ?', $this->_source['id']);
		$this->delete($where);
	}
	
	public function deleteItem($id) {
		$where = $this->getAdapter()->quoteInto('id = ?', $id);
		$this->delete($where);
	}
	
	public function addItem($data, $timestamp, $type, $tags=false, $location=false, $hidden=false, $title=false) {
		$data['source_id'] 	= $this->_source['id'];
		$columns   			= array();
		$keys      			= array();
		$timestamp 			= ($timestamp>=0) ? $timestamp : 0;
		 
		foreach($data as $k => $v) {
			unset($data[$k]);
			if (!$v) continue;
			$columns[] = "$k";
			$keys[] = ":$k";
			$data[":$k"] = "$v";
		}
		
		$sql = "INSERT IGNORE INTO {$this->_name} (".implode(',', $columns).") "
			 . "VALUES(".implode(',', $keys).")";

		$this->_db->query($sql, $data);
		
		if (!$id = (int) $this->_db->lastInsertId()) {
			return;
		}
		
		$data_table = $this->getDataTable();
		$data_table->addItem($id, $this->_source['id'], $this->_source['user_id'], $this->_prefix, $type, $timestamp, $hidden);
		$data_table->setTags($this->_source['id'], $id, $tags);
		$data_table->setSlug($this->_source['id'], $id, Stuffpress_Permalink::entry($this->_source['id'], $id, $title));
		
		if ($location) {
			$latitude  = @$location['latitude'];
			$longitude = @$location['longitude'];
			$elevation = @$location['elevation'];
			$accuracy  = @$location['accuracy'];
			if ($latitude && $longitude) {
				$data_table->setLocation($this->_source['id'], $id, $latitude, $longitude, $elevation, $accuracy);
			}
		}
		
		return $id;
	}
	
	public function updateItem($id, $data, $timestamp=false) {		
		$where = $this->getAdapter()->quoteInto('id = ?', $id);
		$this->update($data, $where);

		if ($timestamp) {
			$data = new Data();
			$data->setTimestamp($this->_source['id'], $id, $timestamp);
		}
	}

	public function setTimestamp($id, $timestamp) {		
		$data = new Data();
		$data->setTimestamp($this->_source['id'], $id, $timestamp);
	}
	
	
	public function markUpdated() {
		if (!($id = $this->getID())) return;
		$sql = "UPDATE `sources` SET last_update = CURRENT_TIMESTAMP WHERE `id`=:source_id";
		$data = array(":source_id" 	=> $id);
		$stmt 	= $this->_db->query($sql, $data);
	}
	
	public function getConfigForm($populate=false) {
		$form = new Stuffpress_Form();
		
		// Add default username element
		$label	 = $this->getServiceName(). " username";
		$element = $form->createElement('text', 'username', array('label' => $label , 'decorators' => $form->elementDecorators));
		$element->setRequired(true);
		$form->addElement($element);  
		
		if($populate) {
			$form->populate($this->getProperties());
		}

		return $form;
	}
	
	public function processConfigForm($form) {
		$values = $form->getValues();
		$this->_properties->setProperty('username', $values['username']);
		return true;
	}
	
	public function getSourcesForActor($actor) {
		$sql = "SELECT s.id FROM sources s LEFT JOIN sources_properties p ON (s.id = p.source_id) "
		. "WHERE s.service = :service AND p.key = :key AND p.value = :actor ";
			
		$data = array(':service' => $this->_prefix,
					  ':key' 	 => $this->_actor_key,
					  ':actor' 	 => $actor);

		$stmt 	= $this->_db->query($sql, $data);
		$rows   = $stmt->fetchAll(Zend_Db::FETCH_COLUMN, 0);
			
		return $rows;
	}
	
	public function getPublisher() {
		return $this->_prefix;
	}
	
	public function onNewItems($items) {
		if ($items && count($items) > 0) {
			$s 		= array();
			$data 	= new Data();
			foreach($items as $i) {
				$d = $data->getItem($this->_source['id'], $i);
				if (!$d->isHidden()) $s[] = $i;
			}
			if (count($s) >0) {
				$this->updateTwitter($s);
			}
		}
	}
	
	protected function updateTwitter($items) {
		// Get the user
		$users = new Users();
		$user  = $users->getUser($this->getUserID());
		
		// Get twitter credentials
		$properties = new Properties(array(Properties::KEY => $user->id));
		$auth	    = $properties->getProperty('twitter_auth');
		$services   = $properties->getProperty('twitter_services');
		$username   = $properties->getProperty('twitter_username');
		$password   = $properties->getProperty('twitter_password');
		$has_preamble   = $properties->getProperty('preamble', true);
		
		// Return if not all conditions are met
		if (!$auth || !in_array($this->getID(), unserialize($services))) {
			return;
		}
		
		// Get an item
		$count		= count($items);
		$data		= new Data();
		$source_id	= $this->_source['id'];
					
		if ($count <= 3) {
			foreach($items as $id) {
				$item		= $data->getItem($source_id, $id);
				$title		= strip_tags($item->getTitle());
				$service	= $this->getServiceName();
				
				if (($item->getType() == SourceItem::STATUS_TYPE ) && strlen($title) < 140) {
					$tweet = $title;
				} 
				
				else {
					$preamble = $has_preamble ? $item->getPreamble() : "";
					$tweet	  = $preamble . $title;
					if (strlen($tweet) > 119) $tweet = substr($tweet, 0, 115) . "[..]";
					$db_ShortUrls 	= new ShortUrls();
					$hash 	= $db_ShortUrls->addUrlForItem($user->id, $source_id, $id);
					$tweet 	= "$tweet http://st.tl/$hash";
				}
				
				try {
					$twitter = new Stuffpress_Services_Twitter($username, $password);
					$twitter->sendTweet($tweet);
				} catch (Exception $e) {}
			}
		} else {
			$selection = array();
			foreach($items as $i) {
				$selection[] = array($source_id, $i);
 			}
 			$db_ShortUrls 	= new ShortUrls();
			$hash 	= $db_ShortUrls->addUrlForSelection($user->id, serialize($selection));
			$url    = "http://st.tl/$hash";
			$tweet  = sprintf($this->_update_tweet, $count, $url);
			try {
				$twitter = new Stuffpress_Services_Twitter($username, $password);
				$twitter->sendTweet($tweet);
			} catch (Exception $e) {}			
			}
	}
	
	private function getDataTable() {
		if (!$this->_data_table) {
			$this->_data_table = new Data();
		}
		return $this->_data_table;
	}
}


