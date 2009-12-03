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
class TimelineController extends BaseController
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

	public function archiveAction() {
		// Request parameters
		$month			= $this->getRequest()->getParam("month");
		$year 			= $this->getRequest()->getParam("year");
		$page 			= $this->getRequest()->getParam("page");
		$count			= 50;

		// A bit of filtering
		$page 	= ($page>=0) ? $page : 0;		

		// Get all the items; if we are an admin, we also get the hidden one
		$data   = new Data();
		$items    = $data->getArchive($year, $month, $this->_admin, $count, $page * $count);
		$months = array('', 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December');
		$this->view->items = $items;
		$this->view->models = $this->getModels();
		$this->view->month = $months[$month];
		$this->view->year  = $year;
		$this->view->archive = true;
			
		// Page title
		$title 		= $this->_properties->getProperty('title');
		$subtitle 	= $this->_properties->getProperty('subtitle');
		$separator 	= $title ? "|" : "";
		$this->view->headTitle("$title $separator {$months[$month]} $year");
			
		// Prepare the common elements
		$this->common();

		// Add paging
		$this->view->count		 = $count; 
		$this->view->page		 = $page;
		$this->view->hasprevious = ($page>0) ? true : false;
		$this->view->hasnext 	 = (count($items) >= $count) ? true : false;	
		$this->view->nextlink 	  = "/archives/$year/$month?page=" . ($page + 1);
		$this->view->previouslink = "/archives/$year/$month?page=" . ($page - 1);
		
		// Add specific styles and javascripts
		$this->view->headScript()->appendFile('js/controllers/timeline.js');
		$this->view->headLink()->appendStylesheet('style/lightbox.css');

		// Render the index view
		$this->render('index');
	}
	
	public function typeAction() {
		// Request parameters
		// Let's get some parameters
		$type		= $this->validateTypes($this->getRequest()->getParam("type"));
		$output		= $this->getRequest()->getParam("output");		

		// Action parameters
		$page 			= $this->getRequest()->getParam("page");
		$count			= $this->getRequest()->getParam("count");

		// A bit of filtering
		$page 	= ($page>=0) ? $page : 0;
		$count	= ($count>0 && $count<50) ?  $count : 50;
		
		// Types map
		$types = array();
		$types['status'] = "status updates";
		$types['link'] = "links";
		$types['blog'] = "posts";
		$types['audio'] = "tracks";
		$types['image'] = "pictures";
		$types['video'] = "videos";
		$type_name = $types[$type[0]];		
		// Get all the items; if we are an admin, we also get the hidden one
		// Get the items
		$data = new Data();
		$items = $data->getLastItems(50, 0,false, false, $type);
		$this->view->items 	= $items;
		$this->view->models = $this->getModels();
		$this->view->type 	= $type_name;
		
		// Page title
		$title 		= $this->_properties->getProperty('title');
		$subtitle 	= $this->_properties->getProperty('subtitle');
		$separator 	= $title ? "|" : "";
		$page_title = "$title $separator All $type_name";
		$this->view->headTitle($page_title);
		
		// If rss, we stop here
		if ($output == "rss") {
			$key = "type_" . $type['0'] . "_{$count}_{$page}";
			$this->generateRss($key, $items, $page_title);
		}
			
		// Prepare the common elements
		$this->common();	

		// Add specific styles and javascripts
		$this->view->headScript()->appendFile('js/controllers/timeline.js');
		$this->view->headLink()->appendStylesheet('style/lightbox.css');
		
		// Set link to RSS of page
		$host 		= trim(Zend_Registry::get("host"), '/');
		$rss_link   = "http://$host/type/" . urlencode($type[0]) . "?output=rss";
		$this->view->headLink()->appendAlternate($rss_link, "application/rss+xml", "RSS Stream");

		// Render the index view
		$this->render('index');
	}

	public function tagAction() {
		// Request parameters
		$tag			= $this->getRequest()->getParam("tag");
		$output			= $this->getRequest()->getParam("output");

		// Action parameters
		$page 			= $this->getRequest()->getParam("page");
		$count			= $this->getRequest()->getParam("count");

		// A bit of filtering
		$page 	= ($page>=0) ? $page : 0;
		$count	= ($count>0 && $count<50) ?  $count : 50;
			
		// Get all the items; if we are an admin, we also get the hidden one
		$data   = new Data();
		$items    = $data->getItemsByTag(array($tag), false,$count, $page * $count, $this->_admin);
		$this->view->items 	= $items;
		$this->view->models = $this->getModels();
		$this->view->tag 	= $tag;
		
		// Page title
		$title 		= $this->_properties->getProperty('title');
		$subtitle 	= $this->_properties->getProperty('subtitle');
		$separator 	= $title ? "|" : "";
		$page_title = "$title $separator Search for $tag";
		$this->view->headTitle($page_title);
		
		// If rss, we stop here
		if ($output == "rss") {
			$key = "tag_{$tag}_{$count}_{$page}";
			$this->generateRss($key, $items, $page_title);
		}
			
		// Prepare the common elements
		$this->common();

		// Add specific styles and javascripts
		$this->view->headScript()->appendFile('js/controllers/timeline.js');
		$this->view->headLink()->appendStylesheet('style/lightbox.css');
		
		// Set link to RSS of page
		$host 		= trim(Zend_Registry::get("host"), '/');
		$rss_link   = "http://$host/tag/" . urlencode($tag) . "?output=rss";
		$this->view->headLink()->appendAlternate($rss_link, "application/rss+xml", "RSS Stream");

		// Render the index view
		$this->render('index');
	}


	public function selectionAction() {
		// Action parameters
		$selection		= $this->getRequest()->getParam("set");

		// Get all the items; if we are an admin, we also get the hidden one
		$selection = unserialize(base64_decode(urldecode($selection)));
		if (!$selection || !is_array($selection)) {
			throw new Stuffpress_Exception("Invalid selection $selection");
		}

		$items = array();
		$data  = new Data();
		foreach($selection as $s) {
			$i = $data->getItem($s[0], $s[1]);
			if ($i && (!$i->isHidden())) {
				$items[] = $i;
			}
		}

		$sorter 	= new Stuffpress_SortItems();
		$sorter->sort($items, 1);

		// Prepare the common elements
		$this->common();

		// Add specifics entries
		$this->view->selection = true;
		$this->view->items  = $items;
		$this->view->models	= $this->getModels();

		// Set page title
		$title 		= $this->_properties->getProperty('title');
		$subtitle 	= $this->_properties->getProperty('subtitle');
		$separator 	= $title ? "|" : "";
		$this->view->headTitle("$title $separator $subtitle");

		// Add specific styles and javascripts
		$this->view->headScript()->appendFile('js/controllers/timeline.js');
		$this->view->headLink()->appendStylesheet('style/lightbox.css');

		// Render the index
		$this->render('index');
	}

	public function searchAction() {
		// Action parameters
		$search = $this->getRequest()->getParam("search");
		$output = $this->getRequest()->getParam("output");
		
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
		
		// Set page title
		$title 		= $this->_properties->getProperty('title');
		$subtitle 	= $this->_properties->getProperty('subtitle');
		$separator 	= $title ? "|" : "";
		$page_title = "$title $separator Search results for $search";
		$this->view->headTitle($page_title);
		
		// If rss, we stop here
//		if ($output == "rss") {
//			$key = "search_$search";
//			$this->generateRss($key, $items, $page_title);
//		}

		// Prepare the common elements
		$this->common();

		// Add specifics entries
		$this->view->search = $search;
		$this->view->items  = $items;
		$this->view->models	= $this->getModels();

		// Add specific styles and javascripts
		$this->view->headScript()->appendFile('js/controllers/timeline.js');
		$this->view->headLink()->appendStylesheet('style/lightbox.css');
		
		// Set link to RSS of page
//		$host 		= trim(Zend_Registry::get("host"), '/');
//		$rss_link   = "http://$host/search/" . urlencode($search) . "?output=rss";
//		$this->view->headLink()->appendAlternate($rss_link, "application/rss+xml", "RSS Stream");

		// Render the index
		$this->render('index');
	}

	public function viewAction()
	{
		// Get, check and setup the parameters
		$source_id 		= $this->getRequest()->getParam("source");
		$item_id		= $this->getRequest()->getParam("item");

		//Verify if the requested item exist
		$data			= new Data();
		if (!($item = $data->getItem($source_id, $item_id))) {
			throw new Stuffpress_NotFoundException("This item does not exist");
		}

		// Is the item public otherwise we must be admin ?
		if (!$this->_admin && $item->isHidden()) {
			throw new Stuffpress_AccessDeniedException("This item is marked private.");
		}

		// Get a hold on the model
		$sources	= new Sources();
		$source		= $sources->getSource($source_id);
		$model 		= SourceModel::newInstance($source['service'], $source);
		$this->view->model = $model;
		$this->view->item  = $item;

		// Prepare the common elements
		$this->common();
		
		// Add the keywords
		$tags = $item->getTags();
		$nice_tags = array();
		if ($tags && count($tags) > 0) {
			foreach($tags as $tag) {
				$nice_tags[] = $tag['tag'];
			}
		}
		$this->view->headMeta()->appendName('keywords', implode(' ', $nice_tags));

		// Set page title
		$title 		= $this->_properties->getProperty('title');
		$subtitle 	= mb_substr($item->getTitle(), 0, 50);
		$separator 	= $title ? "|" : "";
		$this->view->headTitle("$title $separator $subtitle");

		// Add specific styles and javascripts
		$this->view->headScript()->appendFile('js/controllers/timeline.js');
		if (!$this->_embed) $this->view->headLink()->appendStylesheet('style/lightbox.css');
	}

	public function slideAction()
	{
		// No layout required here
		$this->_helper->layout->disableLayout();

		// Get, check and setup the parameters
		$source_id 		= $this->getRequest()->getParam("source");
		$item_id		= $this->getRequest()->getParam("item");

		//Verify if the requested item exist
		$data			= new Data();
		if (!($item = $data->getItem($source_id, $item_id))) {
			throw new Stuffpress_NotFoundException("This item does not exist");
		}

		// Is the item public otherwise we must be admin ?
		if (!$this->_admin && $item->isHidden()) {
			throw new Stuffpress_AccessDeniedException("This item is marked private.");
		}

		// Get a hold on the model
		$sources	= new Sources();
		$source		= $sources->getSource($source_id);
		$model 		= SourceModel::newInstance($source['service'], $source);

		// Prepare the view
		$this->view->model = $model;
		$this->view->item  = $item;
		$this->view->admin = $this->_admin;
	}

	public function imageAction()
	{
		// No layout required here
		$this->_helper->layout->disableLayout();

		// Get, check and setup the parameters
		$source_id 		= $this->getRequest()->getParam("source");
		$item_id		= $this->getRequest()->getParam("item");
		$size			= $this->getRequest()->getParam("size");

		//Verify if the requested item exist
		$data			= new Data();
		if (!($item = $data->getItem($source_id, $item_id))) {
			throw new Stuffpress_NotFoundException("This item does not exist");
		}

		// Is the item public otherwise we must be admin ?
		if (!$this->_admin && $item->isHidden()) {
			throw new Stuffpress_AccessDeniedException("This item is marked private.");
		}

		// This is not a layout page
		$this->_helper->viewRenderer->setNoRender();
		$this->_helper->layout->disableLayout();


		// Redirtect to the image
		$this->_redirect($item->getImageUrl($size));
	}

	public function rssAction() {
		// Let's get some parameters
		$sources	= $this->validateSources($this->getRequest()->getParam("sources"));
		$types		= $this->validateTypes($this->getRequest()->getParam("types"));
		$nopre		= $this->getRequest()->getParam("nopre");
		$nopre   	= $nopre ? true : false;
		
		// We need a title
		$title 		= $this->_properties->getProperty('title');
		$subtitle 	= $this->_properties->getProperty('subtitle');
		if (strlen($subtitle) > 0) {
			$title = $title . " | " . $subtitle;
		}
		
		// Need a RSS key to identify this specific request
		$key = $nopre . "_" . @implode("_", $sources) . "_" . @implode("_", $types);
	
		// Get the items
		$data = new Data();
		$items = $data->getLastItems(50, 0,false, $sources, $types);
		
		// Generate the RSS
		$this->generateRss($key, $items, $title, $nopre);
	}

	public function hideAction() {
		// Get, check and setup the parameters
		$source_id 		= $this->getRequest()->getParam("source");
		$item_id		= $this->getRequest()->getParam("item");

		//Verify if the requested item exist
		$data			= new Data();
		if (!($item			= $data->getItem($source_id, $item_id))) {
			return $this->_helper->json->sendJson(false);
		}

		// Get the user
		$users 			= new Users();
		$attributes		= $item->getAttributes();
		$user			= $users->getUser($attributes['user_id']);

		// Check if we are the owner
		if ($this->_application->user->id != $user->id) {
			return $this->_helper->json->sendJson(false);
		}

		// Ok, we can hide the item
		$data->hideItem($source_id, $item_id);
		return $this->_helper->json->sendJson(true);
	}

	public function showAction() {
		// Get, check and setup the parameters
		$source_id 		= $this->getRequest()->getParam("source");
		$item_id		= $this->getRequest()->getParam("item");

		//Verify if the requested item exist
		$data			= new Data();
		if (!($item			= $data->getItem($source_id, $item_id))) {
			return $this->_helper->json->sendJson(false);
		}

		// Get the user
		$users 			= new Users();
		$attributes		= $item->getAttributes();
		$user			= $users->getUser($attributes['user_id']);

		// Check if we are the owner
		if ($this->_application->user->id != $user->id) {
			return $this->_helper->json->sendJson(false);
		}

		// Ok, we can hide the item
		$data->showItem($source_id, $item_id);
		return $this->_helper->json->sendJson(true);
	}

	public function settitleAction() {
		// Get, check and setup the parameters
		$source_id 		= $this->getRequest()->getParam("source");
		$item_id		= $this->getRequest()->getParam("item");
		$title			= $this->getRequest()->getParam("value");

		//Verify if the requested item exist
		$data			= new Data();
		if (!($item = $data->getItem($source_id, $item_id))) {
			return $this->_helper->json->sendJson(false);
		}

		// Get the user
		$users 			= new Users();
		$user			= $users->getUser($item->getUserid());

		// Check if we are the owner
		if ($this->_application->user->id != $user->id) {
			return $this->_helper->json->sendJson(false);
		}

		// Ok, we can change the title
		$item->setTitle($title);

		// Return the new title
		if (!$title) $title = '[Click to add text]';
		die($title);
	}

	public function settimestampAction() {
		// Get, check and setup the parameters
		$source_id 		= $this->getRequest()->getParam("source");
		$item_id		= $this->getRequest()->getParam("item");
		$timestamp		= $this->getRequest()->getParam("timestamp");

		//Verify if the requested source exist
		$sources		= new Sources();
		if (!($source = $sources->getSource($source_id))) {
			throw new Stuffpress_Exception("Error updating timestamp for source $source - Invalid source");
		}

		// Get the user
		$users 			= new Users();
		$user			= $users->getUser($source['user_id']);

		// Check if we are the owner
		if ($this->_application->user->id != $user->id) {
			throw new Stuffpress_Exception("Error updating timestamp for source $source - Not the owner");
		}

		// Get the user properties
		$properties = new Properties($user->id);

		// Check if the date is valid
		if (!($date = Stuffpress_Date::strToTimezone($timestamp, $properties->getProperty('timezone')))) {
			$this->addErrorMessage("Could not understand the provided date/time");
			return $this->_forward('index');
		}

		// Ok, we can change the title of the item
		$model	= SourceModel::newInstance($source['service'], $source);
		$model->setTimestamp($item_id, $date);
		$this->addStatusMessage("Life is cool");
		// We redirect to the stream
		$this->_forward('index');
	}

	private function validateSources($string) {
		$sources_db = new Sources();
		$sources_u  = $sources_db->getSources();
		$result		= array();
		$i_sources = explode(",", $string);

		foreach($i_sources as $s) {
			if (!isset($sources_u[$s])) {
				return false;
			}
				
			$result[] = $s;
		}
		return $result;
	}

	private function validateTypes($string) {
		$i_types = explode(",", $string);
		foreach($i_types as $t) {
			if (!in_array($t, array('status', 'link', 'blog', 'image', 'audio', 'video'))) {
				return false;
			}
		}
		return $i_types;
	}
}
