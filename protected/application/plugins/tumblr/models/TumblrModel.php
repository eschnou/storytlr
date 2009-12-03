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
class TumblrModel extends SourceModel {

	protected $_name 	= 'tumblr_data';

	protected $_prefix = 'tumblr';

	protected $_search  = 'quote_text, photo_caption, link_text, link_description, regular_title, regular_body';

	protected $_update_tweet = "Posted %d entries with Tumblr %s"; 
	
	public function getServiceName() {
		return "Tumblr";
	}

	public function getServiceURL() {
		if ($username = $this->getProperty('username')) {
			return "http://$username.tumblr.com";
		}
		else {
			return "http://www.tumblr.com/";;
		}
	}

	public function getServiceDescription() {
		return "Tumblr is a tumblelog";
	}
	
	public function isStoryElement() {
		return true;
	}

	public function importData() {
		$items = $this->updateData(true);
		$this->setImported(true);
		return $items;
	}

	public function updateData($full=false) {
		// Fetch the data from twitter
		$username   = $this->getProperty('username');
		$pages		= $full ? 50 : 1;
		$count		= 50;
		$result 	= array();
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_HEADER, false);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($curl, CURLOPT_USERAGENT,'Storytlr/1.0');
					
		for ($page = 0; $page<$pages; $page++) {	
			$url 		= "http://$username.tumblr.com/api/read/json?callback=wrap&num=$count&start=" . $page * $count;
			curl_setopt($curl, CURLOPT_URL, $url);	
			$response = curl_exec($curl);
			$http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
	
			if ($http_code != 200) {
				throw new Stuffpress_Exception("Tumblr API returned http status $http_code for url: $url", $http_code);
			}
	
			// Remove the wrapper
			$response = substr($response, 5, strlen($response) - 8);
			
			if (!($data = json_decode($response))) {
				throw new Stuffpress_Exception("Tumblr did not return any result for url: $url", 0);
			}

			$posts = $data->{'posts-total'};
			
			if ($posts == 0) break;
				
			$items = $this->processItems($data->posts);
			
			$result = array_merge($result, $items);

			if ($posts<$count) {
				break;
			}
		}
		curl_close ($curl);
		unset($curl);
		
		// Mark as updated (could have been with errors)
		$this->markUpdated();
		
		return $result;
	}

	private function processItems($items) {
		$result = array();
		foreach ($items as $item) {
			$data		= array();
			
			$data['tumblr_id'] 			= @$item->id;
			$data['url']				= @$item->url;
			$data['type']				= @$item->type;
			$data['date']				= @$item->{'unix-timestamp'};
			$data['quote_text']			= @$item->{'quote-text'};
			$data['quote_source']		= @$item->{'quote-source'};
			$data['photo_caption']		= @$item->{'photo-caption'};
			$data['photo_url']			= @$item->{'photo-url-500'};
			$data['link_text']			= @$item->{'link-text'};
			$data['link_url']			= @$item->{'link-url'};
			$data['link_description'] 	= @$item->{'link-description'};
			$data['conversation_title']	= @$item->{'conversation-title'};
			$data['conversation_text']	= @$item->{'conversation-text'};
			$data['regular_title']		= @$item->{'regular-title'};
			$data['regular_body']		= @$item->{'regular-body'};
			$data['video_caption']		= @$item->{'video-caption'};
			$data['video_source']		= @$item->{'video-source'};
			$data['video_player']		= @$item->{'video-player'};
			$data['audio_caption']		= @$item->{'audio-caption'};
			$data['audio_player']		= @$item->{'audio-player'};
						
			$id = $this->addItem($data, $data['date'], $this->mapType($data['type']), false, false, false, $data['regular_title']);
			if ($id) $result[] = $id;	
		}
		return $result;
	}
	
	private function mapType($type) {
		switch ($type) {
			case 'regular':
				return SourceItem::BLOG_TYPE;
				break;
			case 'quote':
				return SourceItem::BLOG_TYPE;
				break;
			case 'link':
				return SourceItem::LINK_TYPE;
				break;
			case 'conversation':
				return SourceItem::BLOG_TYPE;
				break;
			case 'photo':
				return SourceItem::IMAGE_TYPE;
				break;				
			case 'video':
				return  SourceItem::VIDEO_TYPE;
				break;	
			case 'audio':
				return  SourceItem::AUDIO_TYPE;
				break;												
		}	
	}
}
