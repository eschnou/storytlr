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
class Widgets_LastcommentsController extends Stuffpress_Controller_Widget
{	
	protected $_prefix = 'lastcomments';
		
	public function indexAction() 	{
		// Get, check and setup the parameters
		if (!($widget_id = $this->getRequest()->getParam("id"))) {
			throw new Stuffpress_Exception("No widget id provided to the widget controller"); 
		}
		
		// Verify if the requested widget exist and get its data
		$widgets = new Widgets();
		if (!$widget  = $widgets->getWidget($widget_id)) {
			throw new Stuffpress_Exception("Invalid widget id");
		}	

		// Get the last comments
		$comments = new Comments(array(Stuffpress_Db_Table::USER => $widget['user_id']));
		$mycomments = $comments->getLastComments();
		
		$data 	= new Data();
		
		// Prepare the comments for output
		foreach ($mycomments as &$comment) {
			$time = strtotime($comment['timestamp']);
			$item = $data->getItem($comment['source_id'], $comment['item_id']);
			$comment['item'] = $item;
			$comment['when'] = Stuffpress_Date::ago($time, "j M y");
			$comment['comment'] = str_replace("\n", " ", $comment['comment']);
			if (strlen($comment['comment']) > 50) {
				$comment['comment'] = mb_substr($comment['comment'], 0, 47) . "...";
			}
		}
		
		// Get the widget properties
		$properties	= new WidgetsProperties(array(Properties::KEY => $widget_id));
		$title 		= $properties->getProperty('title');
		$this->view->title = $title ? $title : "Latest comments";
		
		// Prepare the view for rendering
		$this->view->comments = $mycomments;
	}
}