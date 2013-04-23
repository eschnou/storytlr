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
class FacebookModel extends SourceModel { 
	
	protected $_name 	= 'facebook_data';

	protected $_prefix = 'facebook';

	protected $_search  = 'title, subject, description';

	public function getServiceName() {
		return "Facebook";
	}
	
	public function isActive() {
		return false;
	}

	public function isStoryElement() {
		return false;
	}
	
	public function getServiceURL() {
		return "http://facebook.com";;
	}

	public function getServiceDescription() {
		return "Facebook is a social network.";
	}

	public function importData() {
		return false;
	}

	public function updateData($import=false) {
		return false;
	}
}
