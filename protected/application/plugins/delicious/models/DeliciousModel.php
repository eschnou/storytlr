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
class DeliciousModel extends SourceModel {

	protected $_update_tweet = "Added %d Delicious bookmarks %s"; 
	
	protected $_name 	= 'delicious_data';

	protected $_prefix = 'delicious';

	protected $_search  = 'title, subject, description';

	public function getServiceName() {
		return "Delicious";
	}

	public function isStoryElement() {
		return true;
	}
	
	public function getServiceURL() {
		if ($username = $this->getProperty('username')) {
			return "http://del.icio.us/$username";
		}
		else {
			return "http://del.icio.us/";;
		}
	}

	public function getServiceDescription() {
		return "Delicious is a social bookmarking service.";
	}

	public function importData() {
		$items = $this->updateData(true);
		$this->setImported(true);
		return $items;
	}

	public function updateData($import=false) {
		// Parameters
		$count = $import ? 100 : 10;
		
		// Require to avoid API throtthling
		sleep(1);

		// Verify that we have the required settings
		if (!($username = ($this->getProperty('username')))) {
			throw new Stuffpress_Exception("Update failed, connector not properly configured");
		}

		// Fetch the data from twitter
		$url 		= "http://feeds.delicious.com/v2/json/$username?count=$count";

		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_HEADER, false);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_USERAGENT,'Storytlr/1.0');

		$response = curl_exec($curl);
		$http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		curl_close ($curl);

		if ($http_code != 200) {
			throw new Stuffpress_Exception("Delicious API returned http status $http_code for url: $url", $http_code);
		}

		if (!($data = json_decode($response))) {
			throw new Stuffpress_Exception("Delicious did not return any result for url: $url", 0);
		}
			
		if (count($data) == 0) {
			return 0;
		}
				
		// Mark as updated (could have been with errors)
		$this->markUpdated();
			
		return $this->processItems($data);
	}

	private function processItems($items) {
		$result = array();
		
		for ($i = 0; $i < count($items); $i++) {
			$item				 = $items[$i];
			$tags				 = $item->t;
			$data				 = array();
			$data['title']		 = $item->d;
			$data['link']		 = $item->u;
			$data['dateposted']  = $item->dt;
			$data['description'] = $item->n;
			$data['subject']	 = implode(' ', $tags);

			$id = $this->addItem($data, strtotime((string) $item->dt), SourceItem::LINK_TYPE, $tags, false, false, $data['title']);
			if ($id) $result[] = $id;
		}
		
		return $result;
	}
	
	public function processGnipItem($activity) {	
		if ($activity->action != 'bookmark') {
			return;
		}
		return $this->updateData();
	}
}
