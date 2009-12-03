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

abstract class Pages_BaseController extends BaseController
{
	protected $_prefix;
	
	protected $_page = false;
	
	protected $_page_properties = false;

	public function preDispatch() {
		if (!$this->_user) {
			throw new Stuffpress_NotFoundException("No user specified");
		}
			
		// If the page is private, go back with an error
		if (!$this->_admin && $this->_properties->getProperty('is_private')) {
			throw new Stuffpress_AccessDeniedException("This page has been set as private.");
		}
	}
	
	protected function initPage() {
		// If no page id, we have a problem
		$page_id = $this->getRequest()->getParam("pid");
		
		// If the page does not exist, we have a problem
		$pages = new Pages();
		if ($page = $pages->getPage($page_id)) {
			$this->_page = $page;
		}
	
		// get the page properties
		if ($page) {
			$this->_page_properties	= new PagesProperties(array(PagesProperties::KEY => $page['id']));
			$this->view->page_id = $this->_page['id'];
			$this->view->page_title = $this->_page['title'];
		} else {
			$this->view->page_id = false;
		}
		
		// Get the tab for further use
		if ($tab = (int) $this->getRequest()->getParam("tab")) {
			$this->view->tab = $tab;
		}
		
		// Prepare the view
		$this->_helper->layout->setLayoutPath($this->_root . '/application/public/views/layouts/')
							  ->setlayout('default');

		$this->view->page_class = $this->_prefix;
	}
	
	public function common() {
		// Get the generic stuff from parent
		parent::common();
		
		// Add my own things
		$this->view->headTitle($this->getPageTitle());
	}
	
	public function getPageTitle() {

		$title 		= $this->_properties->getProperty('title');
		$subtitle 	= $this->_properties->getProperty('subtitle');
		$page		= $this->_page['title'];
		
		$elements	= array();
		if ($title)	$elements[] = $title;
		if ($subtitle) $elements[] = $subtitle;
		if ($page) $elements[] = $page;
		
		return implode(" | ", $elements);
	}
	
	public function getRssLink($sources, $types) {		
		$host 		= trim(Zend_Registry::get("host"), '/');
		$arguments  = array();
		if ($types) 	{
			$arguments['types'] 	= implode(",", $types);
		}
		if ($sources) {
			$arguments['sources'] 	= implode(",", $sources);
		}
		
		return Stuffpress_Services_Uri::create($host, "/rss/feed.xml", $arguments); 
	}
}