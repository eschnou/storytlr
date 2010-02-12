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

class Admin_AuthController extends Zend_Controller_Action
{	
    protected $_application;
    
    protected $_bookmarklet = false;

    public function init()
    {
        $this->_application = Stuffpress_Application::getInstance();
    	// If request is from a bookmarklet, we use another layout
		if ($this->_hasParam('bookmarklet') && $this->_getParam('bookmarklet')) { 
			$this->_helper->layout->setlayout('bookmarklet');
			$this->_bookmarklet = true;
			$this->view->bookmarklet = true;
		}
    }
	
	public function indexAction()
	{
		$target = $this->_getParam("target");
		$form   = $this->getForm();
		
		if ($target) {
			$form->getElement('target')->setValue($target);
		}
		
		if (!isset($this->view->form)) {
			$this->view->form = $form;
		}
	}

	public function loginAction()
	{
		// This should be a post request
		if (!$this->getRequest()->isPost()) {
			return $this->_forward('index', 'index', 'admin');
		}
		
		// Whatever happens from here, we first clear all identity
		$this->_application->user = false;
		$this->_application->role = 'guest';
		
		// Validate the form
		$form 	= $this->getForm();
		if (!$form->isValid($_POST)) {
			// Failed validation; redisplay form
			$this->view->failedValidation = true;
			$this->view->form = $form;
			return $this->_forward('index');
		}

		// Get (and maybe we should also clean) the values
		$values   = $form->getValues();
		$username = $values['username'];
		$password = $values['password'];
		$remember = $values['remember'];
		
		// Get the user 
		$users	= new Users();
		if (!$user = $users->getUserFromUsername($username)) {
			$this->view->failedAuthentication = true;
			$this->view->form = $form;
			return $this->_forward('index');
		}
		
		// Validate the password
		if ($user->password != md5($password)) {
			$this->view->failedAuthentication = true;
			$this->view->form = $form;
			return $this->_forward('index');
		}
		
		// Is the user verified ?
		if (!$user->verified) {
			$this->view->unverified=true;
			$this->view->failedAuthentication = true;
			$this->view->form = $form;
			return $this->_forward('index');
		}
		
		// We assing arole
		$role = 'member';
		
		// Everything ok, we can log in and assign role
		$this->_application->user = $user;
		$this->_application->role = $role;
		
		// We can also hit the login stats
		$users->hitLogin($user->id);
		
		// Send the cookie with the authentication data
		$cookie	= new Stuffpress_Cookie($user->id);
		$cookie->set($remember);

		// If we have a special target
		if ($values['target'] == 'user_page') {
			$config = Zend_Registry::get('configuration');
			$domain = trim($config->web->host, " /");
			$path   = trim($config->web->path, " /");
			
			// If single user, we go back to the host
			if (isset($config->app->user)) {
				$url = "http://$domain/$path";
			} else {
				$url	= "http://{$user->username}.$domain/$path";
			};

			return $this->_redirect($url);
		}
		else if ($values['target']) {
			return $this->_redirect($values['target']);
		} 

		// Otherwise we go back to the home page
		return $this->_helper->redirector('index', 'index', 'admin');
	}
	
	public function logoutAction()
	{
		
		// We clear the identity immediately
		$this->_application->user = false;
		$this->_application->role = 'guest';
		
		// Clear the cookie
		$cookie = new Stuffpress_Cookie();
		$cookie->logout();
		
		// Get the request parameters
		$target 	= 	$this->_getParam("target");
		
		// If we have a target, we go there
		if ($target) {
			return $this->_redirect($target);
		}
		
		// Otherwise we go back to the home page
		return $this->_redirect('/');
	}

	private function getForm() {
		$form = new Stuffpress_Form();
   
		// Add the form element details
		$form->setAction('admin/auth/login');
		$form->setMethod('post');
		$form->setName('formLoginMain');

		// Create and configure username element:
		$username = $form->createElement('text', 'username',  array('label' => 'Username:', 'decorators' => $form->noDecorators));
		$username->addValidator('alnum');
		$username->addValidator('stringLength', false, array(4, 20));
		$username->setRequired(true);
		$username->addFilter('StringToLower');
		$form->addElement($username);
		
		// Create and configure password element:
		$password = $form->createElement('password', 'password', array('label' => 'Password:', 'decorators' => $form->noDecorators));
		$password->addValidator('StringLength', false, array(6, 20));
		$password->setRequired(true);
		$form->addElement($password);		
		
		// Remember me
		$element = $form->createElement('checkbox', 'remember',  array('label' => 'Remember:', 'decorators' => $form->noDecorators, 'class' => 'remember'));
		$element->setRequired(true);
		$form->addElement($element);	

		// Add a hidden element with a target url
		$target	= $form->createElement('hidden', 'target');
		$target->setDecorators(array(array('ViewHelper')));

		// Add a hidden element with a bookmarklet flag
		$bk	= $form->createElement('hidden', 'bookmarklet');
		$bk->setDecorators(array(array('ViewHelper')));		
		$bk->setValue($this->_bookmarklet);
		
		// Add elements to form:
		$form->addElement($target);
		$form->addElement($bk);
		$form->addElement('submit', 'login', array('label' => 'Sign in', 'decorators' => $form->noDecorators));
		
		return $form;
	}
}