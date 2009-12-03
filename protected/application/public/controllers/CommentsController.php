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
class CommentsController extends BaseController
{
	
	public function indexAction() {	
		// Get, check and setup the parameters
		$source_id 		= $this->getRequest()->getParam("source");
		$item_id		= $this->getRequest()->getParam("item");

		//Verify if the requested item exist
		$data			= new Data();
		if (!($item		= $data->getItem($source_id, $item_id))) {
			return;
		}

		// Get the source
		$sources = new Sources();
		$source  = $sources->getSource($source_id);
		
		// Are we the owner of these comments ?
		$owner = ($this->_application->user && ($source['user_id'] == $this->_application->user->id)) ? true : false;
		
		// Reset to the system timezone; just in case
		$config 			= Zend_Registry::get("configuration");
		$server_timezone 	= $config->web->timezone;
		date_default_timezone_set($server_timezone);

		// Get the comments
		$c			= new Comments();
		$comments	= $c->getComments($source_id, $item_id);

		foreach ($comments as &$comment) {
			$comment['when'] 	= strtotime($comment['timestamp']);
			$comment['comment'] = str_replace("\n", " <br />", $comment['comment']);
			$comment['delete'] 	= $owner;
		}
		
		// Set the timezone to the user timezone
		$timezone =  $this->_properties->getProperty('timezone');
		date_default_timezone_set($timezone);
		
		// Prepare the view
		$this->view->comments = $comments;
	}

	public function deleteAction() {
		// Get, check and setup the parameters
		$comment_id = $this->getRequest()->getParam("id");

		// Get the comment and source tables
		$comments	= new Comments();
		$sources	= new Sources();

		// Check if the comment exist
		if (!($comment = $comments->getComment($comment_id))) {
			return $this->_helper->json->sendJson(true);
		}

		// Check if the comment belongs to the source
		if (!($source		= $sources->getSource($comment->source_id))) {
			return $this->_helper->json->sendJson(true);
		}

		// Check if we are the owner of the source
		if (!($source['user_id'] == $this->_application->user->id)) {
			return $this->_helper->json->sendJson(true);
		}

		// All checks ok, we can delete !
		$comments->deleteComment($comment_id);
		return $this->_helper->json->sendJson(false);
	}

	public function addAction() {
		if (!$this->getRequest()->isPost()) {
			return $this->_helper->json->sendJson(true);
		}

		$form = $this->getForm();
		if (!$form->isValid($_POST)) {
			return $this->_helper->json->sendJson($form->getErrorArray());
		}

		// Get the values and proceed
		$values 	= $form->getValues();
		$source_id	= $values['source'];
		$item_id	= $values['item'];
		$name		= $values['name'];
		$email		= $values['email'];
		$website	= $values['website'];
		$comment	= $values['comment'];
		$options	= $values['options'];
		$timestamp	= time();
		$notify 	= @in_array('notify',$options) ? 1 : 0;
			
		// Get the source and the user owning it
		$data		= new Data();
		$sources    = new Sources();
		$users      = new Users();
			
		// Does the source exist ?
		if (!($source = $sources->getSource($source_id))) {
			return $this->_helper->json->sendJson(true);
		}
		
		// Does the item exists?
		if (!($item = $data->getItem($source_id, $item_id))) {
			return $this->_helper->json->sendJson(true);
		}
			
		// Does the user exist ?
		if (!($user = $users->getUser($source['user_id']))) {
			return $this->_helper->json->sendJson(true);
		}
		
		// Validate the website URL
		$matches = array();
		if (!preg_match_all("/^http/", $website, $matches)) {
			$website = "http://$website";
		}

		// Add the comment to the database
		$comments  	= new Comments();
		$comments->addComment($source_id, $item_id, $comment, $name, $email, $website, $timestamp, $notify);

		// Send an email alert to owner
		$on_comment		= $this->_properties->getProperty('on_comment');
		$owner			= ($this->_application->user && $this->_application->user->email == $email) ? true : false;
		if ($on_comment && !$owner) {
			$slug = $item->getSlug();
			Stuffpress_Emails::sendCommentEmail($user->email, $user->username, $name, $email, $comment, $slug);
		}
		
		// Send email alerts to everyone else (skip owner and current submiter)
		$subscribers 	= $comments->getSubscriptions($source_id, $item_id);
		foreach($subscribers as $subscriber) {
			if ($subscriber['email'] == $user->email || $subscriber['email'] == $email) continue;
			 Stuffpress_Emails::sendCommentNotifyEmail($subscriber['email'], $subscriber['name'], $name, $comment, $source_id, $item_id);
		}
		
		// Ok send the result
		return $this->_helper->json->sendJson(false);
	}
	
	public function formAction() {
		// Get, check and setup the parameters
		$source_id 		= $this->getRequest()->getParam("source");
		$item_id		= $this->getRequest()->getParam("item");

		//Verify if the requested item exist
		$data = new Data();
		if (!($item	= $data->getItem($source_id, $item_id))) {
			return;
		}
		
		// Are we logged in and the owner of the item ?
		$owner = ($this->_application->user && ($item->getUserid() == $this->_application->user->id)) ? true : false;

		// Get the form
		$this->view->source_id 	= $source_id;
		$this->view->item_id 	= $item_id;
		$this->view->form 		= $this->getForm($source_id, $item_id, $owner);
	}

	private function getForm($source_id=0, $item_id=0) {
		$form = new Stuffpress_Form();
			
		// Add the form element details
		$form->setMethod('post');
		$form->setName("form_add_comment_{$source_id}_{$item_id}");

		// Create and configure comment element:
		$comment = $form->createElement('textarea', 'comment',  array('label' => 'Comment:', 'rows'=> 4, 'cols' => 60, 'decorators' => $form->elementDecorators));
		$comment->setRequired(true);
		$comment->addFilter('StripTags');

		if ($this->_application->user) {
			$name = $form->createElement('hidden', 'name');
			$name->setValue($this->_application->user->username);
			$name->setDecorators(array(array('ViewHelper')));
					
			$email = $form->createElement('hidden', 'email');
			$email->setValue($this->_application->user->email);
			$email->setDecorators(array(array('ViewHelper')));
			
			$config = Zend_Registry::get('configuration');
			$host	= $config->web->host;
			$url	= $this->_application->getPublicDomain();
			
			$website = $form->createElement('hidden', 'website');
			$website->setValue($url);
			$website->setDecorators(array(array('ViewHelper')));
			$website->addFilter('StripTags');
		}
		else {
			// Create and configure username element:
			$name = $form->createElement('text', 'name',  array('label' => 'Name:', 'decorators' => $form->elementDecorators));
			$name->addFilter('StringToLower');
			$name->addValidator('alnum');
			$name->addValidator('stringLength', false, array(4, 20));
			$name->setRequired(true);
	
			// Create and configure email element:
			$email = $form->createElement('text', 'email',  array('label' => 'Email (confidential):', 'decorators' => $form->elementDecorators));
			$email->addValidator(new Zend_Validate_EmailAddress());
			$email->setRequired(true);
	
			// Create and configure website element:
			// TODO Add URL validator
			$website = $form->createElement('text', 'website',  array('label' => 'Website (optional):', 'decorators' => $form->elementDecorators));
			$website->addFilter('StripTags');
			$website->setRequired(false);
		}
		
		$options = new Zend_Form_Element_MultiCheckbox('options', array(
			'decorators' 	=> $form->elementDecorators,
			'multiOptions' 	=> array(
			'notify' 		=> 'Notify me of followup comments via e-mail'
			)
		)); 
		$options->setValue(array('notify'));

		// Add elements to form:
		$form->addElement($comment);
		$form->addElement($name);
		$form->addElement($email);
		$form->addElement($website);
		$form->addElement($options);
			
		// Add a hidden element with the source id
		$element = $form->createElement('hidden', 'source');
		$element->setValue($source_id);
		$element->setDecorators(array(array('ViewHelper')));
		$form->addElement($element);

		// Add a hidden element with the item id
		$element = $form->createElement('hidden', 'item');
		$element->setValue($item_id);
		$element->setDecorators(array(array('ViewHelper')));
		$form->addElement($element);

		// Post button
		$button = $form->createElement('button', 'post', array('label' => 'Post', 'onclick' => "submitFormAddComment($source_id, $item_id);", 'decorators' => array('ViewHelper')));
		$button->setDecorators(array(array('ViewHelper')));
		$form->addElement($button);
		
		// Cancel button
		$button = $form->createElement('button', 'cancel', array('label' => 'Cancel', 'onclick' => "cancelFormAddComment($source_id, $item_id);", 'decorators' => array('ViewHelper')));
		$button->setDecorators(array(array('ViewHelper')));
		$form->addElement($button);
		
		$form->addDisplayGroup(array('post', 'cancel'), 'buttons', array('decorators' => $form->groupDecorators));
			
		return $form;
	}
}