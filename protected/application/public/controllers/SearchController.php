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
class SearchController extends BaseController 
{	    
	protected $_section = 'lifestream';
	
	public function preDispatch() {
		if (!$this->_user) {
			throw new Stuffpress_NotFoundException("No user specified");
		}
			
		// If the page is private, go back with an error
		if (!$this->_admin && $this->_properties->getProperty('is_private')) {
			throw new Stuffpress_AccessDeniedException("This page has been set as private.");
		}	
	}
		
	public function keywordAction() {
		// Action parameters
		$search = $this->getRequest()->getParam("search");
		
		// A bit of filtering
		$search = substr($search, 0, 50);
				
		// Get all the items; if we are an admin, we also get the hidden one
		$data   = new Data();
		$sourcesTable = new Sources();
		$sources	  = $sourcesTable->getSources();
		$items		  = array();
		foreach($sources as $source) {
			$s		= SourceModel::newInstance($source['service'], $source);
			$index  = $s->getSearchIndex();
			$prefix = $s->getServicePrefix();
			$id	    = $s->getID();
			if ($index != '') {
				$r		= $data->search($id, $prefix, $index, $search, $this->_admin);
				if ($r) {
					$items	= array_merge($items, $r); 
				}
			}
		}
		
		// Sort the result of the search
		$sorter 	= new Stuffpress_SortItems();
		$sorter->sort($items, 1);
		
		// Prepare the common elements
		$this->common();
		
		// Add specifics entries
		$this->view->search = $search;
		$this->view->items  = $items;
		$this->view->models	= $this->getModels();
		
		// Set page title
		$title 		= $this->_properties->getProperty('title');
		$subtitle 	= $this->_properties->getProperty('subtitle');
		$separator 	= $title ? "|" : "";
		$this->view->headTitle("$title $separator Search results for $search");
				
		// Add specific styles and javascripts
		$this->view->headScript()->appendFile('js/controllers/timeline.js');
		$this->view->headLink()->appendStylesheet('style/lightbox.css');
		
		// Render the index
		$this->render('index');
	}
}
