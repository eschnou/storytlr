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
class StuffpressModel extends SourceModel {

	protected $_name 	= 'stuffpress_data';

	protected $_prefix = 'stuffpress';
	
	protected $_search  = 'title, text';
	
	public static function forUser($id) {
		$properties = new Properties(array(Properties::KEY => $id));
		$sources	= new Sources(array(Stuffpress_Db_Table::USER => $id));
		$source_id	= $properties->getProperty('stuffpress_source');
		
		if (!$source_id) {
			$source_id = $sources->addSource('stuffpress');
			$sources->setImported($source_id, 1);	
			$properties->setProperty('stuffpress_source', $source_id);
		}
		
		$source 	= $sources->getSource($source_id);
		return new StuffpressModel($source);
	}

	public function getServiceName() {
		return "Storytlr";
	}

	public function getServiceURL() {
		return "";
	}

	public function getServiceDescription() {
		return "Directly posted on Storytlr.";
	}

	public function getAccountName() {
		return Stuffpress_Application::getInstance()->user->username;
	}
	
	public function isStoryElement() {
		return true;
	}
	
	public function setTitle($id, $title) {
		$this->updateItem($id, array('title' => $title));
	}

	public function importData() {
		return 0;
	}

	public function updateData() {
		return 0;
	}
}
