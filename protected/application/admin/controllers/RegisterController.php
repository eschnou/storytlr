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

class Admin_RegisterController extends Stuffpress_Controller_Action
{
	protected $_secure = false;

	protected $_config;
	
    public function init()
    {
        $this->_config 	= Zend_Registry::get('configuration');
        
    	if ($this->_config->app->closed) {
			die("Registration not allowed");
		} 
    }
  
	public function indexAction()
	{
		$this->_forward('register');
	}
	
	public function registerAction()
	{
		$this->view->headScript()->appendFile('js/prototype/prototype.js');
		$this->view->headScript()->appendFile('js/controllers/register.js');
		
		if (!isset($this->view->form)) {
			$this->view->form = $this->getForm();
		}
		$this->view->status_messages	= $this->getStatusMessages();
		$this->view->error_messages		= $this->getErrorMessages();
	}

	public function signupAction()
	{
		if (!$this->getRequest()->isPost()) {
			$this->addErrorMessage("Form was not properly posted.");
			$this->_forward('index');
		}

		// Retrieve the form values and its values
		$form  		= $this->getForm();
		$valid 		= $form->isValid($_POST);
		$values 	= $form->getValues();
		$username 	= $values['username'];
		$email	  	= $values['email'];
		$password 	= $values['password'];
		 
		// Validate the form itself
		if (!$form->isValid($_POST)) {
			// Failed validation; redisplay form
			$this->view->form = $form;
			$this->addErrorMessage("Your form contains some errors, please correct them and submit this form again");
			return $this->_forward('register');
		}

		// Register user
		$users	= new Users();
		$user 	= $users->addUser($username, $password, $email);
		
		// Add some default widgets to the user
		$widgets	= new Widgets(array(Stuffpress_Db_Table::USER => $user->id));
		$widgets->addWidget('search');
		$widgets->addWidget('rsslink');
		$widgets->addWidget('links');
		$widgets->addWidget('lastcomments');
		$widgets->addWidget('archives');
		$widgets->addWidget('logo');
		
		// Add some default properties
		$properties = new Properties(array(Stuffpress_Db_Properties::KEY => $user->id));
		$properties->setProperty('theme', 'clouds');
		$properties->setProperty('title', ucfirst($username));
		$properties->setProperty('subtitle', "my life online");
		
		// Add the storytlr data source
		StuffpressModel::forUser($user->id);
		
		// Add default pages
		$pages	= new Pages(array(Stuffpress_Db_Table::USER => $user->id));
		//$pages->addPage('dashboard', 'Home');
		$pages->addPage('lifestream', 'Stream');
		$pages->addPage('stories', 'Stories');		
		
		// Send the user a verification email
		Stuffpress_Emails::sendWelcomeEmail($email, $username, $password, $user->token);
		
		// Done !
		$this->view->username = $username;
		$this->view->email 	  = $email;
		$this->render('success');
	}

	public function validateAction() {
		$key = $this->_getParam('key');
		$users = new Users();
		$user	= $users->getUserFromKey($key);
		$result = $users->verifyUser($key);
		if ($result>0) {
			$this->render('validated');		
		}
		else {
			$this->render('wrongkey');
		}
	}

	public function getForm() {
		$form = new Stuffpress_Form();
		$domain = $this->_config->web->host;
   
		// Add the form element details
		$form->setAction('admin/register/signup');
		$form->setMethod('post');
		$form->setName('formRegister');
		
		// Create and configure email element:
		$email = $form->createElement('text', 'email',  array('label' => 'Email:', 'decorators' => $form->elementDecorators));
		$email->addValidator(new Zend_Validate_EmailAddress());
		$email->setRequired(true);

		// Create and configure username element:
		$username = $form->createElement('text', 'username',  array('label' => 'Username:', 'onkeyup' => 'updateName(this.value);', 'decorators' => $form->elementDecorators));
        $username->addFilter('StringToLower');
		$username->addValidator('alnum');
		$username->addValidator('regex', false, array('/^[a-z0-9]+$/'));
        $username->addValidator('stringLength', false, array(4, 20));
        $username->addValidator(new Stuffpress_Validate_AvailableUsername(Zend_Db_Table::getDefaultAdapter(), 'users', 'username'));
        $username->setRequired(true);
        $username->setDescription("Minimum of 4 characters.<br/> This will be your link: <strong>http://<span id='user_link'>username</span>.$domain</strong>");
		
		// Create and configure password element:
		$password = $form->createElement('password', 'password', array('label' => 'Password:', 'decorators' => $form->elementDecorators));
		$password->addValidator('StringLength', false, array(6, 20));
		$password->setDescription("Minimum of 6 characters.");
		$password->setRequired(true);


		// Add elements to form:
		$form->addElement($email);
		$form->addElement($username);
		$form->addElement($password);
		$form->addElement('submit', 'register', array('label' => 'Sign up', 'decorators' => $form->buttonDecorators));

		return $form;
	}  
}