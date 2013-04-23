<?php
/*
 *    Copyright 2008-2013 Laurent Eschenauer and Alard Weisscher
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
class SoundcloudModel extends SourceModel {

	protected $_name 	= 'soundcloud_data';

	protected $_prefix = 'soundcloud';

	protected $_search  = 'title, description';

	protected $_update_tweet = "%d tracks added from Soundcloud %s";

	public function getServiceName() {
		return "Soundcloud";
	}

	public function getServiceURL() {
		if ($username = $this->getProperty('username')) {
			return "http://www.soundcloud.com/".$username;
		}
		else {
			return "http://www.soundcloud.com/";;
		}
	}

	public function getServiceDescription() {
		return "Soundcloud is a music sharing site.";
	}

	public function isStoryElement() {
		return false;
	}

	public function importData() {
		// We first need to figure out the userid.
		$username = $this->getProperty('username');
		$userid = $this->lookupUserid($username);
		
		// If no userid, give up
		if (!$userid) {
			throw new Stuffpress_Exception("Failed at looking up usedid for username " . $username);
		}
		
		// Store the userid
		$this->setProperty('userid', $userid);
		
		// Then we import the favorites
		$tracks = $this->updateTracks(true);
		$this->setImported(true);
		return $tracks;
	}

	public function updateData() {
		$tracks = $this->updateTracks();
		$this->markUpdated();
		return $tracks;
	}

	public function updateTracks($import=false) {
		$userid 	= $this->getProperty('userid');
		$result		= array();
		
    	$uri  = "http://api.soundcloud.com/users/" . $userid . "/favorites.json?client_id=YOUR_CLIENT_ID";
		$data = $this->fetchItems($uri);
				
		$items 	= $this->addItems($data);
	
		return $items;
	}

	private function fetchItems($url) {		
		$response = $this->curl($url);
		
		if ($response["http_code"] != 200) {
			throw new Stuffpress_Exception("Soundcloud API returned http status " . $response["http_code"] . " for url: $url", $response["http_code"]);
		}

		if (!($data = json_decode($response["body"]))) {
			throw new Stuffpress_Exception("Soundcloud did not return any result for url: $url", 0);
		}

		return $data;
	}

	private function addItems($items) {
		$result = array();
		
		foreach ($items as $item) {
			$timestamp	= time();
			
			$data       	= array();
			$data["track_id"]		= @$item->id;				
			$data["title"]			= @$item->title;
			$data["artwork_url"]	= @$item->artwork_url;
			$data["permalink_url"]	= @$item->permalink_url;
			$data["stream_url"]		= @$item->stream_url;
			$data["uri"]			= @$item->uri;			
			
			$id = $this->addItem($data, $timestamp, SourceItem::AUDIO_TYPE, array(), false, false, $data['title']);
	
			if ($id) $result[] = $id;
		}
	
		return $result;
	}
	
	
	private function lookupUserid($username) {
		$result = $this->curl("http://api.soundcloud.com/resolve.json?url=http://soundcloud.com/" . $username . "&client_id=YOUR_CLIENT_ID");
		preg_match('/(?<id>[0-9]+).json/', $result["body"], $matches);
		$userid = $matches["id"];
		return $userid;
	}
	
	private function curl($url, $header = array()) {
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_HEADER, false);
		curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_USERAGENT,'Storytlr/1.0');

		$result = array();
		$result["body"]  = curl_exec($curl);
		$result["http_code"] = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		curl_close ($curl);

		return $result;
	}
}
