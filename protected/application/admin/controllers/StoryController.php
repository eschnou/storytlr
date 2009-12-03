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

class Admin_StoryController extends Admin_BaseController 
{
	protected $_section = 'story';
    	
	public function indexAction() {
		$sources = $this->getAvailableSources();
		
		if (count($sources)==0) {
			$this->view->nosource = true;
			return;
		}
		
		if (!isset($this->view->form)) {		
			$this->view->form = $this->getForm($sources);
		}
		
		// Common view elements
		$this->common();

		// Setup the required javascripts
		$this->view->headScript()->appendFile('js/calendar_date_select/calendar_date_select.js');				
		
		// Add required css files
		$this->view->headLink()->appendStylesheet('style/calendar.css');	
		$this->view->headLink()->appendStylesheet('style/calendar_date_select/blue.css');		
		
		// Get errror and status messages
		$this->view->status_messages	= $this->getStatusMessages();
		$this->view->error_messages		= $this->getErrorMessages();
	}
	
	public function submitAction()
	{
		// Is the form correctly posted ?
		if (!$this->getRequest()->isPost()) {
			return $this->_forward('index');
		}

		// Is the form valid ?
		$form = $this->getForm($this->getAvailableSources());
		if (!$form->isValid($_POST)) {
			$this->view->form = $form;
			$this->addErrorMessage("Please check your input and try again.");
			return $this->_forward('index');
		}
		
		// Get the values
		// TODO SECURE THIS ! Especially verify that the user
		// has access to the given sources !
		$values 	= $form->getValues();
		$title		= $values['title'];
		$subtitle	= $values['subtitle'];
		$date_from	= Stuffpress_Date::strToTimezone($values['date_from'], $this->_properties->getProperty("timezone"));
		$date_to	= Stuffpress_Date::strToTimezone($values['date_to'], $this->_properties->getProperty("timezone")) + 86400;
		$sources	= $values['sources'];
		
		if (count($sources) == 0) {
			$this->view->form = $form;
			$this->addErrorMessage("You must select at least one source to build a story.");
			return $this->_forward('index');	
		}
		
		// Fetch the items
		$data		= new Data();
		$storyItems = new StoryItems();
		$items		= array();
		foreach($sources as $source_id) {
			$i = $data->getItemsByDate($date_from, $date_to, $source_id, true);
			foreach($i as $item) {
				$type	 = $item->getType();
				$items []= $item;
			}
		}
		
		// If no items, we have an error
		if (!$items || (count($items) == 0)) {
			$this->view->form = $form;
			$this->addErrorMessage("Your story does not contain any items.");
			return $this->_forward('index');	
		}
		
		// Create the new story
		$stories 	= new Stories();
		$story_id	= $stories->addStory($date_from, $date_to, $title, $subtitle, serialize($sources));		

		// Add the story items
		$images		= array();
		foreach($items as $item) {
			if ($item->getType() == SourceItem::IMAGE_TYPE) {
				$images []= $item->getImageUrl(ImageItem::SIZE_MEDIUM);
			}
			$storyItems->addItem($story_id, $item->getSource(), $item->getID(), $item->getPrefix(), $item->getTimestamp(), $item->ishidden());
		}
		
		// Pick an image randomly and save it
		if (count($images) > 0) {
			$image 	= $images[rand(0, count($images) - 1)];
			$this->setKeyImage($story_id, $image);
		}
		
		// Forward to the story page
		$config = Zend_Registry::get('configuration');
		$host	= $config->web->host;
		$url	= $this->getUrl($this->_application->user->username, "/story/edit/id/$story_id");
		return $this->_redirect($url);	
	}
		
	public function hideitemAction() {
		// Get, check and setup the parameters
		$story_id 		= $this->getRequest()->getParam("story");
		$source_id 		= $this->getRequest()->getParam("source");
		$item_id		= $this->getRequest()->getParam("item");
		
		//Verify if the requested story exist
		$stories		= new Stories();
		if (!($story	= $stories->getStory($story_id))) {
			return $this->_helper->json->sendJson(false);
		}

		// Check if we are the owner
		if ($this->_application->user->id != $story->user_id) {
			return $this->_helper->json->sendJson(false);
		}
		
		// Ok, we can hide the item
		$storyItems	= new StoryItems();
		$storyItems->hideItem($story_id, $source_id, $item_id);
		return $this->_helper->json->sendJson(true);
	}
	
	public function showitemAction() {
		// Get, check and setup the parameters
		$story_id 		= $this->getRequest()->getParam("story");
		$source_id 		= $this->getRequest()->getParam("source");
		$item_id		= $this->getRequest()->getParam("item");
		
		//Verify if the requested story exist
		$stories		= new Stories();
		if (!($story	= $stories->getStory($story_id))) {
			return $this->_helper->json->sendJson(false);
		}

		// Check if we are the owner
		if ($this->_application->user->id != $story->user_id) {
			return $this->_helper->json->sendJson(false);
		}
		
		// Ok, we can show the item
		$storyItems		= new StoryItems();
		$storyItems->showItem($story_id, $source_id, $item_id);
		return $this->_helper->json->sendJson(true);
	}
	
	public function settitleAction() {
		// Get, check and setup the parameters
		$story_id 		= $this->getRequest()->getParam("story");
		
		// TODO We should also filter and strip tags here
		$title	 		= substr($this->getRequest()->getParam("value"), 0, 25);
		
		//Verify if the requested story exist
		$stories		= new Stories();
		if (!($story	= $stories->getStory($story_id))) {
			return $this->_helper->json->sendJson(false);
		}

		// Check if we are the owner
		if ($this->_application->user->id != $story->user_id) {
			return $this->_helper->json->sendJson(false);
		}
		
		// Ok, we can set the title
		$stories->setTitle($story_id, $title);
		
		// Die with the string
		if (!$title) $title = '[Edit Title]';
		die($title);
	}
	
	public function setsubtitleAction() {
		// Get, check and setup the parameters
		$story_id 		= $this->getRequest()->getParam("story");
		
		// TODO We should also filter and strip tags here
		$title	 		= substr($this->getRequest()->getParam("value"),0, 50); 
		
		//Verify if the requested story exist
		$stories		= new Stories();
		if (!($story	= $stories->getStory($story_id))) {
			return $this->_helper->json->sendJson(false);
		}

		// Check if we are the owner
		if ($this->_application->user->id != $story->user_id) {
			return $this->_helper->json->sendJson(false);
		}
		
		// Ok, we can set the title
		$stories->setSubTitle($story_id, $title);
		
		// Die with the string
		if (!$title) $title = '[Edit Subtitle]';
		die($title);
	}
	
	public function setcoverAction() {
		// Get, check and setup the parameters
		$story_id 		= $this->getRequest()->getParam("story");
		$source_id 		= $this->getRequest()->getParam("source");
		$item_id 		= $this->getRequest()->getParam("item");
		
		//Verify if the requested story exist
		$stories		= new Stories();
		if (!($story	= $stories->getStory($story_id))) {
			return $this->_helper->json->sendJson(false);
		}

		// Check if we are the owner
		if ($this->_application->user->id != $story->user_id) {
			return $this->_helper->json->sendJson(false);
		}
		
		//Verify if the requested source exist
		$sources		= new Sources();
		if (!($source	= $sources->getSource($source_id))) {
			return $this->_helper->json->sendJson(false);
		}

		// Check if we are the owner
		if ($this->_application->user->id != $source['user_id']) {
			return $this->_helper->json->sendJson(false);
		}
		
		//Verify if the requested item exist
		$data		= new Data();
		if (!($item	= $data->getItem($source_id, $item_id))) {
			return $this->_helper->json->sendJson(false);
		}
		
		// Do we have a url ?
		if (!($url = $item->getImageUrl(ImageItem::SIZE_MEDIUM))) {
			return $this->_helper->json->sendJson(false);
		}
		
		// Get the old image and delete it
		$files	= new Files();
		if ($file = $files->getFileFromKey($story->thumbnail)) {
			$files->deleteFile($file->key);
		}
		
		// Ok, we can set the image
		$this->setKeyImage($story_id, $url);
		return $this->_helper->json->sendJson(true);
	}
	
	public function setprivateAction() {
		// Get, check and setup the parameters
		$story_id 		= $this->getRequest()->getParam("story");
		
		//Verify if the requested story exist
		$stories		= new Stories();
		if (!($story	= $stories->getStory($story_id))) {
			if ($this->_ajax) {
				return $this->_helper->json->sendJson(false);
			}
			else {
				$this->_forward('view', 'story', 'public', array('id' => $story_id));
			}
		}

		// Check if we are the owner
		if ($this->_application->user->id != $story->user_id) {
			if ($this->_ajax) {
				$this->_helper->json->sendJson(false);
			}
			else {
				$this->_forward('view', 'story', 'public', array('id' => $story_id));
			}
		}
		
		// Ok, we can hide the item
		$stories->setHidden($story_id, 1);
		
		// Done
		if ($this->_ajax) {
			return $this->_helper->json->sendJson(true);
		}
		else {
				$this->_forward('view', 'story', 'public', array('id' => $story_id));
		}
	}
	
	public function setpublicAction() {
		// Get, check and setup the parameters
		$story_id 		= $this->getRequest()->getParam("story");
		
		//Verify if the requested story exist
		$stories		= new Stories();
		if (!($story	= $stories->getStory($story_id))) {
			if ($this->_ajax) {
				return $this->_helper->json->sendJson(false);
			}
			else {
				$this->_forward('view', 'story', 'public', array('id' => $story_id));
			}
		}

		// Check if we are the owner
		if ($this->_application->user->id != $story->user_id) {
			if ($this->_ajax) {
				return $this->_helper->json->sendJson(false);
			}
			else {
				$this->_forward('view', 'story', 'public', array('id' => $story_id));
			}
		}
		
		// Ok, we can show the item
		$stories->setHidden($story_id, 0);
		if ($this->_ajax) {
			return $this->_helper->json->sendJson(true);
		}
		else {
			$this->_forward('view', 'story', 'public', array('id' => $story_id));
		}
	}
	
	public function deleteAction() {
		// Get, check and setup the parameters
		$story_id 		= $this->getRequest()->getParam("id");
		
		//Verify if the requested story exist
		$stories		= new Stories();
		if (!($story	= $stories->getStory($story_id))) {
			return $this->_helper->json->sendJson(false);
		}

		// Check if we are the owner
		if ($this->_application->user->id != $story->user_id) {
			return $this->_helper->json->sendJson(false);
		}
		
		// Ok, we can hide the item
		$stories->deleteStory($story_id);
		return $this->_helper->json->sendJson(true);
	}
	
	private function getAvailableSources() {
		$sourcesTable 	= new Sources();
		$sources 		= $sourcesTable->getSources();
		$s 				= array();
		if ($sources) foreach ($sources as $source) {
			$model = SourceModel::newInstance($source['service']);
			$model->setSource($source);
			if ($model->isStoryElement()) {
				$s[$source['id']] = $model->getServiceName() . "(" . $model->getAccountName() . ")";		
			}
		}
		
		return $s;
	}
	
	private function setKeyImage($story_id, $url) {
		$stories	= new Stories();
		$files 		= new Files();
		$file_id	= $files->downloadFile($url, "");
		$files->fitSquare($file_id, 50,  'thumbnails');
		$file		= $files->getFile($file_id);
		
		$stories->setThumbnail($story_id, $file->key);
	}
	
	private function getForm($sources) {
		$form = new Stuffpress_Form();
			
		// Add the form element details
		$form->setMethod('post');
		$form->setAction('admin/story/submit');
		$form->setName('formCreateStory');
		
		// Title
		$e = $form->createElement('text', 'title',  array('size' => 25, 'label' => 'Title', 'decorators' => array('ViewHelper', 'Errors'), 'maxlength' => 35));
        $e->setRequired(true);
		$e->addValidator('stringLength', false, array(0, 40));        
		$e->addFilter('StripTags');
        $form->addElement($e);

        // Subtitle
		$e = $form->createElement('text', 'subtitle',  array('size' => 25, 'label' => 'Subtitle', 'decorators' => array('ViewHelper', 'Errors'), 'maxlength' => 35));
        $e->setRequired(false);
		$e->addValidator('stringLength', false, array(0, 40));        
		$e->addFilter('StripTags');
        $form->addElement($e);
        
        // From
        // TODO Validate the date
		$e = $form->createElement('text', 'date_from',  array('size' => 25, 'readonly' => 'readonly', 'label' => 'From', 'decorators' => array('ViewHelper', 'Errors')));
        $e->setRequired(true);
        $form->addElement($e);
                 
       	// To
        // TODO Validate the date
		$e = $form->createElement('text', 'date_to',  array('size' => 25, 'label' => 'To', 'readonly' => 'readonly', 'decorators' => array('ViewHelper', 'Errors')));
        $e->setRequired(true);
        $form->addElement($e);

		// Sources
		$e = new Zend_Form_Element_MultiCheckbox('sources', array(
			'decorators' => array('ViewHelper', 'Errors'),
			'multiOptions' => $sources, 
			'class' => 'checkbox'
		));

		$e->setLabel('Sources');
		$form->addElement($e);

		// Save button
		$e = $form->createElement('submit', 'post', array('label' => 'Create', 'decorators' => $form->buttonDecorators));
		$form->addElement($e);

		return $form;
	}
	
	private function createArray($from, $to) {
		$result = array();
		for ($i=$from; $i < $to; $i++) {
			$result[$i] = $i;
		}
		return $result;
	}
	
}