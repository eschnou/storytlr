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

class Pages_StoriesController extends Pages_BaseController 
{	
	protected $_prefix='stories';
	
	public function indexAction() 	{
		// To do before anything else
		$this->initPage();

		// Get, check and setup the parameters
		$page 			= $this->getRequest()->getParam("page");
		$tab 			= $this->getRequest()->getParam("tab");		
		
		// A bit of filtering
		$page 	= ($page>=0) ? $page : 0;
		$count	= 25;
				
		// Get the list of stories
		$storiesTable 	= new Stories();
		$stories 		= $storiesTable->getStories($count, $page * $count, $this->_admin);
		
		// Update stories with few gimicks and assign to view
		foreach($stories as &$story) {
			$story['permalink'] = Stuffpress_Permalink::story($story['id'], $story['title']);
			$story['is_geo'] =  $storiesTable->isGeo($story['id']);
		}
		$this->view->stories	 	= $stories; 
	
		// Navigation options
		$this->view->page		 	= $page;
		$this->view->hasprevious 	= ($page>0) ? true : false;
		$this->view->hasnext 	 	= (isset($items) && (count($items) >= $count)) ? true : false;
		$this->view->nextlink 	  = "/home?tab=$tab&page=" . ($page + 1);
		$this->view->previouslink = "/home?tab=$tab&page=" . ($page - 1);		
		
		// Prepare the common elements
		$this->common();
		
		// Add page specific elements
		$this->view->headScript()->appendFile('js/controllers/stories.js');		
	}
}