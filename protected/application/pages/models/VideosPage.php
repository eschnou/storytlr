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

class VideosPage extends Stuffpress_PageModel {

	protected $_prefix	= 'videos';
	
	protected $_name 	= 'Videos';
	
	protected $_description = 'Page with a thumbnail overview for video sources only with slideshow feature.';
	
	public function getDefaultValues() {
		$values = array();
		$values['title'] = 'Videos';
		return $values;
	}
}