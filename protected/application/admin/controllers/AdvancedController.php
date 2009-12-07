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
class Admin_AdvancedController extends Admin_BaseController 
{
	protected $_section = 'advanced';
    
	public function indexAction() {		
		// Get the required properties
		$values 	= $this->_properties->getProperties(array(	"disqus"));

		// User CNAME setting
		$values['cname'] = $this->_application->user->domain;
		
		// Get the form and assign the values
		$form = $this->getForm();
		$form->populate($values);

		// Add common view elements
		$this->common();
		
		// Add the specific view elements
		$this->view->headScript()->appendFile('js/controllers/advanced.js');
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
		
		// If we have a cname.. is it valid ?
		if ($values['cname']) {
			$user = $users->getUserFromDomain($values['cname']);
			if ($user && $user->id != $this->_application->user->id) {
				return $this->_helper->json->sendJson(array("Own domain: Domain {$values['cname']} already claimed by another user"));
			}
		}
		
		// Proceed and save the values
		$cname		= $values['cname'];
		$disqus		= $values['disqus'];
		$googlefc   = $values['friendconnect'];
		
		// Save the new values
		$this->_properties->setProperty('disqus', 	$disqus);
		$this->_properties->setProperty('googlefc', $googlefc);				
		$users->setDomain($this->_application->user->id, $cname);

		// Ok
		return $this->_helper->json->sendJson(false);
	}

	private function getForm() {
		$form = new Stuffpress_Form();
			
		// Add the form element details
		$form->setMethod('post');
		$form->setName('formAdvanced');
		
		// CNAME
		$e = $form->createElement('text', 'cname',  array('size' => 37, 'label' => 'Own domain', 'decorators' => $form->elementDecorators));
        $e->setRequired(false);
        $e->addFilter('StringToLower');
        $e->setDescription("Fill-in your domain name (e.g. www.johndoe.com) and add a CNAME entry towards this domain (<a href='http://code.google.com/p/storytlr/wiki/CName'>help</a>)");
        $form->addElement($e);

        // Disqus
        $username = $this->_application->user->username;
        $host	  = $this->getHostname();
        $url 	  = "http://$username.$host";
		$e = $form->createElement('text', 'disqus',  array('size' => 37, 'label' => 'Disqus commenting', 'decorators' => $form->elementDecorators));
        $e->setRequired(false);
        $e->addFilter('StringToLower');
        $e->setDescription("Create a disqus web site for the url $url and fill-in its short-name (<a href='http://code.google.com/p/storytlr/wiki/FAQ'>help</a>)");
        $form->addElement($e);

        
        // Friend connect
		$e = $form->createElement('text', 'friendconnect',  array('size' => 37, 'label' => 'Friend connect key', 'decorators' => $form->elementDecorators));
        $e->setRequired(false);
        $e->setDescription("Add Google Friend Connect to your site and make it social");
        $form->addElement($e);
    
        
        // Save button
		$e = $form->createElement('button', 'save', array('label' => 'Save', 'onclick' => "submitFormAdvanced();", 'decorators' => $form->buttonDecorators));
		$form->addElement($e);

		return $form;
	}
}