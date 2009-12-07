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

class Admin_WidgetsController extends Admin_BaseController
{
    protected $_section = 'config';
	
	public function indexAction() {
		$w = new Widgets();
		$this->view->widgets = $w->getAvailableWidgets();
		$this->view->user_widgets = $w->getWidgets($this->_application->user->id);

		// Prepare view
		$this->common();
		$this->view->headScript()->appendFile('js/storytlr/effects.js');
		$this->view->headScript()->appendFile('js/controllers/widgets.js');	
	}
	
	public function addAction() {
		// Get, check and setup the parameters
		$prefix = $this->getRequest()->getParam("widget");
		
		// Is the widget available
		$widgets 	= new Widgets();
		$available 	= $widgets->getAvailableWidgets();
		if (!isset($available[$prefix])) {
			return;
		}
		
		// Add the widget
		$widget_id  = $widgets->addWidget($prefix);
		$widget 	= $widgets->getWidget($widget_id);
		
		// Find the widget position
		$total	= count($widgets->getWidgets());

		// Set the position
		$widgets->setPosition($widget_id, $total);
		
		// Return the new widget 
		$this->view->widget	= $widget;
	}
	
	public function orderAction() {
		// Get, check and setup the parameters
		$order = $this->getRequest()->getParam("user_widgets");
		
		// Assign the new positions
		$widgets = new Widgets();
		for($i=0; $i<count($order); $i++) {
			$widgets->setPosition($order[$i], $i);
		}
		
		return $this->_helper->json->sendJson(false);
	}
	
	public function deleteAction() {
		// Get, check and setup the parameters
		$widget_id	= $this->getRequest()->getParam("id");

		// Do we own the widget ?
		$widgets = new Widgets();
		
		if (!$widget = $widgets->getWidget($widget_id)) {
			throw new Stuffpress_Exception("Unknown widget with id $widget_id");
		}
		
		if ($widget['user_id'] != $this->_application->user->id) {
			throw new Stuffpress_AccessDeniedException("Not the owner of widget $widget_id");
		}
		
		$widgets->deleteWidget($widget_id);
		
		// Delete the widget properties
		$properties = new WidgetsProperties(array(Properties::KEY => $widget_id));
		$properties->deleteAllProperties();
		
		return $this->_helper->json->sendJson(false);
	}
}