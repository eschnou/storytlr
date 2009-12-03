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
class Widgets_CustomController extends Stuffpress_Controller_Widget
{
	protected $_prefix = 'custom';
		
	public function indexAction() {		
		// Get, check and setup the parameters
		if (!($widget_id = $this->getRequest()->getParam("id"))) {
			throw new Stuffpress_Exception("No widget id provided to the widget controller"); 
		}
		
		// Verify if the requested widget exist and get its data
		$widgets = new Widgets();
		if (!$widget  = $widgets->getWidget($widget_id)) {
			throw new Stuffpress_Exception("Invalid widget id");
		}		
		
		// Get the widget properties
		$properties	= new WidgetsProperties(array(Properties::KEY => $widget_id));
		$this->view->title = $properties->getProperty('title');
		$this->view->content = $properties->getProperty('content');
	}
	
	public function formAction() {
		// Get, check and setup the parameters
		if (!($widget_id = $this->getRequest()->getParam("id"))) {
			throw new Stuffpress_Exception("No widget id provided to the widget controller"); 
		}
		
		// Get the current values
		$properties = new WidgetsProperties(array(Properties::KEY => $widget_id));
		$data		= $properties->getPropertiesArray(array('title', 'content'));
		
		// Get the form and populate with the current values
		$form = $this->getForm($widget_id);
		$form->populate($data);
	
		$this->view->form = $form;
	}
	
	public function submitAction() {
		if (!$this->getRequest()->isPost()) {
			return $this->_helper->json->sendJson(true);
		}

		$form = $this->getForm();
		if (!$form->isValid($_POST)) {
			return $this->_helper->json->sendJson($form->getErrorArray());
		}

		// Get the values and proceed
		$values 	= $form->getValues();
		$title		= $values['title'];
		$content	= $values['content'];
		$id			= $values['id'];	
		
		// Get the user
		$application = Stuffpress_Application::getInstance();
			
		// Get the widget properties
		$properties = new WidgetsProperties(array(Properties::KEY => $id, Properties::USER => $application->user->id));
		
		// Save the new properties
		$properties->setProperty('title', $title);
		$properties->setProperty('content', $content);
				
		// Ok send the result
		return $this->_helper->json->sendJson(false);
		
	}
	
	private function getForm($widget_id=0) {
		$form = new Stuffpress_Form();
			
		// Add the form element details
		$form->setMethod('post');
		$form->setName("form_widget_{$widget_id}");
		
		// Create and configure title element:
		$e = $form->createElement('text', 'title',  array('label' => 'Title:', 'class' => 'width1'));
		$e->addFilter('StripTags');
		$form->addElement($e);

		// Create and configure comment element:
		$e = $form->createElement('textarea', 'content',  array('label' => 'Content:'));
		$form->addElement($e);
			
		// Add a hidden element with the widget id
		$e = $form->createElement('hidden', 'id');
		$e->setValue($widget_id);
		$e->removeDecorator('HtmlTag');
		$form->addElement($e);

		// use addElement() as a factory to create 'Post' button:
		$e = $form->createElement('button', 'save', array('label' => 'Save', 'onclick' => "onSubmitFormWidget('custom', $widget_id);"));
		$e->setDecorators(array(
			array('ViewHelper'),
			array('HtmlTag', array('tag' => 'dd'))
		));
		$form->addElement($e);
		
		return $form;
	}
	
}