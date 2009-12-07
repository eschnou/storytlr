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

class Admin_ServicesController extends Admin_BaseController 
{
    protected $_section = 'services';
    	
	public function indexAction() {
		// TODO Needs to display more info about the source and nicer string
		// Fetch the list of configured services for this user
		$table	= new Sources();
		$sources = $table->getSources();
		$s = array();
		if ($sources) foreach ($sources as $source) {
			if ($source['service'] == 'stuffpress') continue;
			$model = SourceModel::newInstance($source['service']);
			$model->setSource($source);
			$e = array();
			$e['service'] = $model->getServicePrefix();
			$e['url'] = $model->getServiceURL();
			$e['name'] = $model->getServiceName();
			$e['description'] = $model->getServiceDescription();
			$e['account'] = $model->getAccountName();			
			$e['id'] = $source['id'];
			$s[] = $e;
		}
		$this->view->sources = $s;
		
		// Fetch the list of available services
		$services = array();
		foreach (Sources::getAvailableSources() as $c) {
			if ($c == 'stuffpress') continue;
			$model = SourceModel::newInstance($c);
			$e['service'] = $c;
			$e['name'] = $model->getServiceName();
			$e['description'] = $model->getServiceDescription();
			$services[] = $e;
		}		
		$this->view->services = $services;
		
		// Get errror and status messages
		$this->view->status_messages	= $this->getStatusMessages();
		$this->view->error_messages		= $this->getErrorMessages();
		$this->view->suspended			= $this->_application->user->is_suspended;
		
		// Add common stuff
		$this->common();		
		$this->view->headScript()->appendFile('js/storytlr/effects.js');
		$this->view->headScript()->appendFile('js/controllers/services.js');
	}
	
	public function editAction() {
		// Get the source from the request and initialize the model
		$id = $this->_getParam('id');
		$sources = new Sources();
		
		if (!$source = $sources->getSource($id)) {
			throw new Stuffpress_Exception("Unknown source with id $id");
		}
		
		if ($source['user_id'] != $this->_application->user->id) {
			throw new Stuffpress_AccessDeniedException("Not the owner of source $id");
		}
		
		$model = SourceModel::newInstance($source['service']);
		$model->setSource($source);
		
		// Get the settings description, values and create form out of it
		// Build the form and validate
		if (!$this->view->form) {
			$this->view->form 	= $this->buildform($model, true);
		}
		
		// Assigns the form to the view and other view parameters
		$this->view->description = $model->getServiceDescription();
		$this->view->service_name = $model->getServiceName();
		$this->view->onload		 = "setFocus();";		
		
		// Commn view stuff
		$this->common();
		$this->view->headScript()->appendFile('js/storytlr/effects.js');
		$this->view->headScript()->appendFile('js/storytlr/focus.js');
		$this->view->headScript()->appendFile('js/controllers/services.js');
	}
	
	public function addAction() {
		// Get the source from the request and initialize the model
		$service = $this->_getParam('service');
		$model = SourceModel::newInstance($service);
		
		if (!$this->view->form) {
			$this->view->form 	= $this->buildform($model);
		}
		
		// Assigns the form to the view and other view parameters
		$this->view->description = $model->getServiceDescription();
		$this->view->service_name = $model->getServiceName();
		$this->view->onload		 = "setFocus();";		

		// Common view stuff
		$this->common();
		$this->view->headScript()->appendFile('js/storytlr/effects.js');
		$this->view->headScript()->appendFile('js/storytlr/focus.js');
		$this->view->headScript()->appendFile('js/controllers/services.js');
	}
	
	public function saveAction() {
		// We need the Sources database in this function
		$sources = new Sources();
		
		// Get the source from the request
		$id 		= $this->_getParam('id');
		$service 	= $this->_getParam('service');
		
		// Instantiate a model for the source
		$model = SourceModel::newInstance($service);
		
		// Build the form and validate
		$form 	= $this->buildform($model);
        if (!$form->isValid($_POST)) {
            // Failed validation; redisplay form
			$this->view->failedValidation = true;
			$this->view->service_name = $model->getServiceName();	
            $this->view->form = $form;
            return $this->render('edit');
        }
		
		// If we don't have a source, it means we create something
		$create = ($id>0) ? false : true;    
		if ($create) {
			$id = $sources->addSource($service);
		} 
		
		// Check if the source exist
		if (!$source = $sources->getSource($id)) {
			throw new Stuffpress_Exception("Unknown source with id $id");
		}
		
		// Check if we are the owner of the source
		if ($source['user_id'] != $this->_application->user->id) {
			throw new Stuffpress_AccessDeniedException("Not the owner of source $id");
		}
		
		// Assign the source to the model
		$model->setSource($source);
        
        // Get the values, and update the settings
		$update = $model->processConfigForm($form);
		
		// Import the data NOW if a new source
		if ($update) {
			try {
				$model->setImported(0);
				$count = count($model->importData());
			}
			catch(Exception $e) {
				$count = 0;
				$error = $e->getMessage();
				Zend_Registry::get('logger')->log("Exception updating $service ($id): " . $e->getMessage(), Zend_Log::ERR);
			}
			if ($count) {
				$this->addStatusMessage("Successfully imported $count entries.");
			}
			else if (isset($error)) {
				$this->addErrorMessage("There was an error while importing the data. Please doublecheck your import configuration and try again later. Let us know if this problem persists.");
				if ($create) {
					$sources->deleteSource($id);
				}
			}
			else {
				$this->addStatusMessage("No data imported. Maybe there is nothing available at the moment. Please double-check your import configuration just in case.");
			}
		}
		else {
				$this->addStatusMessage("Settings saved.");
		}
				
		// Add a succes message and forward to the index
		return $this->_forward('index', 'services', 'admin');
	}
	
	public function deleteAction() {
		// Get the source from the request and initialize the model
		$id = $this->_getParam('id');
		
		// Get the sources database
		$sources = new Sources();
		
		// Check if the source exists
		if (!$source = $sources->getSource($id)) {
			return $this->_helper->json->sendJson(true);
		}
		
		// Check if we own the source
		if ($source['user_id'] != $this->_application->user->id) {
			return $this->_helper->json->sendJson(true);
		}
		
		// Instantiate a model and remove all the data
		$model = SourceModel::newInstance($source['service']);
		$model->setSource($source);
		$model->deleteItems();
		
		// Delete the duplicated from the Data table
		$data = new Data();
		$data->deleteItems($source['id']);
		
		// Delete the source settings
		$properties = new SourcesProperties(array(Properties::KEY => $source['id']));
		$properties->deleteAllProperties();
		
		// Delete the tags
		$tags		= new Tags();
		$tags->deleteSource($source['id']);
		
		// Remove the source
		$sources->deleteSource($id);
		
		// We should also delete the associated comments
		$comments = new Comments();
		$comments->deleteComments($source['id']);
		
		// Forward to the list view with a success message
		return $this->_helper->json->sendJson(false);
	}
	
	private function buildForm($model, $populate=false) {
		$service  = $model->getServicePrefix();
		$source	  = $model->getSource();
		$id		  = isset($source) ? $source['id'] : 0;
		
		// Create an empty form
		$form = $model->getConfigForm($populate);
    	
    	// Add the form element details
		$form->setAction('admin/services/save');
     	$form->setMethod('post');
     	$form->setOptions(array('class' => $service));
     	$form->setName('formEditService');
     	
		// Add a submit button
		$button = $form->createElement('submit', 'save', array('onClick' => 'onFormSubmit();' , 'decorators' => array('ViewHelper')));
		$form->addElement($button);
	    
	    // Add a cancel button
		$element = $form->createElement('button', 'cancel', array('decorators' => array('ViewHelper')));
		$element->setAttrib('onClick', 'history.go(-1);return false;');
		$form->addElement($element);
		
		// Group elements
		$form->addDisplayGroup(array('save', 'cancel'), 'buttons', array('decorators' => $form->groupDecorators));							 

	    // Add a hidden element with the current id (for edits)
		$element = $form->createElement('hidden', 'id');
		$element->setDecorators(array(array('ViewHelper')));
		$element->setValue($id);
		$form->addElement($element);
		
		// Add a hidden element with the connector (for new)
		$element = $form->createElement('hidden', 'service');
		$element->setDecorators(array(array('ViewHelper')));
		$element->setValue($service);
		$form->addElement($element);
	    
		return $form;
	}
}