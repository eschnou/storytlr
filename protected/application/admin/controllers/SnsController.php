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

class Admin_SnsController extends Admin_BaseController 
{
	protected $_section = 'config';
	
	public function indexAction() {
		// Get the user properties
		$values 	= $this->_properties->getProperties(array("twitter_auth", "twitter_username", "twitter_services"));
		
		// If not logged in, get the login form
		if (!$values['twitter_auth']) {
			if (!$this->view->twitter_login_form) {
				$this->view->twitter_login_form = $this->getTwitterLoginForm();
			}
		} 
		// Else get the config form
		else {
			if (!$this->view->twitter_config_form) {
				$form = $this->getTwitterConfigForm();
				$form->twitter_services->setValue(unserialize($values['twitter_services']));
				$this->view->twitter_config_form = $form;
			}
		}
		
		// Prepare view
		$this->common();
		$this->view->twitter_auth 	 = $values['twitter_auth'];
		$this->view->twitter_user 	 = $values['twitter_username'];
		$this->view->status_messages = $this->getStatusMessages();
		$this->view->error_messages	 = $this->getErrorMessages();		
		$this->view->headScript()->appendFile('js/controllers/sns.js');
	}

	public function submitAction()
	{
		// Is the form correctly posted ?
		if (!$this->getRequest()->isPost()) {
			return $this->_helper->json->sendJson(true);
		}

		// Is the form valid ?
		$form = $this->getTwitterConfigForm();
		if (!$form->isValid($_POST)) {
			return $this->_helper->json->sendJson($form->getErrorArray());
		}
		
		// Get the values and proceed
		$values 	= $form->getValues();

		// Save the new values
		$this->_properties->setProperty('twitter_services', serialize($values['twitter_services']));
		
		// Ok
		return $this->_helper->json->sendJson(false);
	}
	
	public function loginAction()
	{
		// Is the form correctly posted ?
		if (!$this->getRequest()->isPost()) {
			$this->addErrorMessage("Not a valid form posted");
			return $this->_forward('index');
		}

		// Is the form valid ?
		$form = $this->getTwitterLoginForm();
		if (!$form->isValid($_POST)) {
			$this->addErrorMessage("Please check input and try again");
			$this->view->twitter_login_form = $form;
			return $this->_forward('index');
		}
		
		// Get the values and proceed
		$values 	= $form->getValues();
		
		// Proceed and save the values
		$t_user		= $values['username'];
		$t_pwd		= $values['password'];

		// If twitter password is changed, validate the account
		if (!$this->validateTwitterAccount($t_user, $t_pwd)) {
			$this->addErrorMessage("Could not log you into Twitter with these credentials");
			$this->view->twitter_login_form = $form;
			return $this->_forward('index');			
		}

		// Save the new values
		$this->_properties->setProperty('twitter_auth', 1);
		$this->_properties->setProperty('twitter_username', $t_user);
		$this->_properties->setProperty('twitter_password', $t_pwd);
		
		// Add the storytlr source by default
		$sources 	= array();
		$sources[] 	= $this->_properties->getProperty('stuffpress_source');
		$this->_properties->setProperty('twitter_services', serialize($sources));
		
		// Ok
		$this->addStatusMessage("Successfully logged into Twitter");
		$this->_forward('index');
	}
	
	public function logoutAction() {
		$this->_properties->setProperty('twitter_auth', false);	
		$this->_properties->setProperty('twitter_username', '');	
		$this->_properties->setProperty('twitter_password', '');	
		
		// Ok
		$this->addStatusMessage("You have been logged out from Twitter");
		$this->_forward('index');
	}
	
	private function getTwitterConfigForm() {
		$form = new Stuffpress_Form();
			
		// Add the form element details
		$form->setMethod('post');
		$form->setName('formTwitterConfig');
		
		// Sources
		$e = new Zend_Form_Element_MultiCheckbox('twitter_services', array(
			'decorators' => array('ViewHelper', 'Errors'),
			'multiOptions' => $this->getAvailableSources(),
			'class' => 'checkbox'
		));
		$form->addElement($e);

		// Save button
		$e = $form->createElement('button', 'save', array('label' => 'Save', 'onclick' => "submitFormTwitterConfig();", 'decorators' => $form->buttonDecorators));
		$form->addElement($e);

		return $form;
	}
	
	private function getTwitterLoginForm() {
		$form = new Stuffpress_Form();
			
		// Add the form element details
		$form->setMethod('post');
		$form->setName('formTwitterLogin');
		$form->setAction('admin/sns/login');
		
		// Twitter account
		$e = $form->createElement('text', 'username',  array('size' => 12, 'label' => 'Username', 'decorators' => array('ViewHelper', 'Errors')));
        $e->setRequired(true);
        $form->addElement($e);

        // Twitter account
		$e = $form->createElement('password', 'password',  array('size' => 12, 'label' => 'Password', 'decorators' => array('ViewHelper', 'Errors')));
        $e->setRequired(true);
        $form->addElement($e);

		// Save button
		$form->addElement('submit', 'login', array('label' => 'Sign in', 'decorators' => $form->buttonDecorators));

		return $form;
	}
	
	private function validateTwitterAccount($username, $password) {
		$url  = "http://twitter.com/account/verify_credentials.json";
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL,$url);  
		curl_setopt($curl, CURLOPT_HEADER, false);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_USERPWD, "$username:$password"); 
		curl_setopt($curl, CURLOPT_USERAGENT,'Storytlr/1.0');
	
		$response = curl_exec($curl);
		$http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		curl_close ($curl);

		if ($http_code != 200) {
			return false;
		} else {
			return true;
		}
	}
	
	private function getAvailableSources() {
		$sourcesTable 	= new Sources();
		$sources 		= $sourcesTable->getSources();
		$s 				= array();
		if ($sources) foreach ($sources as $source) {
			$model = SourceModel::newInstance($source['service'], $source);
			$account = $model->getAccountName() ? "(" . $model->getAccountName() . ")" : "";
			$s[$source['id']] = $model->getServiceName() . $account;		
		}
		
		return $s;
	}
}