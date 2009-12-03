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
class VimeoModel extends SourceModel {

	protected $_name 	= 'vimeo_data';

	protected $_prefix = 'vimeo';

	protected $_search  = 'title,caption';
	
	protected $_update_tweet = "%d videos added from Vimeo %s"; 

	public function getServiceName() {
		return "Vimeo";
	}

	public function getServiceURL() {
		if ($username = $this->getProperty('username')) {
			return "http://www.vimeo.com/".$username;
		}
		else {
			return "http://www.vimeo.com/";;
		}
	}

	public function getServiceDescription() {
		return "Vimeo is a video sharing site.";
	}

	public function isStoryElement() {
		return true;
	}

	public function importData() {
		$videos = $this->updateVideos(true);
		$favs	= $this->updateFavorites(true, true);
		$this->setImported(true);
		return (array_merge($videos,$favs));
	}

	public function updateData() {
		$videos = $this->updateVideos();
		$favs	= $this->updateFavorites();
		
		// Mark as updated (could have been with errors)
		$this->markUpdated();
		
		return (array_merge($videos,$favs));
	}

	public function updateVideos($full=false) {
		$username 	= $this->getProperty('username');
		$uri  = "http://vimeo.com/api/$username/clips.php";
		$data = $this->fetchItems($uri);
		if (!$data || count($data) == 0) return array();
		return $this->addItems($data, 'video');
	}

	public function updateFavorites($full=false, $import=false) {
		$username 	= $this->getProperty('username');
		$uri  = "http://vimeo.com/api/$username/likes.php";
		$data = $this->fetchItems($uri);
		if (!$data || count($data) == 0) return array();
		return $this->addItems($data, 'favorite', $import ? 'published' : 'now');
	}

	private function addItems($items, $type, $time='published', $ishidden=0) {
		$result = array();
		foreach($items as $item) {
			$data = array();
			$data['type'] 	  	= $type;
			$data['title'] 	  	= $item['title'];
			$data['caption']  	= ($type == 'video') ? $item['caption'] : '';
			$data['published']	= strtotime((string) $item['upload_date']) + 60 * 60 * 6;
			$data['clip_id']	= $item['clip_id'];
			$data['url']		= $item['url'];
			$data['user_name']  = $item['user_name'];
			$data['user_url']	= $item['user_url'];
			$data['tags']		= $item['tags'];
			$data['duration']	= $item['duration'];
			$data['width']		= $item['width'];
			$data['height']		= $item['height'];
			$data['thumbnail']	= $item['thumbnail_large'];
			
			$tags		= (strlen($item['tags']) > 0) ? explode(',',$item['tags']) : false;
			$timestamp	= ($time == 'published') ? $data['published'] : time();

			$id = $this->addItem($data, $timestamp, SourceItem::VIDEO_TYPE, $tags, false, $ishidden, $data['title']);
			if ($id) $result[] = $id;
		}
		
		return $result;
	}

	private function fetchItems($url) {
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_HEADER, false);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_USERAGENT,'Storytlr/1.0');

		$response = curl_exec($curl);
		$http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		curl_close ($curl);

		if ($http_code != 200) {
			throw new Stuffpress_Exception("Vimeo API returned http status $http_code for url: $url", $http_code);
		}

		$data = @unserialize($response);
		
		if (!is_array($data)) {
			throw new Stuffpress_Exception("Vimeo result could not be unserialized for url: $url", 0);
		}

		return $data;
	}
}
