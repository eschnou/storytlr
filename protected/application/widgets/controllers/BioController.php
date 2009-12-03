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
class Widgets_BioController extends Stuffpress_Controller_Widget
{
	protected $_prefix = 'bio';
	
	public function indexAction() {		
		// Get, check and setup the parameters
		if (!($widget_id = $this->getRequest()->getParam("id"))) {
			throw new Stuffpress_Exception("No widget id provided to the widget controller"); 
		}
		
		// Verify if the requested widget exist and get its data
		$widgets = new Widgets();
		if (!$widget  = $widgets->getWidget($widget_id)) {
			throw new Stuffpress_Exception("Invalid widget id");
		}		
		
		// Get the user
		$users 	= new Users();
		$user 	= $users->getUser($widget['user_id']);
		
		// Get all sources configured for that user
		$properties = new Properties(array(Properties::KEY => $user->id));

		// Get the bio data
		// User profile
		$this->view->username			= $user->username;
		$this->view->first_name			= $properties->getProperty('first_name');
		$this->view->last_name			= $properties->getProperty('last_name');
		$this->view->bio				= $properties->getProperty('bio');
		$this->view->location			= $properties->getProperty('location');
		$this->view->avatar				= $properties->getProperty('avatar_image');
		
		// Get the widget properties
		$properties	= new WidgetsProperties(array(Properties::KEY => $widget_id));
		$title 		= $properties->getProperty('title');
		$this->view->title = $title ? $title : "About {$user->username}";
	}
	
}