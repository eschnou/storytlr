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

abstract class Admin_BaseController extends Stuffpress_Controller_Action 
{	    
    protected $_application;
    
    protected $_properties;
       
    protected $_cache;
    
    protected $_config;
    
    protected $_section;
    
    protected $_root;
    
    protected $_ajax;

    public function init()
    {
		
		if (Zend_Registry::isRegistered("cache")) $this->_cache = Zend_Registry::get("cache");
		
		if (Zend_Registry::isRegistered("configuration")) $this->_config = Zend_Registry::get("configuration");
		
        $this->_application = Stuffpress_Application::getInstance();
        
        if (!Zend_Registry::isRegistered('shard')) {
			Zend_Registry::set("shard", $this->_application->user->id);
		}
		
		if (Zend_Registry::isRegistered("root"))  $this->_root = Zend_Registry::get("root");	
        
		$this->_properties 	= new Properties(array(Stuffpress_Db_Properties::KEY => $this->_application->user->id));

    	// Disable layout if XMLHTTPRequest
        if ($this->_request->isXmlHttpRequest()) {
			$this->_helper->layout->disableLayout();	
			$this->_ajax = true;
		}
    }

	protected function common() { 
		$this->view->section = $this->_section;
		$this->view->headScript()->appendFile('js/prototype/prototype.js');
		$this->view->headScript()->appendFile('js/scriptaculous/scriptaculous.js');
		$this->view->headScript()->appendFile('js/storytlr/validateForm.js');
	}
}
