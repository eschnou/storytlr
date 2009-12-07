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

class Admin_PagesController extends Admin_BaseController
{
    protected $_section = 'config';
	
	public function indexAction() {
		$p = new Pages();
		$this->view->pages = $p->getSchemas();
		$this->view->user_pages = $p->getPages($this->_application->user->id);
		
		// Prepare view
		$this->common();
		$this->view->headScript()->appendFile('js/storytlr/effects.js');
		$this->view->headScript()->appendFile('js/controllers/pages.js');	
	}
	
	public function addAction() {
		// Get, check and setup the parameters
		$type = $this->getRequest()->getParam("type");
		
		// Get the page model
		$model 	= Pages::getModel($type);
		
		// Prepare the form
		$form	= $model->getForm();
		$form->populate($model->getDefaultValues());
		
		// Prepare the view
		$this->common();
		$this->view->headScript()->appendFile('js/storytlr/effects.js');
		$this->view->headScript()->appendFile('js/controllers/pages.js');
		$this->view->name		 = $model->getName();
		$this->view->description = $model->getDescription();
		$this->view->edit		 = 'false';		
		$this->view->form 		 = $form;
		
		// Add TinyMCE code
		Stuffpress_TinyMCE::append($this->view);
	}
	
	public function editAction() {
		// We'll need to access the pages database
		$pages		= new Pages();
		
		// Get the source from the request
		$id 		= (int)    $this->_getParam('id');
		$type 		= (string) $this->_getParam('type');
		
		// Validate the parameters
		if (! (($id>=0) && in_array($type, Pages::getAvailablePages()))) {
			throw new Stuffpress_Exception("Parameters failed validation");
		}
		
		// Does the page exist ?
		if (!$page = $pages->getPage($id)) {
			throw new Stuffpress_Exception("Unknown page with id $id");
		}
		
		// Are we the owner ?
		if ($page['user_id'] != $this->_application->user->id) {
			throw new Stuffpress_AccessDeniedException("Not the owner of page $id");
		}
		
		// Get the page model
		$model 	= Pages::getModel($type);
		
		// Prepare the form
		$form		= $model->getForm();
		$values 	= $model->getPageValues($id);
		$values['id'] 	 = $id;
		$values['title'] = $page['title'];
		$form->populate($values);
		
		// Prepare the view
		$this->common();
		$this->view->headScript()->appendFile('js/storytlr/effects.js');
		$this->view->headScript()->appendFile('js/controllers/pages.js');	
		$this->view->name		 = $model->getName();
		$this->view->edit		 = 'true';
		$this->view->description = $model->getDescription();
		$this->view->form 		 = $form;
		
		// Add TinyMCE code
		Stuffpress_TinyMCE::append($this->view);
	}
	
	public function saveAction() {
		// We'll need to access the pages database
		$pages		= new Pages();
					
		// Is the form correctly posted ?
		if (!$this->getRequest()->isPost()) {
			throw new Stuffpress_Exception("Invalid request - must be post");
		}
		
		// Get the source from the request
		$id 		= (int)    $this->_getParam('id');
		$type 		= (string) $this->_getParam('type');
		
		// Validate the parameters

		if (! (($id>=0) && in_array($type, Pages::getAvailablePages()))) {
			throw new Stuffpress_Exception("Parameters failed validation");
		}
		
		// If it is an edit; are we the owner ?
		if ($id > 0) {
			// Does the page exist ?
			if (!$page = $pages->getPage($id)) {
				throw new Stuffpress_Exception("Unknown page with id $id");
			}
			// Are we the owner ?
			if ($page['user_id'] != $this->_application->user->id) {
				throw new Stuffpress_AccessDeniedException("Not the owner of page $id");
			}
		}
		
		// Get the page descriptor
		$model		= Pages::getModel($type);
		
		// Validate the form
		$form		= $model->getForm();
		if (!$form->isValid($_POST)) {
			return $this->_helper->json->sendJson($form->getErrorArray());
		}
		
		// Get the form values
		$values 	= $form->getValues();
		
		// Proceed and save the values
		$title		= @$values['title']; // There shoudl always be a title
		
		// Create the page if it does not exist
		if ($id ==0) {
			$id = $pages->addPage($type, $title);
		} 
		// else update the title of the page
		else {
			$pages->setTitle($id, $title);
		}
		
		// Save the page configuration
		$model->processForm($id, $values);
		
		// Ok
		return $this->_helper->json->sendJson(false);
	}
	
	public function orderAction() {
		// Get, check and setup the parameters
		$order = $this->getRequest()->getParam("user_pages");
		
		// Assign the new positions
		$pages = new Pages();
		for($i=0; $i<count($order); $i++) {
			
			$page_id = $order[$i];
			
			// Does the page exist ?
			if (!$page = $pages->getPage($page_id)) {
				throw new Stuffpress_Exception("Unknown page with id $page_id");
			}
			
			// Are we the owner ?
			if ($page['user_id'] != $this->_application->user->id) {
				throw new Stuffpress_AccessDeniedException("Not the owner of page $page_id");
			}
						
			$pages->setPosition($page_id, $i);
		}
		
		return $this->_helper->json->sendJson(false);
	}
	
	public function deleteAction() {
		// Get, check and setup the parameters
		$page_id	= $this->getRequest()->getParam("id");

		// Do we own the page ?
		$pages = new Pages();
		
		if (!$page = $pages->getPage($page_id)) {
			throw new Stuffpress_Exception("Unknown page with id $page_id");
		}
		
		if ($page['user_id'] != $this->_application->user->id) {
			throw new Stuffpress_AccessDeniedException("Not the owner of page $page_id");
		}
		
		$pages->deletePage($page_id);
		
		// Delete the page properties
		$properties = new PagesProperties(array(Properties::KEY => $page_id));
		$properties->deleteAllProperties();
		
		return $this->_helper->json->sendJson(false);
	}
}