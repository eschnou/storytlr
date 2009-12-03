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
class PicasaModel extends SourceModel {

	protected $_name 	= 'picasa_data';

	protected $_prefix = 'picasa';
	
	protected $_search  = 'title, description';

	protected $_update_tweet = "Uploaded %d photos to Picasa %s"; 
	
	public function getServiceName() {
		return "Picasa";
	}

	public function getServiceURL() {
		if ($username = $this->getProperty('username')) {
			return "http://picasaweb.google.com/$username";
		}
		else {
			return "http://picasaweb.google.com/";;
		}
	}

	public function getServiceDescription() {
		return "Picasa is a picture sharing service.";
	}

	public function isStoryElement() {
		return true;
	}
	
	public function setTitle($id, $title) {
		$this->updateItem($id, array('title' => $title));
	}

	public function importData() {
		$items = $this->updateData(true);
		$this->setImported(true);
		return $items;
	}

	public function updateData($full = false) {
		$username 	= $this->getProperty('username');
		$pages		= $full ? 5 : 1;
		$count		= 1000;
		$result		= array();
		$lastupdate = strtotime($this->getLastUpdate());
	
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_HEADER, false);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_USERAGENT,'Storytlr/1.0');		
		curl_setopt($curl, CURLOPT_TIMEVALUE, $this->getLastUpdate());
		
		for($page=0; $page<$pages; $page++) {									
			$url		= "http://picasaweb.google.com/data/feed/api/user/$username/?kind=photo&max-results=$count&start-index=" . ($page * $count + 1);			
			curl_setopt($curl, CURLOPT_URL, $url);
			$response = curl_exec($curl);
			$http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);

			if ($http_code == 304) {
				break;
			}
			
			if ($http_code != 200) {
				throw new Stuffpress_Exception("Picasa API returned http status $http_code for url: $url", $http_code);
			}
			
			if (!($items = simplexml_load_string($response))) {
				throw new Stuffpress_Exception("Picasa did not return any result", 0);
			}
	
			$new_items 	= $this->addItems($items);
			$result 	= array_merge($result, $new_items);
			
			if (count($new_items) < $count) break;
		}

		curl_close ($curl);
		unset($curl);
		
		// Mark as updated (could have been with errors)
		$this->markUpdated();
		
		return $result;
	}

	private function addItems($items) {
		$result = array();
		foreach ($items as $k => $v) {
			if ($k=="entry") {
				$ns_gphoto	= $v->children('http://schemas.google.com/photos/2007');
				$ns_media	= $v->children('http://search.yahoo.com/mrss/');
				
				$tags					= explode(',',$ns_media->group->keywords);
				
				
				$data					= array();
				$data['published'] 		= $v->published;
				$data['link']	 		= $v->link[1]->attributes()->href;
				$data['photo_id']		= $ns_gphoto->id;
				$taken_at				= $ns_gphoto->timestamp;
				$data['taken_at']		= $taken_at;
				$data['title'] 			= $ns_media[0]->description;
				$data['description']	= "";
				$data['url']			= $ns_media[0]->content->attributes()->url;
					
				$timestamp = substr($taken_at, 0, strlen($taken_at) - 3);

				$id = $this->addItem($data, $timestamp, SourceItem::IMAGE_TYPE, $tags, false, false, $data['title']);
				if ($id) $result[] = $id;	
			}
		}

		return $result;
	}
	
	private function getLastUploadDate() {
		$sql  = "SELECT UNIX_TIMESTAMP(dateupload) FROM `$this->_name` WHERE source_id = :source_id ORDER BY dateupload DESC";
		$data = array(":source_id" => $this->_source['id']);
		$timestamp = $this->_db->fetchOne($sql, $data);
    	$date = date('Y-m-d\TH:i:s', $timestamp);
	    $matches = array();
	    if (preg_match('/^([\-+])(\d{2})(\d{2})$/', date('O', $timestamp), $matches)) {
    	    $date .= $matches[1].$matches[2].':'.$matches[3];
    	} else {
        	$date .= 'Z';
    	}
    	echo "Last date is $date\r\n";
    	return $date;
	}
}
