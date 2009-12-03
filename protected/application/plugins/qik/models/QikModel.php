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
class QikModel extends SourceModel {

	protected $_name 	= 'qik_data';
	
	protected $_prefix = 'qik';
	
	protected $_search  = 'title, description';
	
	protected $_update_tweet = "Broadcasted %d live videos with Qik %s"; 
	
	public function getServiceName() {
		return "Qik";
	}

	public function getServiceURL() {
		if ($username = $this->getProperty('username')) {
			return "http://www.qik.com/$username";
		}
		else {
			return "http://www.qik.com/";;
		}
	}

	public function getServiceDescription() {
		return "Qik is a live video broadcasting service for your mobile.";
	}

	public function isStoryElement() {
		return true;
	}
	
	public function importData() {
		$items = $this->updateData();
		$this->setImported(true);
		return $items;
	}
	
	public function updateData() {		

		$username   = strtolower($this->getProperty('username'));
		$url 		= "http://qik.com/$username/latest-videos";
		
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_HEADER, false);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_USERAGENT,'Storytlr/1.0');
		
		$response = curl_exec($curl);
		$http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		curl_close ($curl);
		
		if ($http_code != 200) {
			throw new Stuffpress_Exception("Qik API returned http status $http_code for url: $url", $http_code);
		}
		
		if (!($items = simplexml_load_string($response))) {
			throw new Stuffpress_Exception("Qik did not return any result for url: $url", 0);
		}
		
		// Mark as updated (could have been with errors)
		$this->markUpdated();

		return $this->processItems($items->channel->children());
	}
	
	private function processItems($items) {
		$result = array();
		foreach ($items as $k => $v) {
			if ($k=="item") {
				$data		= array();

				$data['title'] 		= $v->title;
				$data['description']= $v->description;
				$data['pubDate']	= $v->pubDate;
				$data['link']	 	= $v->link;
				$data['urlflv']		= isset($v->enclosure[0]) ? $v->enclosure[0]->attributes()->url : "";
				$data['url3gp']		= isset($v->enclosure[1]) ? $v->enclosure[1]->attributes()->url : "";
				
				$latitude = false;
				$longitude = false;
				$ns_media	= $v->children('http://search.yahoo.com/mrss/');
				foreach($ns_media->text as $t) {
					$matches = array();

					if (preg_match("/latitude\:(?<lat>[\-\d\.]*)$/i", $t, $matches)) {
						$latitude = $matches['lat'];
					}
					
					if (preg_match("/longitude\:(?<lon>[\-\d\.]*)$/i", $t, $matches)) {
						$longitude = $matches['lon'];
					}
				}
				
				if ($latitude && $longitude) {
					$location['latitude'] = $latitude;
					$location['longitude'] = $longitude;
				} else {
					$location = false;
				}
				
				
				$id = $this->addItem($data, strtotime($data['pubDate']), SourceItem::VIDEO_TYPE, false, $location, false, $data['title']);
				if ($id) $result[] = $id;					
			}
		}
		return $result;
	}
}
