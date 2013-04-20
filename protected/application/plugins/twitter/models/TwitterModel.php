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
			return "http://twitter.com/$username";
		}
		else {
			return "http://twitter.com/";
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
		$pages = $import ? 50  : 1;
		$count = $import ? 200: 50;
		
		// Get application properties
		$app_properties = new Properties(array(Stuffpress_Db_Properties::KEY => Zend_Registry::get("shard")));
		
		// Get twitter user properties
		$username = $this->getProperty('username');
		$uid	  = $this->getProperty('uid', 0);

		if (!$username) {
			throw new Stuffpress_Exception("Update failed, connector not properly configured");
		}

		// Get twitter consumer tokens and user secrets
		$consumer_key = $config->twitter->consumer_key;
		$consumer_secret = $config->twitter->consumer_secret;
		$oauth_token = $app_properties->getProperty('twitter_oauth_token');
		$oauth_token_secret = $app_properties->getProperty('twitter_oauth_token_secret');
		
		if (!$consumer_key || !$consumer_secret || !$oauth_token || !$oauth_token_secret) {
			throw new Stuffpress_Exception("Missing twitter credentials. Please configure your twitter account in the <a href='/admin/sns/'>Configure -> Social Networks</a> section.");
		}

		// Fetch the data from twitter
		$result = array();
		$connection = new TwitterOAuth_Client($consumer_key, $consumer_secret, $oauth_token, $oauth_token_secret);
		$max_id = false;
		$params = array('screen_name' => $username, 'count' => $count);
		
		for($page=1; $page<= $pages; $page ++) {
			if ($max_id) {
				$params['max_id'] = $max_id;
			}
			$response = $connection->get('statuses/user_timeline', $params);
	
			if ($response && isset($response->errors) && count($response->errors) > 0) {
				throw new Stuffpress_Exception($response->errors[0]->message);
			}
			
			if (count($response) == 0) {
				break;
			}
			
			$max_id = $response[count($response) -1]->id_str;
			$items = $this->processItems($response);
			
			if (count($items) == 0) {
				break;
			}
						
			$result = array_merge($result,$items);
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
		$data['twitter_id']				= (string) $item->id_str;
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
		}
		// Do we have brizzly?s
		elseif (preg_match("/brizzly.com\/pic\/(\w+)/i",$status,$matches)) {
			$photo['key'] = $matches[1];
			$photo['service'] = 'brizzly';
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

}
