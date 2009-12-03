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

class Admin_PostemailController extends Admin_BaseController 
{
	protected $_section = 'tools';
    
    
	public function indexAction() {			
		// Get the current secret
		$this->view->secret = $this->_application->user->email_secret;
		
		// Get the form and assign the values
		$form = $this->getForm();

		// Add common view elements
		$this->common();
		$this->view->username = $this->_application->user->username;
		
		// Add the specific view elements
		$this->view->headScript()->appendFile('js/controllers/postemail.js');
		$this->view->form 	= $form;
	}

	public function submitAction()
	{
		// Prepare the DB table
		$users = new Users();
		
		// Is the form correctly posted ?
		if (!$this->getRequest()->isPost()) {
			return $this->_helper->json->sendJson(true);
		}

		// Is the form valid ?
		$form = $this->getForm();
		if (!$form->isValid($_POST)) {
			return $this->_helper->json->sendJson($form->getErrorArray());
		}
		
		// Get the values and proceed
		$values 	= $form->getValues();
		
		// Proceed and save the values
		$secret		= $values['secret'];
		
		// Save the new values
		$users->setSecret($this->_application->user->id, $secret);

		// Ok
		return $this->_helper->json->sendJson(false);
	}

	private function getForm() {
		$form = new Stuffpress_Form();
			
		// Add the form element details
		$form->setMethod('post');
		$form->setName('formPostEmail');
		
		// Create and configure secret element:
		$username = $form->createElement('text', 'secret',  array('label' => 'Secret:', 'onkeyup' => 'updateName(this.value);', 'decorators' => $form->elementDecorators));
        $username->addFilter('StringToLower');
		$username->addValidator('alnum');
		$username->addValidator('regex', false, array('/^[a-z0-9]+$/'));
        $username->addValidator('stringLength', false, array(4, 20));
        //$username->addValidator(new Stuffpress_Validate_AvailableUsername(Zend_Db_Table::getDefaultAdapter(), 'users', 'username'));
        $username->setRequired(true);
        $username->setDescription("Minimum of 4 characters.<br/> This will be your private email to post to storytlr: <strong>" . $this->_application->user->username . ".<span id='secret_link'>secret</span>@submit.storytlr.com</strong>");
		$form->addElement($username);
		
        // Save button
		$e = $form->createElement('button', 'save', array('label' => 'Save', 'onclick' => "submitFormPostEmail();", 'decorators' => $form->buttonDecorators));
		$form->addElement($e);

		return $form;
	}
	
}