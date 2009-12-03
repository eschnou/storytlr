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

class Admin_PasswordController extends Admin_BaseController
{	
	protected $_section = 'config';
	
	public function indexAction() {
		$this->common();
		$this->view->form = $this->getForm(); 
		$this->view->headScript()->appendFile('js/controllers/password.js');
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
		$password	= $values['password'];
		$confirm	= $values['confirm'];

		if (strcmp($password, $confirm)) {
			return $this->_helper->json->sendJson(array("Passwords do not match."));
		}

		// Save the new values
		$users 		= new Users();
		$users->setPassword($this->_application->user->id, $password);
			
		// Ok
		return $this->_helper->json->sendJson(false);
	}
	
	private function getForm() {
		$form = new Stuffpress_Form();
   
		// Add the form element details
		$form->setMethod('post');
		$form->setName('formPassword');

		// New password
		$e = $form->createElement('password', 'password', array('label' => 'New password:', 'decorators' => $form->elementDecorators));
		$e->addValidator('StringLength', false, array(6, 20));
		$e->setRequired(true);
		$form->addElement($e);
		
		// Confirm password
		$e = $form->createElement('password', 'confirm', array('label' => 'Confirm password:', 'decorators' => $form->elementDecorators));
		$e->addValidator('StringLength', false, array(6, 20));
		$e->setRequired(true);
		$form->addElement($e);
	
		// use addElement() as a factory to create 'Post' button:
		$form->addElement('button', 'save', array('label' => 'Save', 'onclick' => "submitFormPassword();", 'decorators' => $form->buttonDecorators));
		
		return $form;
	}
}