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

		// If not logged in, get the login form
		if (!$this->_properties->getProperty('twitter_auth',false)) {
			if (!$this->view->twitter_login_form) {
				$this->view->twitter_login = true;
			}
		} 
		// Else get the config form
		else {
			if (!$this->view->twitter_config_form) {
				$form = $this->getTwitterConfigForm();
				$form->twitter_services->setValue($this->_properties->getProperty('twitter_services'));
				$this->view->twitter_config_form = $form;
			}
			
			$this->view->twitter_username = $this->_properties->getProperty('twitter_username');
		}
		
		// Prepare view
		$this->common();
		$this->view->twitter_auth = $this->_properties->getProperty('twitter_auth', false);
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
	
	public function connectAction() {
		
		if (! isset($this->_config->twitter->consumer_key) && !isset($this->_config->twitter->consumer_secret)) {
			$this->addErrorMessage("Missing OAuth consumer key and secret, these should be added to the Storytlr config.ini file.
			More details <a href='http://github.com/storytlr/core/wiki/How-to-integrate-with-twitter'>here</a>.");
			$this->_forward('index');
			return;
		} 
		
		$consumer_key = $this->_config->twitter->consumer_key;
		$consumer_secret = $this->_config->twitter->consumer_secret;
		$oauth_callback = $this->getStaticUrl() . "/admin/sns/callback";
		
		/* Create a new twitter client */
		$connection = new TwitterOAuth_Client($consumer_key, $consumer_secret);
 
		/* Get temporary credentials. */
		$request_token = $connection->getRequestToken($oauth_callback);
		
		/* Save temporary credentials to session. */
		$oauth_token = $request_token['oauth_token'];
		$oauth_token_secret = $request_token['oauth_token_secret'];
		$this->_properties->setProperty("twitter_oauth_token", $oauth_token);
		$this->_properties->setProperty("twitter_oauth_token_secret", $oauth_token_secret); 
		
		/* If last connection failed don't display authorization link. */
		switch ($connection->http_code) {
		  case 200:
		    /* Build authorize URL and redirect user to Twitter. */
		    $this->_redirect($connection->getAuthorizeURL($oauth_token));
		    break;
		  default:
		    /* Show notification if something went wrong. */
		    $this->addErrorMessage('Could not connect to Twitter. Refresh the page or try again later.');
		}	
		
		$this->_forward('index');
	}
	
	public function callbackAction() {
		/* Get the saved tokens */
		$oauth_token = $this->_properties->getProperty('twitter_oauth_token');
		$oauth_token_secret = $this->_properties->getProperty('twitter_oauth_token_secret');
		
		if (!isset($oauth_token) && !isset($oauth_token_secret)) {
			$this->addErrorMessage("Missing temporary OAuth tokens");
			$this->_forward('index');
			return;			
		}
		
		/* Get the consumer key and secret from the config */
		if (! isset($this->_config->twitter->consumer_key) && !isset($this->_config->twitter->consumer_secret)) {
			$this->addErrorMessage("Missing OAuth consumer key and secret");
			$this->_forward('index');
			return;
		} 
		
		$consumer_key = $this->_config->twitter->consumer_key;
		$consumer_secret = $this->_config->twitter->consumer_secret;
		$oauth_callback = $this->getStaticUrl() . "/admin/sns/callback";
		
		/* If the oauth_token is old redirect to the connect page. */
		if (isset($_REQUEST['oauth_token'])) {
			if ($oauth_token != $_REQUEST['oauth_token']) {
				$this->_properties->deleteProperty("twitter_auth");
				die("Session should be cleared");
			}
		}
		
		/* Create TwitteroAuth object with app key/secret and token key/secret from default phase */
		$connection = new TwitterOAuth_Client($consumer_key, $consumer_secret, $oauth_token, $oauth_token_secret);
		
		/* Request access tokens from twitter */
		$access_token = $connection->getAccessToken($_REQUEST['oauth_verifier']);
		
		/* Save the access tokens. Normally these would be saved in a database for future use. */
		$this->_properties->setProperty('twitter_oauth_token', $access_token['oauth_token']);
		$this->_properties->setProperty('twitter_oauth_token_secret', $access_token['oauth_token_secret']);
		$this->_properties->setProperty('twitter_user_id', $access_token['user_id']);
		$this->_properties->setProperty('twitter_username', $access_token['screen_name']);
		
		/* If HTTP response is 200 continue otherwise send to connect page to retry */
		if (200 == $connection->http_code) {
		  /* The user has been verified and the access tokens can be saved for future use */
		  $this->_properties->setProperty('twitter_auth', true);
		} else {
		  /* Save HTTP status for error dialog on connnect page.*/
		  die("Error, We should clear the session.");
		}
		
		$this->_forward('index');
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