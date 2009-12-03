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
class StumbleModel extends SourceModel {

	protected $_name 	= 'stumble_data';

	protected $_prefix = 'stumble';
	
	protected $_search  = 'title';
	
	protected $_update_tweet = "Stumbled upon %d stories %s";

	public function getServiceName() {
		return "StumbleUpon";
	}
	
	public function isStoryElement() {
		return true;
	}
	
	public function getServiceURL() {
		if ($username = $this->getProperty('username')) {
			return "http://$username.stumbleupon.com";
		}
		else {
			return "http://www.stumbleupon.com/";;
		}
	}
	

	public function getServiceDescription() {
		return "StumbleUpon is a social bookmarking service.";
	}
	
	
	public function getPublisher() {
		return "stumbleupon";
	}

	public function importData() {
		$username	= $this->getProperty('username');
		$url		= "http://rss.stumbleupon.com/user/$username/favorites";

		$items = $this->updateData();
		$this->setImported(true);
		return $items;
	}

	public function updateData() {
		$username	= $this->getProperty('username');
		$url		= "http://rss.stumbleupon.com/user/$username/favorites";

		// Fetch the latest headlines from the feed
		try {
			$items = Zend_Feed::import($url);
			return $this->processItems($items);
		} catch (Zend_Feed_Exception $e) {
			throw new Stuffpress_Exception("Stumbleupon - could not fetch feed at url $url", 0);
		}
		
		// Mark as updated (could have been with errors)
		$this->markUpdated();
	}

	private function processItems($items) {
		$result = array();
		foreach ($items as $item) {
			$data			= array();
			$data['title']	= $item->title;
			$data['link']	= $item->link;
			$data['published']	= strtotime($item->pubDate); 	// For Atom entries	
			$data['type'] 	= 'favorite';
			
			// Save the item in the database
			$id = $this->addItem($data, $data['published'], SourceItem::LINK_TYPE, false, false, false, $data['title']);
			if ($id) $result[] = $id;
		}

		return $result;
	}
}
