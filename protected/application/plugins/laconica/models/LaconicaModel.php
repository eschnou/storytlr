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
class LaconicaModel extends SourceModel {

	protected $_name 	= 'laconica_data';

	protected $_prefix  = 'laconica';

	protected $_search  = 'text';
	
	protected $_update_tweet = "Updated %d times my Identi.ca status %s"; 

	public function getServiceName() {
		return "Identi.ca";
	}

	public function getServiceURL() {
		if ($username = $this->getProperty('username')) {
			if (($pos = strpos($username, '@')) == false) {
				return "http://identi.ca/$username";
			} else {
				$host = substr($username, $pos+1);
				$username = substr($username, 0, $pos);		
				return "http://$host/$username";		
			}
		}
	}

	public function getServiceDescription() {
		return "Identi.ca is a micro-blogging platform.";
	}

	public function isStoryElement() {
		return true;
	}
	
	public function importData() {
		$username = $this->getProperty('username');

		if (!$username) {
			throw new Stuffpress_Exception("Update failed, connector not properly configured");
		}
		
		$items = $this->updateData(true);
		
		$this->setImported(true);
		return $items;
	}

	public function updateData($import = false) {
		// Get service propertie
		$config = Zend_Registry::get("configuration");
		$pages = $import ? 25  : 1;
		$count = $import ? 200 : 50;
		
		// Get user properties
		if (!$username = $this->getProperty('username')) {
			throw new Stuffpress_Exception("Update failed, connector not properly configured");
		}
		
		// Is this identica or another server ?
		if ($pos = strpos($username, '@')) {
			$host = substr($username, $pos+1);
			$username = substr($username, 0, $pos);
		}
		
		// API base
		$base = isset($host) ? "http://$host/api" : "http://identi.ca/api";

		// Fetch the data from twitter
		$result = array();
		for($page=0; $page< $pages; $page ++) {
			$offset = $page * ($count / 10) + 1;
			$url  = "$base/statuses/user_timeline/$username.xml?count=$count&page=$offset";
			$curl = curl_init();
			curl_setopt($curl, CURLOPT_URL, $url);
			curl_setopt($curl, CURLOPT_HEADER, false);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); 
			curl_setopt($curl, CURLOPT_USERAGENT,'Storytlr/1.0');
		
			$response = curl_exec($curl);
			$http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
			curl_close ($curl);

			if ($http_code != 200) {
				throw new Stuffpress_Exception("Identica API returned http status $http_code for url: $url", $http_code);
			}

			if (!($data = simplexml_load_string($response))) {
				throw new Stuffpress_Exception("Identica did not return any result for url: $url", 0);
			}

			if (count($data) == 0) {
				break;
			}
			
			$items = $this->processItems($data);
			$size  = count($items);
			$result = array_merge($result,$items);
			
			if ($size < $count) {
				break;
			}
		}
							
		// Mark as updated (could have been with errors)
		$this->markUpdated();

		return $result;
	}

	public function getConfigForm($populate=false) {
		$form = new Stuffpress_Form();

		// Add the blog url element
		$label	 = $this->getServiceName(). " username";
		$element = $form->createElement('text', 'username', array('label' => $label , 'decorators' => $form->elementDecorators));
		$element->setRequired(true);
		$element->setDescription("To connect to an independent Laconi.ca server, enter username@host as your username");
		$form->addElement($element);
		
		// Options
		$options = array();
		if ($this->getPropertyDefault('hide_replies')) $options[] = 'hide_replies';
		$e = new Zend_Form_Element_MultiCheckbox('options', array(
			'decorators' => $form->elementDecorators,
			'multiOptions' => array(
			'hide_replies' 	=> 'Hide @replies status'
			)
		)); 
		$e->setLabel('Options');
		$e->setValue($options);
		$form->addElement($e);
		
		if($populate) {
			$options = array();
			$values  = $this->getProperties();
			if ($this->getProperty('hide_replies')) $options[]='hide_replies';
			$values['options'] = $options;
			$form->populate($values);
		}

		return $form;
	}

	public function processConfigForm($form) {
		$values   = $form->getValues();
		$username =  $values['username'];
		$options  = $values['options'];
		$update	  = false;

		// Save twitter username
		if($username != $this->getProperty('username')) {
			$this->_properties->setProperty('username',   $username);
			$update = true;
		}
		
		// Save hide_replies property
		$hide_replies = @in_array('hide_replies',$options) ? 1 : 0;
		$this->_properties->setProperty('hide_replies', $hide_replies);

		return $update;
	}
	
	public function getPublisher() {
		return "identica";
	}

	public function processGnipItem($activity) {
		
		if (!$item = @new SimpleXMLElement($activity->payload->decodedRaw())) {
			return;
		}
		
		if ($activity->action != 'notice') {
			return;
		}
		
		$text							= $item->title;
		$text							= substr($text, strpos($text, ':') + 2);
		if ($photo = $this->getPhoto($item->text)) {
				$data['photo_key'] 			= $photo['key'];
				$data['photo_service']		= $photo['service'];					
		}
		$data = array();
		$data['created_at'] 			= $item->published;
		$data['status_id']				= $item->status_id;
		$data['text'] 					= $text;
		$data['source'] 				= $item->from_source->asXML();
		$data['truncated']				= 0;
		$data['in_reply_to_status'] 	= $item->in_reply_to_status_id;
		$data['in_reply_to_user_id']	= $item->in_reply_to_user_id;

		$hide_replies = $this->getProperty('hide_replies');
		$is_reply  = (substr($text, 0, 1) == '@') ? true : false;
		$is_hidden = ($is_reply && $hide_replies) ? 1 : 0;
 		$type = $photo ? SourceItem::IMAGE_TYPE : SourceItem::STATUS_TYPE;       		
		
 		$this->addItem($data, strtotime($item->published), $type, false, false, $is_hidden, $data['text']);
	}
	
	
	private function processItems($items) {
		$result = array();
		if ($items && count($items)>0) foreach ($items as $item) {
			$data = array();
			if ($photo = $this->getPhoto($item->text)) {
				$data['photo_key'] 			= $photo['key'];
				$data['photo_service']		= $photo['service'];					
			}
			$data['created_at'] 			= $item->created_at;
			$data['status_id']				= $item->id;
			$data['text'] 					= $item->text;
			$data['source'] 				= $item->source;
			$data['truncated']				= ($item->truncated == "false") ? 0:1;
			$data['in_reply_to_status'] 	= $item->in_reply_to_status_id;
			$data['in_reply_to_user_id']	= $item->in_reply_to_user_id;

			$hide_replies = $this->getProperty('hide_replies');
			$is_reply  = (substr($item->text, 0, 1) == '@') ? true : false;
			$is_repost = @in_array(strip_tags($data['source']), array('storytlr'));
			$is_hidden = ($is_repost || ($is_reply && $hide_replies)) ? 1 : 0;
			$type = $photo ? SourceItem::IMAGE_TYPE : SourceItem::STATUS_TYPE;
			
			$id = $this->addItem($data, strtotime($item->created_at), $type, false, false, $is_hidden, $data['text']);
			if ($id) $result[] = $id;	
		}
		return $result;
	}
	
	private function getPhoto($status) {
		$matches = array();
		
		// Do we have a twitpic ?
		if (preg_match("/twitpic.com\/(\w+)/i",$status,$matches)) {
			$photo['key'] = $matches[1];
			$photo['service'] = 'twitpic';
			return $photo;
		} 
		// Do we have an android ?
		elseif (preg_match("/phodroid.com\/(\w+)/i",$status,$matches)) {
			$photo['key'] = $matches[1];
			$photo['service'] = 'phodroid';
			return $photo;
		} else {
			return false;
		}
	}

	private function getLastID() {
		$sql  = "SELECT laconica_id FROM `$this->_name` WHERE source_id = :source_id ORDER BY id DESC";
		$data = array(":source_id" => $this->_source['id']);
		$id = $this->_db->fetchOne($sql, $data);
		echo "Fetched the ID $id for Laconica.\r\n";
		return $id;
	}

}
