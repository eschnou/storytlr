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
class MappageController extends BaseController
{	
    protected $_application;
    	
    public function init()
    {
    	parent::init();

        // If request is Ajax, we disable the layout
		if ($this->_request->isXmlHttpRequest()) {
			$this->_helper->layout->disableLayout();	
		}
		else {
			$this->_helper->layout->setlayout('page_mapview');
		}
    }
    
    public function indexAction() {
    	$this->_forward("view");
    }
    
   	public function viewAction() {	   		
		// Action parameters
		$page 			= $this->getRequest()->getParam("page");
		
		// A bit of filtering
		$page 	= ($page>=0) ? $page : 0;
		$count	= 50;

		// Get all the items; if we are an admin, we also get the hidden one
		$data   = new Data();
		$items  = $data->getLastItems($count, $page * $count, $this->_admin, false, false, true);
		$this->view->items = $items;
		$this->view->models		= $this->getModels();
			
		// Prepare the common elements
		$this->common();	
			
		// Add the data required by the view
		$this->view->username 		= $user->username;
		$this->view->owner			= $owner;
		$this->view->user_id 	 	= $user->id;
		$this->view->gmap_key 		= $this->_config->gmap->key;	
	}
}
