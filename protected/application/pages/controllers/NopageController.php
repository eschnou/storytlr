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

class Pages_NopageController extends Pages_BaseController 
{	
	protected $_prefix='nopage';
	
	public function indexAction() 	{
		// To do before anything else
		$this->initPage();

		// Prepare the common elements
		$this->common();
	}
	
	protected function initPage() {
		// Prepare the view
		$this->_helper->layout->setLayoutPath($this->_root . '/application/public/views/layouts/')
							  ->setlayout('default');

		$this->view->page_class = $this->_prefix;
	}
}