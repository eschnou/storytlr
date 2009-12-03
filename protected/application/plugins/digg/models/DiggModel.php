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
class DiggModel extends SourceModel {

	protected $_name 	= 'digg_data';

	protected $_prefix = 'digg';
	
	protected $_update_tweet = "Dugg %d new stories %s"; 

	public function getServiceName() {
		return "Digg";
	}
	
	public function isStoryElement() {
		return true;
	}

	public function getServiceURL() {
		if ($username = $this->getProperty('username')) {
			return "http://digg.com/users/$username";
		}
		else {
			return "http://digg.com/";;
		}
	}

	public function getServiceDescription() {
		return "Digg is a place for people to discover and share content across the web, from the biggest online destinations to the most obscure blog.";
	}

	public function importData() {
		$items = $this->updateData(true);
		$this->setImported(true);
		return $items;
	}

	public function updateData($import=false) {
		// Parameters
		$count = $import ? 100 : 10;
		
		// Verify that we have the required settings
		if (!($username = ($this->getProperty('username')))) {
			throw new Stuffpress_Exception("Update failed, connector not properly configured");
		}

		// Fetch the data from twitter
		$url 		= "http://services.digg.com/user/$username/dugg?count=$count&appkey=http://storytlr.com&type=json";

		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_HEADER, false);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_USERAGENT,'Storytlr/1.0');
		
		$response = curl_exec($curl);
		$http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		curl_close ($curl);
	
		if ($http_code != 200) {
			throw new Stuffpress_Exception("Digg API returned http status $http_code for url: $url", $http_code);
		}

		if (!($data = json_decode($response))) {
			throw new Stuffpress_Exception("Digg did not return any result for url: $url", 0);
		}
			
		if (!isset($data->stories) || count($data->stories) == 0) {
			return 0;
		}
				
		// Mark as updated (could have been with errors)
		$this->markUpdated();
		
		return $this->processItems($data->stories, $import);
	}
	
	public function processGnipItem($activity) {	
		//
	}

	private function processItems($stories,$import=false) {
		$result = array();
				
		foreach ($stories as $story) {
			$data				 = array();
			$data['digg_id']	 = $story->id;
			$data['submit_date'] = $import ? $story->submit_date : time();
			$data['diggs']		 = $story->diggs;
			$data['comments']	 = $story->comments;
			$data['title']		 = $story->title;
			$data['description'] = ''; //$story->description;
			$data['status']		 = $story->status;
			$data['media']		 = $story->media;
			$data['topic']		 = $story->topic->name;
			$data['container']	 = $story->container->name;
			$data['href']		 = $story->href;
			$data['link']		 = $story->link;
			
			$id = $this->addItem($data, $data['submit_date'], SourceItem::LINK_TYPE, false, false, false, $data['title']);
			if ($id) $result[] = $id;
		}
		
		return $result;
	}
}
