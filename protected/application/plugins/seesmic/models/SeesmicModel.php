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
class SeesmicModel extends SourceModel {

	protected $_name 	= 'seesmic_data';

	protected $_prefix = 'seesmic';

	protected $_search  = 'title';
	
	protected $_update_tweet = "Posted %d videos to Seesmic %s"; 

	public function getServiceName() {
		return "Seesmic";
	}

	public function getServiceURL() {
		if ($username = $this->getProperty('username')) {
			return "http://www.seesmic.com/$username";
		}
		else {
			return "http://www.seesmic.com/";;
		}
	}

	public function getServiceDescription() {
		return "Seesmic is enabling the video conversation accross the web.";
	}
	
	public function isStoryElement() {
		return true;
	}

	public function importData() {
		$items = $this->updateData(true);
		$this->setImported(true);
		return $items;
	}

	public function updateData($import=false) {
		// Fetch the data from twitter
		$username   = $this->getProperty('username');
		$pages		= $import ? 50 : 1;
		$count		= 50;
		$result 	= array();
		
		for ($page = 0; $page<$pages; $page++) {	
			$url 		= "http://api.seesmic.com/users/$username/videos.json?pagesize=$count&offset=$page";
			
			$curl = curl_init();
			curl_setopt($curl, CURLOPT_URL, $url);
			curl_setopt($curl, CURLOPT_HEADER, false);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curl, CURLOPT_USERAGENT,'Storytlr/1.0');
			
			$response = curl_exec($curl);
			$http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
			curl_close ($curl);
	
			if ($http_code != 200) {
				throw new Stuffpress_Exception("Seesmic API returned http status $http_code for url: $url", $http_code);
			}
	
			if (!($data = json_decode($response))) {
				throw new Stuffpress_Exception("Seesmic did not return any result for url: $url", 0);
			}
	
			if (count($data) == 0) break;
			
			$items 	= $this->processItems($data);
			$result = array_merge($result, $items);
			
			if (count($items)<$count) {
				break;
			}
		}

		// Mark as updated (could have been with errors)
		$this->markUpdated();
		
		return $result;
	}

	public function getItems($count=null, $offset=null, $show_hidden=false) {
		$select = $this->select();
		$select->where('source_id = ?', $this->_source['id']);
		if (!$this->getProperty('show_replies')) {
			$select->where('to_username = ""');
		}
		if (!$show_hidden) {
			$select->where('_is_hidden = 0');
		}
		$select->limit($count, $offset);
		$select->order('timestamp DESC');
		$rows = $this->fetchAll($select);
		$items = $this->arrayToItems($rows);
		return $items;
	}

	public function getItemsByDate($from, $to, $show_hidden=false) {
		$select = $this->select();
		$select->where('source_id = ?', $this->_source['id']);
		if (!$this->getProperty('show_replies')) {
			$select->where('to_username = ""');
		}
		if (!$show_hidden) {
			$select->where('_is_hidden = 0');
		}
		$select->where('UNIX_TIMESTAMP(`timestamp`) > ? ', $from);
		$select->where('UNIX_TIMESTAMP(`timestamp`) < ?', $to);
		$select->order('timestamp DESC');
		$rows = $this->fetchAll($select);
		$items = $this->arrayToItems($rows);
		return $items;
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
			'hide_replies' 	=> 'Hide @replies videos'
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
		$username = $values['username'];
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
		//
	}

	private function processItems($items) {
		$result = array();
		foreach ($items as $item) {
			$data		= array();

			$data['title'] 			= $item->title;
			$data['url_player']		= $item->url_player;
			$data['created_at']		= $item->created_at;
			$data['url_flv']		= $item->url_flv;
			$data['video_id']		= $item->video_id;
			$data['platform_id']	= $item->platform_id;
			$data['to_username']	= isset($item->to_username) ? $item->to_username : '';
				
			$hide_replies = $this->getProperty('hide_replies');
			$is_hidden = (isset($item->to_username) && $hide_replies) ? 1 : 0;
			
			$id = $this->addItem($data, strtotime($data['created_at']), SourceItem::VIDEO_TYPE, false, false, $is_hidden, $data['title']);
			if ($id) $result[] = $id;				
		}
		return $result;
	}
}
