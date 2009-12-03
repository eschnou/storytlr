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

class Pages_DashboardController extends Pages_BaseController {
    
	protected $_section = 'dashboard';
	
	public function indexAction() {
		// To do before anything else
		$this->initPage();	
				
		// Get the list of sources to filter on
		$types = unserialize($this->_page_properties->getProperty('types'));

		// Get all the items; if we are an admin, we also get the hidden one
		$data   = new Data();
		
		if (!$types || in_array('status', $types)) {
			$this->view->items_status = $data->getLastItems(5, 0, false, false, array('status'));
		}
		
		if (!$types || in_array('link', $types)) {
			$this->view->items_link = $data->getLastItems(5, 0, false, false, array('link'));
		}
		
		if (!$types || in_array('blog', $types)) {
			$this->view->items_blog = $data->getLastItems(1, 0, false, false, array('blog'));
		}
		
		if (!$types || in_array('image', $types)) {
			$this->view->items_image = $data->getLastItems(6, 0, false, false, array('image'));
		}
		
		if (!$types || in_array('audio', $types)) {
			$this->view->items_audio = $data->getLastItems(10, 0, false, false, array('audio'));
		}
		
		if (!$types || in_array('video', $types)) {
			$this->view->items_video = $data->getLastItems(6, 0, false, false, array('video'));
		}
		
		if (!$types || in_array('story', $types)) {
			$storiesTable 	= new Stories();
			$this->view->items_story = $storiesTable->getStories(1, 0, false);
		}
		

		// Add the models
		$this->view->models		= $this->getModels();
		
		// Prepare the common elements
		$this->common();
		
		// Set link to RSS of page
		$host 		= trim(Zend_Registry::get("host"), '/');		
		$rss_link 	= "http://$host/rss"; 
		$this->view->headLink()->appendAlternate($rss_link, "application/rss+xml", "RSS Stream");
	}
}
