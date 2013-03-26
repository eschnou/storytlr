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
class RepliesController extends BaseController
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
		
		// Are we the owner of these mentions ?
		$owner = ($this->_application->user && ($source['user_id'] == $this->_application->user->id)) ? true : false;
		
		// Reset to the system timezone; just in case
		$config 			= Zend_Registry::get("configuration");
		$server_timezone 	= $config->web->timezone;
		date_default_timezone_set($server_timezone);

		// Get the mentions
		$m			= new Mentions();
		$mentions	= $m->getMentions($source_id, $item_id);

		foreach ($mentions as &$mention) {
			$mention['when'] 	= strtotime($mention['timestamp']);
			$mention['delete'] 	= $owner;
		}
		
		// Set the timezone to the user timezone
		$timezone =  $this->_properties->getProperty('timezone');
		date_default_timezone_set($timezone);
		
		// Prepare the view
		$this->view->mentions = $mentions;
	}

	public function deleteAction() {
		// Get, check and setup the parameters
		$mention_id = $this->getRequest()->getParam("id");

		// Get the mention and source tables
		$mentions	= new Mentions();

		// Check if the mention exist
		if (!($mention = $mentions->getMention($mention_id))) {
			return $this->_helper->json->sendJson(true);
		}

		// Check if we are the owner of the mention
		if (!($mention['user_id'] == $this->_application->user->id)) {
			return $this->_helper->json->sendJson(true);
		}

		// All checks ok, we can delete !
		$mentions->deleteMention($mention_id);
		return $this->_helper->json->sendJson(false);
	}
}