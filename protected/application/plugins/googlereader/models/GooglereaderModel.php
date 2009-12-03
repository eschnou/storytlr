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
class GooglereaderModel extends SourceModel {

	protected $_name 	= 'googlereader_data';

	protected $_prefix = 'googlereader';
	
	protected $_search  = 'title, note';
	
	protected $_update_tweet = "Shared %d stories with GoogleReader %s"; 

	public function getServiceName() {
		return "Google Reader";
	}
	
	public function isStoryElement() {
		return true;
	}
	
	public function getServiceURL() {
		if ($url = $this->getProperty('url')) {
			return $url;
		}
		else {
			return "http://reader.google.com";
		}
	}

	public function getServiceDescription() {
		return "Google Reader is an RSS client that enables you to share your favorite items.";
	}

	public function getAccountName() {
		return false;
	}

	public function importData() {
		$url	= $this->getProperty('url');

		$feeds = Zend_Feed::findFeeds($url);
		
		if (!$feeds) {
			$items = Zend_Feed::import($url);
			$feed_url = $url;
		} else {
			$items = $feeds[0];
			$feed_url = Zend_Feed::getHttpClient()->getUri(true);
		}
		
		$this->setProperty('feed_url', $feed_url);
		$items = $this->processItems($items, 'published');
		$this->setImported(true);
		return $items;
	}

	public function updateData() {
		$feed_url	= $this->getProperty('feed_url');		
		Zend_Feed::registerNamespace('gr','http://www.google.com/schemas/reader/atom/');				
		if (!($feed = new Zend_Feed_Atom($feed_url))) return 0;				
		$result = $this->processItems($feed,'now');		
		
		// Clear up to free memory
		unset($feed);		
		
		// Mark as updated (could have been with errors)
		$this->markUpdated();
		
		return $result;
	}

	private function processItems($items, $time) {
		$result = array();
		$tidy = new tidy();		
		foreach ($items as $item) {
			$data		= array();
			$data['title']		= $item->title();
			if ($item->link() && count($item->link()) > 0) {
				$links = $item->link();
				$link  = $links[0];
				if (is_object($link)) {
					$data['link'] = (string) $link->getAttribute('href');
				} else {
					$data['link'] = "";
				}
			} else{
				$link = $item->link;
				$data['link'] = (string) $link['href'];			
			}	
			
			$content 			= $item->content();
			$data['published']	= $item->published();
			$data['note']		= $item->{'gr:annotation'}->content;
			
			$timestamp = ($time == 'now') ? time() : strtotime((string) $data['published']);
			
			// Tidy up the content
			$config = array(
	           'indent'         => true,
	           'output-xhtml'   => true,
	           'wrap'           => 200);
	
			$tidy->parseString((string) $content, $config, 'utf8');
			$tidy->cleanRepair();
			$data['content'] = $tidy;

			$id = $this->addItem($data, $timestamp, SourceItem::LINK_TYPE, false, false, false, $data['title']);
			if ($id) $result[] = $id;
			unset($data);
		}
		unset($tidy);
		return $result;
	}
	
	public function getConfigForm($populate=false) {
		$form = new Stuffpress_Form();
		
		// Add the blog url element
		$element = $form->createElement('text', 'url', array('label' => 'URL of your shared items', 'decorators' => $form->elementDecorators));
		$element->setRequired(true);
		$form->addElement($element);

		if($populate) {
			$form->populate($this->getProperties());
		}

		return $form;
	}
	
	public function processConfigForm($form) {
		$values = $form->getValues();
		$this->_properties->setProperty('url',   $values['url']);
		return true;
	}
}
