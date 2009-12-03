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
class RssModel extends SourceModel {

	protected $_name 	= 'rss_data';

	protected $_prefix = 'rss';
	
	protected $_search  = 'title';
	
	protected $_update_tweet = "Syndicated %d blog entries on my Lifestream %s";

	public function getServiceName() {
		return "RSS";
	}
	
	public function isStoryElement() {
		return true;
	}

	public function getServiceURL() {
		return $this->getProperty('url');
	}

	public function getServiceDescription() {
		return "RSS is a standard blog syndication technology. This source enable you to get data from any blog.";
	}

	public function getAccountName() {
		if ($name = $this->getProperty('title')) {
			return $name;
		}
		else {
			return false;
		}
	}
	
	public function getTitle() {
		if ($name = $this->getProperty('title')) {
			return $name;
		}
		else {
			return $this->getServiceName();
		}
	}

	public function importData() {
		$url	= $this->getProperty('url');

		try {
			$feeds = Zend_Feed::findFeeds($url);
		}
		catch (Zend_Feed_Exception $e) {
			return 0;
		}
		
		if (!$feeds) {
			try {
				$items = Zend_Feed::import($url);
			}
			catch (Zend_Feed_Exception $e) {
				return 0;
			}
			$feed_url = $url;
		} else {
			$items = $feeds[0];
			$feed_url = Zend_Feed::getHttpClient()->getUri(true);
		}
		
		$title = $items->title();
		$this->setProperty('feed_title', $title);
		$this->setProperty('feed_url', $feed_url);
		$items = $this->updateData();
		$this->setImported(true);
		return $items;

	}

	public function updateData() {
		$feed_url	= $this->getProperty('feed_url');

		// Fetch the latest headlines from the feed
		try {
			$items = $feed = Zend_Feed::import($feed_url);
			return $this->processItems($items);
		} catch (Zend_Feed_Exception $e) {
			return;
		}
	
		// Mark as updated (could have been with errors)
		$this->markUpdated();
	}

	private function processItems($items) {
		$result = array();
		$tidy = new tidy();		
		$config = array(
           'indent'         => true,
           'output-xhtml'   => true,
           'wrap'           => 200);

        foreach ($items as $item) {
			$data		= array();
			
			// Fetch the title
			$data['title'] = (string) $item->title();
			
			// Fetch the link
			$link 			= $item->link;
			if (is_array($link)) {
				$link = $link[0];
			}
			if (isset($link['href'])) {
				$link = $link['href'];
			}
			$data['link']		= $link;
			
			// Date
			$pubDate			= strtotime((string) $item->pubDate);		// For RSS entries
			$published			= strtotime((string) $item->published); 	// For Atom entries	
			$updated			= strtotime((string) $item->updated); 	// For Atom entries
						
			$data['published']	= max($pubDate, $published, $updated);
			
			//Content
			$content		 	= (string) $item->content;
			$desc				= (string) $item->description;	
			if (strlen($desc) > strlen($content)) $content = $desc;
	
			// Tidy !
			$tidy->parseString($content, $config, 'utf8');
			$tidy->cleanRepair();
			$data['content'] = $tidy;	
			
			// Save the item in the database
			$id = $this->addItem($data, $data['published'], SourceItem::BLOG_TYPE, false, false, false, $data['title']);
			if ($id) $result[] = $id;	
			if (count($result)> 100) break;
		}
		unset($tidy);
		return $result;
	}
	
	public function getConfigForm($populate=false) {
		$form = new Stuffpress_Form();
		
		// Add the blog url element
		$element = $form->createElement('text', 'url', array('label' => 'Feed URL', 'decorators' => $form->elementDecorators));
		$element->setRequired(true);
		$form->addElement($element);  
		
		// Add the blog title element
		$element = $form->createElement('text', 'title', array('label' => 'Title', 'decorators' => $form->elementDecorators));
		$element->setRequired(false);
		$form->addElement($element);  
		
		// Options
		$options = array();
		if ($this->getPropertyDefault('hide_content')) $options[] = 'hide_content';
		$e = new Zend_Form_Element_MultiCheckbox('options', array(
			'decorators' => $form->elementDecorators,
			'multiOptions' => array(
			'hide_content' 	=> 'Hide blog post (only title will be shown)'
			)
		)); 
		$e->setLabel('Options');
		$e->setValue($options);
		$form->addElement($e);

		// Populate
		if($populate) {
			$options = array();
			$values  = $this->getProperties();
			if ($this->getProperty('hide_content')) $options[]='hide_content';
			$values['options'] = $options;
			$form->populate($values);
		}

		return $form;
	}
	
	public function processConfigForm($form) {
		$values = $form->getValues();
		$options = $values['options'];
		$update	= false;
		
		if($values['url'] != $this->getProperty('url')) {
			$this->_properties->setProperty('url',   $values['url']);
			$update = true;
		}

		$hide_content = @in_array('hide_content',$options) ? 1 : 0;
		$this->_properties->setProperty('hide_content', $hide_content);
		
		$this->_properties->setProperty('title', $values['title']);
		return $update;
	}
}
