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
class FlickrModel extends SourceModel {

	protected $_name 	= 'flickr_data';

	protected $_prefix = 'flickr';

	protected $_search  = 'title';
	
	protected $_actor_key = 'user_id';
	
	protected $_update_tweet = "Uploaded %d photos to Flickr %s"; 

	public function getServiceName() {
		return "Flickr";
	}

	public function isStoryElement() {
		return true;
	}

	public function getServiceURL() {
		if ($username = $this->getProperty('username')) {
			return "http://www.flickr.com/photos/$username";
		}
		else if ($user_id = $this->getProperty('user_id')) {
			return "http://www.flickr.com/photos/$user_id";
		}
		else {
			return "http://www.flickr.com/";;
		}
	}

	public function getServiceDescription() {
		return "Flickr is a picture sharing service.";
	}

	public function setTitle($id, $title) {
		$this->updateItem($id, array('title' => $title));
	}

	public function importData() {
		// Fetch the data from twitter
		$username = $this->getProperty('username');

		// fetch the API key from config
		$config	  = Zend_Registry::get('configuration');
		$key  	  = isset($config->flickr) ? $config->flickr->api_key : "";	

		try {
			$flickr   = new Zend_Service_Flickr($key);
			$user_id  = $flickr->getIdByUsername($username);
		}
		catch(Exception $e) {
			$user_url = urlencode("http://www.flickr.com/photos/$username"); 
			$url 	  = "http://api.flickr.com/services/rest/?method=flickr.urls.lookupUser&api_key=$key&url=$user_url&format=json&nojsoncallback=1";
			
			$curl = curl_init();
			curl_setopt($curl, CURLOPT_URL, $url);
			curl_setopt($curl, CURLOPT_HEADER, false);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curl, CURLOPT_CONNECTTIMEOUT,5);
			curl_setopt($curl, CURLOPT_USERAGENT,'Storytlr/1.0');
			
			$response = curl_exec($curl);
			$http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
			curl_close ($curl);
			
			if ($http_code != 200) {
				throw new Stuffpress_Exception("Bad Flickr return code $http_code searching for user $username at url $url ");
			}
			
			if (!($data = json_decode($response))) {
				throw new Stuffpress_Exception("Flickr did not return any result", 0);
			}
			
			if ($data->stat == 'ok') {
				$username = $data->user->username->_content;
				$user_id  = $data->user->id;
			} else {
				throw new Stuffpress_Exception("Flickr return {$data->stat} when asking for user $username at url $url ", 0);
			}
		}

		$this->setProperty('username', $username);
		$this->setProperty('user_id', $user_id);

		$items = $this->updateData(true);

		$this->setImported(true);
		
		return $items;
	}

	public function updateData($import=false) {
		// Fetch the data from twitter
		$user_id  = $this->getProperty('user_id');
		$username = $this->getProperty('username');

		// fetch the API key from config
		$config	  = Zend_Registry::get('configuration');
		$key  	  = $config->flickr->api_key;
		$count    = $import ? 250 : 100;
		$pages	  = $import ? 10  : 1;
		
		if (!$user_id) {
			$flickr   = new Zend_Service_Flickr($key);
			$user_id  = $flickr->getIdByUsername($username);
			$this->setProperty('user_id', $user_id);
		}
		
		$total = array();
		
		for ($page = 1; $page <= $pages; $page ++) {
			if (!$photos = $this->fetchFlickrPage($key, $user_id, $count, $page)) break;
			$total = array_merge($total, $photos);
			if (count($photos) < $count) break;
		}
		
		// Mark as updated (could have been with errors)
		$this->markUpdated();
		
		// Return number of new items
		return $total;
	}
	
	private function fetchFlickrPage($key, $user_id, $count=500, $page=1) {
		$url = "http://api.flickr.com/services/rest/?method=flickr.people.getPublicPhotos&api_key=$key&user_id=$user_id&extras=geo%2C+license%2C+tags%2C+date_upload%2C+date_taken%2C+owner_name%2C+icon_server&per_page=$count&page=$page&format=json&nojsoncallback=1 ";

		$lastupdate = strtotime($this->getLastUpdate());
		
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_HEADER, false);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_TIMEVALUE, $this->getLastUpdate());
		

		$response = curl_exec($curl);
		$http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		curl_close ($curl);

		if ($http_code == 304) {
			return;
		}
		
		if ($http_code != 200) {
			throw new Stuffpress_Exception("Flickr API returned http status $http_code for url: $url", $http_code);
		}

		if (!($data = json_decode($response))) {
			throw new Stuffpress_Exception("Flickr did not return any result", 0);
		}

		$photos = $data->photos;

		if (count($photos->photo) == 0) {
			return;
		}

		return $this->storePhotos($photos);
	}

	private function storePhotos($photos) {
		$properties	= new Properties(array(Properties::KEY => $this->_source['user_id']));
		$current_tz	= date_default_timezone_get();
		$user_tz	= $properties->getProperty('timezone');

		date_default_timezone_set($user_tz);
		$result = array();
		for ($i = 0; $i < count($photos->photo); $i++) {
			$photo 				= $photos->photo[$i];
			
			$data				= array();
			$data['photo_id']	= $photo->id;
			$data['secret']		= $photo->secret;
			$data['server']		= $photo->server;
			$data['datetaken']	= $photo->datetaken;
			$data['dateupload']	= $photo->dateupload;
			$data['title']		= $photo->title;
			$data['owner']		= $photo->owner;
			
			$location			   = array();
			$location['latitude']  = $photo->latitude;
			$location['longitude'] = $photo->longitude;
			$location['accuracy']  = $photo->accuracy;
			
			$tags				= (strlen($photo->tags) > 0) ? explode(' ', $photo->tags) : false;
			
			$id = $this->addItem($data, strtotime($data['datetaken']), SourceItem::IMAGE_TYPE, $tags, $location, false, $data['title']);
						
			if ($id) $result[] = $id;
		}
		date_default_timezone_set($current_tz);

		return $result;
	}
	
	public function processGnipItem($activity) {
		if ($activity->action != 'upload') {
			return;
		}
		$this->updateData();
	}
}
