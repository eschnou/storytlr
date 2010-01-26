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

class Pages_VideosController extends Pages_BaseController 
{	
	protected $_prefix='videos';
	
	public function indexAction() 	{
		// To do before anything else
		$this->initPage();
		
		// Action parameters
		$page 			= $this->getRequest()->getParam("page");
		$tab 			= $this->getRequest()->getParam("tab");		
		
		// A bit of filtering
		$page 	= ($page>=0) ? $page : 0;
		$count	= 25;
				
		// Get the list of sources to filter on
		if ($this->_page_properties && $this->_page_properties->getProperty('sources_filter')>0) {
			$sources	= unserialize($this->_page_properties->getProperty('sources'));
		} else {
			$sources 	= false;
		}

		// Get all the items; if we are an admin, we also get the hidden one
		$data   = new Data();
		$items  = $data->getLastItems($count, $page * $count, $this->_admin, $sources, array (SourceItem::VIDEO_TYPE));
		$this->view->items = $items;
		$this->view->models		= $this->getModels();
		
		// Set link to RSS of page
		$rss_link = $this->getRssLink(false, array('video'));
		$this->view->headLink()->appendAlternate($rss_link, "application/rss+xml", "RSS Stream");
		
		// Add paging
		$this->view->count		 = $count; 
		$this->view->page		 = $page;
		$this->view->hasprevious = ($page>0) ? true : false;
		$this->view->hasnext 	 = (count($items) >= $count) ? true : false;
		$this->view->nextlink 	  = "home?tab=$tab&page=" . ($page + 1);
		$this->view->previouslink = "home?tab=$tab&page=" . ($page - 1);
				
		// Prepare the common elements
		$this->common();

		// Special
		if (!$this->_page) {
			$this->view->page_title = "All videos";
		}
		
		// Add specific styles and javascripts
		$this->view->headScript()->appendFile('js/shadowbox/adapter/shadowbox-prototype.js');
		$this->view->headScript()->appendFile('js/shadowbox/shadowbox.js');
		$this->view->headScript()->appendFile('js/shadowbox/init.js');
	}
}
