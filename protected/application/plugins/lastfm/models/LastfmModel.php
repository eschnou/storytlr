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
class LastfmModel extends SourceModel {

	protected $_name 	= 'lastfm_data';
	
	protected $_prefix = 'lastfm';
	
	protected $_search  = 'artist, name';
	
	protected $_update_tweet = "Liked %d songs on Last.fm %s"; 
	
	public function getServiceName() {
		return "Lastfm";
	}
	
	public function isStoryElement() {
		return true;
	}
	
	public function getServiceURL() {
		if ($username = $this->getProperty('username')) {
			return "http://www.lastfm.com/user/$username";
		}
		else {
			return "http://www.lastfm.com/";
		}
	}

	public function getServiceDescription() {
		return "Last.fm is a social network for music lovers.";
	}
	
	public function importData() {
		$items = $this->updateData();
		$this->setImported(true);
		return $items;
	}
	
	public function updateData() {		
		// Verify that we have the required settings
		if (!($username = $this->getProperty('username'))) {
			throw new Stuffpress_Exception("Update failed, username not properly configured");
		}
		
		// Fetch the data from twitter
		$url		= "http://ws.audioscrobbler.com/1.0/user/$username/recentlovedtracks.xml";
		
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_HEADER, false);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_USERAGENT,'Storytlr/1.0');
		
		$response = curl_exec($curl);
		$http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		curl_close ($curl);
		
		if ($http_code != 200) {
			throw new Stuffpress_Exception("Lastfm API returned http status $http_code for url: $url", $http_code);
		}
		
		if (!($items = simplexml_load_string($response))) {
			throw new Stuffpress_Exception("Last.fm did not return any result", 0);
		}
		
		if (count($items) == 0) {
			return;
		}
							
		// Mark as updated (could have been with errors)
		$this->markUpdated();
			
		return $this->processItems($items);
	}
	
	private function processItems($items) {
		$result = array();
		foreach ($items as $item) {
			$data 				= array();
			$data['source_id'] = $this->_source['id'];
			$data['artist'] 	= $item->artist;
			$data['name']		= $item->name;
			$data['url']		= $item->url;
			$date				= $item->date->attributes();
			$data['date']		= $date->uts;
			$title				= $data['name'] . " by " . $data['artist'];
			$id = $this->addItem($data, $data['date'], SourceItem::LINK_TYPE, false, false, false, $title);
			if ($id) $result[] = $id;			
		}
		return $result;
	}
}
