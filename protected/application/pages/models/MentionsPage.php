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

class MentionsPage extends Stuffpress_PageModel {

	protected $_prefix	= 'mentions';
	
	protected $_name 	= 'Mentions';
	
	protected $_description = 'Page with a list of mentions you have received from accross the web.';
	
	public function getDefaultValues() {
		$values = array();
		$values['title'] = 'Mentions';
		return $values;
	}
}