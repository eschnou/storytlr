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

class Pages_MentionsController extends Pages_BaseController {
    
	protected $_prefix = 'mentions';
	
	public function indexAction() {
		// To do before anything else
		$this->initPage();
		
		// Action parameters
		$page 			= $this->getRequest()->getParam("page");
		$tab 			= $this->getRequest()->getParam("tab");
		
		// A bit of filtering
		$page 	= ($page>=0) ? $page : 0;
		$count	= 50;
		
		// Get all the items; if we are an admin, we also get the hidden one
		$mentions = new Mentions();
		$items  = $mentions->getLastMentions($count, $page * $count, $this->_admin);
		$this->view->items = $items;
		
		// Prepare the common elements
		$this->common();
		
		// Add js controler
		$this->view->headScript()->appendFile('js/controllers/timeline.js');
		
		// Add description
		if ($description = $this->_page_properties->getProperty('description')) {
			$this->view->description = $description;
		}
		
		// Add paging
		$this->view->count		 = $count; 
		$this->view->page		 = $page;
		$this->view->hasprevious = ($page>0) ? true : false;
		$this->view->hasnext 	 = (count($items) >= $count) ? true : false;
		$this->view->nextlink 	  = "home?tab=$tab&page=" . ($page + 1);
		$this->view->previouslink = "home?tab=$tab&page=" . ($page - 1);					
	}
}
