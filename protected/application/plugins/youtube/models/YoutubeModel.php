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
class YoutubeModel extends SourceModel {

	protected $_name 	= 'youtube_data';

	protected $_prefix = 'youtube';

	protected $_search  = 'title';

	protected $_update_tweet = "%d videos added from Youtube %s";

	public function getServiceName() {
		return "Youtube";
	}

	public function getServiceURL() {
		if ($username = $this->getProperty('username')) {
			return "http://www.youtube.com/".$username;
		}
		else {
			return "http://www.youtube.com/";;
		}
	}

	public function getServiceDescription() {
		return "Youtube is a video sharing site.";
	}

	public function isStoryElement() {
		return true;
	}

	public function importData() {
		$videos = $this->updateVideos(true);
		try {
			$favs	= $this->updateFavorites(true, true);
		} catch(Exception $e) {
			$favs	= array();
		};
		$this->setImported(true);
		return (array_merge($videos,$favs));
	}

	public function updateData() {
		$videos = $this->updateVideos();
		try {
			$favs	= $this->updateFavorites();
		} catch(Exception $e) {
			$favs	= array();
		};
		sleep(1);

		// Mark as updated (could have been with errors)
		$this->markUpdated();

		return (array_merge($videos,$favs));
	}

	public function updateVideos($import=false) {
		$username 	= $this->getProperty('username');
		$pages		= $import ? 50 : 1;
		$count		= 50;
		$result		= array();

		for($page=0; $page < $pages; $page++) {
			$uri  = "http://gdata.youtube.com/feeds/api/users/$username/uploads?alt=json&max-results=$count&start-index=" . ($page * $count + 1);
			$data = $this->fetchItems($uri);
			if (!$data || count($data) == 0) break;
			$items 	= $this->addItems($data, 'video');
			$result = array_merge($result, $items);
			if (count($items) < $count) break;
		}

		return $result;
	}

	public function updateFavorites($import=false) {
		$username 	= $this->getProperty('username');
		$pages		= $import ? 25 : 1;
		$count		= 50;
		$result		= array();

		for($page=0; $page < $pages; $page++) {
			$uri		= "http://gdata.youtube.com/feeds/api/users/$username/favorites?alt=json&max-results=$count&start-index=" . ($page * $count + 1);
			$data = $this->fetchItems($uri);
			if (!$data || count($data) == 0) break;
			$items   = $this->addItems($data, 'favorite');
			$result = array_merge($result, $items);
			if (count($items) < $count) break;
		}

		return $result;
	}

	public function processGnipItem($activity) {
		//
	}

	private function addItems($items, $type) {
		$result = array();
		foreach ($items->feed->entry as $v) {
			$link 					= (string) @$v->link[0]->href;
			$keywords				= (string) @$v->{'media$group'}->{'media$keywords'}->{'$t'};
			$published				= (string) @$v->{'published'}->{'$t'};
			//$recorded				= (string) @$v->{'yt$recorded'}->{'$t'};
			$video_id 				= (string) @$v->{'media$group'}->{'yt$videoid'}->{'$t'};
			$title					= (string) @$v->{'title'}->{'$t'};
			$description			= (string) @$v->{'media$group'}->{'media$description'}->{'$t'};
			$author					= (string) @$v->author[0]->name->{'$t'};
			$geo					= (string) @$v->{'georss$where'}->{'gml$Point'}->{'gml$pos'}->{'$t'};

			$data					= array();
			$data['video_id']		= $video_id;
			$data['uri']			= "http://gdata.youtube.com/feeds/api/videos/" . $video_id;
			$data['type']			= $type;
			$data['title'] 			= $title;
			$data['content']		= ($type == 'video') ? $description : '';
			$data['published']		= $published; //($type == 'video' && $recorded) ? $recorded : $published;
			$data['link']	 		= $link;
			$data['author']			= $author;

			$timestamp				= strtotime((string) $data['published']);
			$tags					= explode(',', (string) $keywords);
			$location = array();
			if ($type == 'video' && $geo) {
				$geo = explode(' ', $geo);
				$location['latitude'] = $geo[0];
				$location['longitude'] = $geo[1];
			}

			$id = $this->addItem($data, $timestamp, SourceItem::VIDEO_TYPE, $tags, $location, false, $data['title']);

			if ($id) $result[] = $id;
		}

		return $result;
	}

	private function fetchItems($url) {
		$config = Zend_Registry::get("configuration");
		
		$header = array();
		$header[] = "GData-Version: 2";

		if (isset($config->youtube)) {
			$header[] = "X-GData-Client: {$config->youtube->client_id}";
			$header[] = "X-GData-Key: key={$config->youtube->developer_key}";
		}
		
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_HEADER, false);
		curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_USERAGENT,'Storytlr/1.0');

		$response = curl_exec($curl);
		$http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		curl_close ($curl);

		if ($http_code != 200) {
			throw new Stuffpress_Exception("Youtube API returned http status $http_code for url: $url", $http_code);
		}

		if (!($data = json_decode($response))) {
			throw new Stuffpress_Exception("Youtube did not return any result for url: $url", 0);
		}

		return $data;
	}
}
