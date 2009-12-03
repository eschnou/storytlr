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

class Admin_RecoverController extends Admin_BaseController
{
    public function indexAction()
    {
		$this->view->form = $this->getForm();
    }
    
    public function submitAction()
    {
        if (!$this->getRequest()->isPost()) {
            return $this->_forward('index');
        }
        
        $form	= $this->getForm();
        
        // Validate the form itself
        if (!$form->isValid($_POST)) {
            $this->view->form = $form;
            return $this->render('index');
        }

		// Get the form data
		$values	= $form->getValues();
		$email	= $values['email'];
		
		// Find the user
		$users	= new Users();
		if (!$user 	= $users->getUserFromEmail($email)) {
			$this->view->failedRecovery = true;
			return $this->_forward('index');
		}
		
		// Change the password
		$password	= Stuffpress_Token::create(8);
		$users->setPassword($user->id, $password);
		
		// Send the user an email with the new password
		Stuffpress_Emails::sendRecoveryEmail($email, $user->username, $password);

		// Done !
		$this->view->email 	  = $email; 
		$this->render('success');
    }
        
    public function getForm() {
    	$form = new Stuffpress_Form();
    	
    	// Add the form element details
		$form->setAction('admin/recover/submit');
     	$form->setMethod('post');
     	$form->setName('formRecover');

		// Create and configure email element:
		$e = $form->createElement('text', 'email',  array('label' => 'Email:', 'decorators' => $form->elementDecorators));
		$e->addValidator(new Zend_Validate_EmailAddress());
        $e->setRequired(true);
        $form->addElement($e);     	
		
		// Submit button
		$button = $form->createElement('submit', 'post', array('label' => 'Recover', 'decorators' => $form->buttonDecorators));;
		$form->addElement($button);
		
		return $form;
    }
   
}