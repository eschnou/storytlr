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

class Admin_PageController extends Zend_Controller_Action
{
    protected $_application;

    public function init()
    {
        $this->_application = Stuffpress_Application::getInstance();
    }
		
	public function viewAction() {
		$page = $this->_getParam('page');
		
		if (!in_array($page, array('about', 'cname', 'contact', 'css', 'faq', 'getstarted', 'privacy', 'tools', 'tos', 'presskit'))) {
			throw new Stuffpress_NotFoundException("Page $page does not exist");
		}
		
		$this->view->section = $page;
		$this->render($page);
	}
}