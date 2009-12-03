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

class Admin_ProfileController extends Admin_BaseController
{
	protected $_section = 'config';

	public function indexAction() {
		// Get the user properties
		$values 	= $this->_properties->getProperties(array(	"first_name", "last_name", "bio", "location", "avatar_image"));
			
		// Get the form and assign the values
		$form = $this->getForm();
		$form->populate($values);
		$this->view->form = $form;
		
		// Render the aa-vatar form
		if (isset($values['avatar_image'])) {
			$this->view->avatar = $values['avatar_image'];
		}
		
		// Add view elements
		$this->common();
		$this->view->headScript()->appendFile('js/controllers/profile.js');
	}
	
	public function submitAction()
	{
		// Validate the form and extract the values
		if (!$this->getRequest()->isPost()) {
			return $this->_helper->json->sendJson(true);
		}

		$form = $this->getForm();
		if (!$form->isValid($_POST)) {
			return $this->_helper->json->sendJson($form->getErrorArray());
		}

		// Get the values and proceed
		$values 	= $form->getValues();
		$first_name	= $values['first_name'];
		$last_name	= $values['last_name'];		
		$bio		= $values['bio'];	
		$location	= $values['location'];	

		// Save the new values
		$this->_properties->setProperty('first_name', $first_name);
		$this->_properties->setProperty('last_name', $last_name);
		$this->_properties->setProperty('location', $location);
		$this->_properties->setProperty('bio', $bio);
			
		// Ok
		return $this->_helper->json->sendJson(false);
	}
	
	public function noavatarAction() {
		$files = new Files();
		$file  = $this->_properties->getProperty('avatar_image');
   		$this->_properties->deleteProperty('avatar_image');
   		$files->deleteFile($file);		
   		$this->_forward('index');
	}
	
	public function uploadimageAction() {
		$this->_forward('uploadimage', 'file', 'public', array('source' => 'profile'));
	}

	private function getForm() {
		$form = new Stuffpress_Form();
   
		// Add the form element details
		$form->setMethod('post');
		$form->setName('formProfile');

		// First name
		$e = $form->createElement('text', 'first_name',  array('label' => 'First name', 'class' => 'width1', 'decorators' => $form->elementDecorators));
		$e->addFilter('StripTags');
		$form->addElement($e);
		
		// Last name
		$e = $form->createElement('text', 'last_name',  array('label' => 'Last name', 'class' => 'width1', 'decorators' => $form->elementDecorators));
		$e->addFilter('StripTags');
		$form->AddElement($e);
		
		// Short Bio
		$e = $form->createElement('textarea', 'bio',  array('label' => 'Short bio', 'class' => 'width1', 'decorators' => $form->elementDecorators));
		$e->addFilter('StripTags');
		$form->AddElement($e);
		
		// Location
		$e = $form->createElement('text', 'location',  array('label' => 'Location', 'class' => 'width1', 'decorators' => $form->elementDecorators));
		$e->addFilter('StripTags');
		$form->AddElement($e);
		
		// use addElement() as a factory to create 'Post' button:
		$form->addElement('button', 'save', array('label' => 'Save', 'onclick' => "submitFormProfile();", 'decorators' => $form->buttonDecorators));
		
		return $form;
	}
}