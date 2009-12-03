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
class TwitterModel extends SourceModel {

	protected $_name 	= 'twitter_data';

	protected $_prefix  = 'twitter';

	protected $_search  = 'text';
	
	protected $_update_tweet = "Updated %d times my Twitter status %s"; 

	public function getServiceName() {
		return "Twitter";
	}

	public function getServiceURL() {
		if ($username = $this->getProperty('username')) {
			return "http://www.twitter.com/$username";
		}
		else {
			return "http://www.twitter.com/";
		}
	}

	public function getServiceDescription() {
		return "Twitter is a micro-blogging platform.";
	}

	public function isStoryElement() {
		return true;
	}
	
	public function importData() {
		$username = $this->getProperty('username');

		if (!$username) {
			throw new Stuffpress_Exception("Update failed, connector not properly configured");
		}
		
		// Proceed with the update
		$items = $this->updateData(true);

		$this->setImported(true);
		return $items;
	}

	public function updateData($import = false) {
		// Get service propertie
		$config = Zend_Registry::get("configuration");
		$user  = $config->twitter->username;
		$pwd   = $config->twitter->password;
		$pages = $import ? 32  : 1;
		$count = $import ? 100 : 50;
		
		// Get user properties
		$username = $this->getProperty('username');
		$uid	  = $this->getProperty('uid', 0);

		if (!$username) {
			throw new Stuffpress_Exception("Update failed, connector not properly configured");
		}
		
		// Update the user uid if required
		if (!$uid || $uid==0) {
			$uid = $this->getTwitterUid($username);	
			if ($uid > 0) {
				$this->setProperty('uid', $uid);
			}
		}

		// Fetch the data from twitter
		$result = array();
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_HEADER, false);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_USERPWD, "$user:$pwd"); 
		curl_setopt($curl, CURLOPT_USERAGENT,'Storytlr/1.0');
		
		for($page=1; $page<= $pages; $page ++) {
			$url  = "http://twitter.com/statuses/user_timeline/$username.xml?count=$count&page=$page";
			curl_setopt($curl, CURLOPT_URL, $url);
			$response = curl_exec($curl);
			$http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
			
			if ($http_code != 200) {
				throw new Stuffpress_Exception("Twitter API returned http status $http_code for url: $url", $http_code);
			}

			if (!($data = simplexml_load_string($response))) {
				throw new Stuffpress_Exception("Twitter did not return any result for url: $url", 0);
			}

			if (count($data) == 0) {
				break;
			}

			$items = $this->processItems($data);	
			$result = array_merge($result,$items);
			
			if (count($data) < $count) {
				break;
			}
		}
		
		curl_close ($curl);			
		unset($curl);

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
		$form->addElement($element);
		
		// Options
		$options = array();
		if ($this->getPropertyDefault('hide_replies')) $options[] = 'hide_replies';
		$e = new Zend_Form_Element_MultiCheckbox('options', array(
			'decorators' => $form->elementDecorators,
			'multiOptions' => array(
			'hide_replies' 	=> 'Hide @replies tweets'
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

	public function processGnipItem($activity) {
		
		if (!$item = @new SimpleXMLElement($activity->payload->decodedRaw())) {
			return;
		}
		
		if ($activity->action != 'notice') {
			return;
		}
		
		$text							= $item->title;
		$text							= mb_substr($text, strpos($text, ':') + 2);
		
		$tags = $this->getTags($text);
		
		$data = array();
		if ($photo = $this->getPhoto($text)) {
				$data['photo_key'] 			= $photo['key'];
				$data['photo_service']		= $photo['service'];					
		}
		$data['created_at'] 			= $item->published;
		$data['twitter_id']				= $item->status_id;
		$data['text'] 					= $text;
		$data['source'] 				= $item->from_source->asXML();
		$data['truncated']				= 0;
		$data['in_reply_to_status'] 	= $item->in_reply_to_status_id;
		$data['in_reply_to_user_id']	= $item->in_reply_to_user_id;

		$hide_replies = $this->getProperty('hide_replies');
		$is_reply  = (mb_substr($text, 0, 1) == '@') ? true : false;
		$is_repost = @in_array(strip_tags($data['source']), array('storytlr'));		
		$is_hidden = ($is_repost || ($is_reply && $hide_replies)) ? 1 : 0;
 		$type = $photo ? SourceItem::IMAGE_TYPE : SourceItem::STATUS_TYPE;       		
		
 		if ($is_repost) return;
 		
 		$this->addItem($data, strtotime($item->published), $type, $tags, false, $is_hidden, $data['text']);
	}
	
	
	private function processItems($items) {
		$result = array();
		if ($items && count($items)>0) foreach ($items as $item) {
			$data = array();
			
			$tags = $this->getTags((string) $item->text);
			
			if ($photo = $this->getPhoto((string) $item->text)) {
				$data['photo_key'] 			= $photo['key'];
				$data['photo_service']		= $photo['service'];					
			}
			$data['created_at'] 			= (string) $item->created_at;
			$data['twitter_id']				= (string) $item->id;
			$data['text'] 					= (string) $item->text;
			$data['source'] 				= (string) $item->source;
			$data['truncated']				= ((string)$item->truncated == "false") ? 0:1;
			$data['in_reply_to_status'] 	= (string) $item->in_reply_to_status_id;
			$data['in_reply_to_user_id']	= (string) $item->in_reply_to_user_id;

			$hide_replies = $this->getProperty('hide_replies');
			$is_reply  = (mb_substr($data['text'], 0, 1) == '@') ? true : false;
			$is_repost = @in_array(strip_tags($data['source']), array('storytlr'));
			$is_hidden = ($is_repost || ($is_reply && $hide_replies)) ? 1 : 0;
			$type = $photo ? SourceItem::IMAGE_TYPE : SourceItem::STATUS_TYPE;
			
			if ($is_repost) continue;

			$id = $this->addItem($data, strtotime($data['created_at']), $type, $tags, false, $is_hidden, $data['text']);
			
			if ($id) $result[] = $id;	
			unset($data);
		}
		return $result;
	}
	
	public function processItem($item) {
		$result = array();
		$tags = $this->getTags((string) $item->text);
		
		if ($photo = $this->getPhoto((string) $item->text)) {
			$data['photo_key'] 			= $photo['key'];
			$data['photo_service']		= $photo['service'];					
		}
		
		$data['created_at'] 			= (string) $item->created_at;
		$data['twitter_id']				= (string) $item->id;
		$data['text'] 					= (string) $item->text;
		$data['source'] 				= (string) $item->source;
		$data['truncated']				= ((string)$item->truncated == "false") ? 0:1;
		$data['in_reply_to_status'] 	= (string) $item->in_reply_to_status_id;
		$data['in_reply_to_user_id']	= (string) $item->in_reply_to_screen_name;

		$hide_replies = $this->getProperty('hide_replies');
		$is_reply  = (mb_substr($data['text'], 0, 1) == '@') ? true : false;
		$is_repost = @in_array(strip_tags($data['source']), array('storytlr'));
		$is_hidden = ($is_repost || ($is_reply && $hide_replies)) ? 1 : 0;
		$type = $photo ? SourceItem::IMAGE_TYPE : SourceItem::STATUS_TYPE;
		
		if ($is_repost) continue;

		$id = $this->addItem($data, strtotime($data['created_at']), $type, $tags, false, $is_hidden, $data['text']);
		if ($id) $result[] = $id;	
			
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
	
	private function getTags($status) {
		$matches 	= array();
		preg_match_all("/#(\w+)/",$status,$matches);
		return $matches[1];
	}

	private function getLastID() {
		$sql  = "SELECT twitter_id FROM `$this->_name` WHERE source_id = :source_id ORDER BY id DESC";
		$data = array(":source_id" => $this->_source->id);
		$id = $this->_db->fetchOne($sql, $data);
		echo "Fetched the ID $id for Twitter.\r\n";
		return $id;
	}
	
	private function getTwitterUid($username) {
		$config = Zend_Registry::get("configuration");
		$user  = $config->twitter->username;
		$pwd   = $config->twitter->password;	

		$curl = curl_init();
		curl_setopt($curl, CURLOPT_HEADER, false);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_USERPWD, "$user:$pwd"); 
		curl_setopt($curl, CURLOPT_USERAGENT,'Storytlr/1.0');
		
		$url  = "http://twitter.com/users/show.json?screen_name=$username";
		curl_setopt($curl, CURLOPT_URL, $url);
		$response = curl_exec($curl);
		$http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
				
		if ($http_code != 200) {
			return 0;
		}		

		if ($response && strlen($response) > 0) {
			$twitter_user = json_decode($response);
			$uid = $twitter_user->id;
			return $uid;
		}	
		
		return 0;
	}
}
