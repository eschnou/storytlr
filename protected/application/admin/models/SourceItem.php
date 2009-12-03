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

abstract class SourceItem
{
	const IMAGE_TYPE 	= 'image';
	
	const AUDIO_TYPE 	= 'audio';
	
	const VIDEO_TYPE 	= 'video';
	
	const STATUS_TYPE 	= 'status';
	
	const BLOG_TYPE 	= 'blog';
	
	const LINK_TYPE 	= 'link';
	
	const OTHER_TYPE 	= 'other';
	
	const STORY_TYPE 	= 'story';
	
	protected $_data;
	
	protected $_attributes;
	
	protected $_prefix;
	
	protected $_preamble;
	
	protected $_tags;
	
	public function __construct($data, $attributes) {
		$this->_data = $data;
		$this->_attributes = $attributes;
	}
	
	public function toArray() {
		return array_merge($this->_data, $this->_attributes);
	}
	
	public function getType() {
		return SourceItem::OTHER_TYPE;
	}
	
	public function getID() {
		return $this->_data['id'];
	}
	
	public function getUserid() {
		return $this->_attributes['user_id'];
	}
	
	public function getTimestamp() {
		return $this->_attributes['timestamp'];
	}
	
	public function getPrefix() {
		return $this->_prefix;
	}
	
	public function getSlug() {
		return $this->_attributes['slug'];
	}
	
	public function getSource() {
		return $this->_attributes['source_id'];
	}
	
	public function getLatitude() {
		return $this->_attributes['latitude'];
	}
	
	public function getLongitude() {
		return $this->_attributes['longitude'];
	}
	
	public function getElevation() {
		return $this->_attributes['elevation'];
	}
	
	public function hasLocation() {
		return $this->_attributes['has_location'];
	}
	
	public function getLink() {
		return false;
	}
	
	public function getTitle() {
		return "untitled";
	}
	
	public function getPreamble() {
		return $this->_preamble;
	}
	
	public function getRssBody() {
		$data = array('item' => $this);
		return $this->render('rss', $data);
	}
	
	public function isHidden() {
		return $this->_attributes['is_hidden'];
	}
	
	public function getAttributes() {
		return $this->_attributes;
	}
	
	public function getCommentCount() {
		return $this->_attributes['comment_count'];
	}

	public function getTagCount() {
		return $this->_attributes['tag_count'];
	}
	
	public function getTags() {
		if ($this->getTagCount() == 0) {
			return false;
		}
		
		if (!$this->_tags) {
			$tags = new Tags();
			$this->_tags = $tags->getTags($this->getSource(), $this->getID());
		}
		
		return $this->_tags;
	}
	
	public function getBackup() {
		return $this->_data;
	}
	
	public function render($template, $data=array()) {
		$root = Zend_Registry::get('root');
		
		// Prepare the view
		$view = new Zend_View();
		$view->setEncoding('UTF-8');
		$view->addHelperPath("$root/library/Stuffpress/View/Helper", 'Stuffpress_View_Helper');
		$view->setBasePath("$root/application/plugins/{$this->_prefix}/views");
		
		// Add the data
		foreach($data as $k => $v) {
			$view->{$k} = $v;
		}
		
		return $view->render("$template.phtml");
	}
	
	protected function getBaseURL() {
		$config = Zend_Registry::get("configuration");
		$host 	= $config->web->host;
		$base   = $config->web->base;
		$base = $host.$base;
		return $base; 
	}
}