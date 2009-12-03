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
class Widgets_LinksController extends Stuffpress_Controller_Widget
{
	protected $_prefix = 'links';
		
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
		
		// Get all sources configured for that user
		$sources = new Sources(array(Stuffpress_Db_Table::USER => $widget['user_id']));
		$mysources = $sources->getSources();
		
		// If sources are configured, get the links for each source
		$links = array();
		if ($mysources) foreach ($mysources as $source) {
			if (!($source['public'] && $source['enabled'])) continue;
			if ($source['service'] == 'stuffpress') continue;
			$model = SourceModel::newInstance($source['service']);
			$model->setSource($source);
			$link['prefix'] = $model->getServicePrefix();
			$link['url'] = $model->getServiceURL();
			$link['name'] = $model->getTitle();
			$links[] = $link;
		}
		
		// Get the widget properties
		$properties	= new WidgetsProperties(array(Properties::KEY => $widget_id));
		$title 		= $properties->getProperty('title');
		$this->view->title = $title ? $title : "My 2.0 Life";
		
		// Prepare the view for rendering
		$this->view->links	  = $links;
	}
	
}