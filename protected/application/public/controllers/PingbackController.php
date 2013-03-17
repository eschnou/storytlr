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

use mf2\Parser;

class PingbackController extends Stuffpress_Controller_Action 
{
	protected $_application;
	
	protected $_logger;
	
    public function preDispatch()
    {
		$this->_application = Stuffpress_Application::getInstance();
		$this->_logger = Zend_Registry::get("logger");
    }
	
	public function pingAction() {
		// We encapsulate xmlrpc, disable everything
		$this->_helper->layout->disableLayout();
		$this->_helper->viewRenderer->setNoRender();
		
		// Get the request
		$request = Pingback_Utility::getRawPostData();
		$this->_logger->log("Received pingback request: $request", Zend_Log::DEBUG);
		
		// Process the request
		$server = new Pingback_Server();
		$server->execute($request);
		
		if ($server->isValid()) {
			$source = $server->getSourceURL();
			$target = $server->getTargetURL();
			$this->process($source,$target);
		}
				
		// Log the response
		$response = $server->getResponse();
		$this->_logger->log("Response: $response", Zend_Log::DEBUG);
		
		// We turn off output buffering to avoid memory issues
	    // when dumping the response 
		ob_end_flush();
		print $response;
		
		// Die to make sure that we don't screw up the response
		die();
	}
	
	private function process($source, $target) {
		$this->_logger->log("Processing pingback from $source for $target", Zend_Log::INFO);
		
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $source);
		curl_setopt($curl, CURLOPT_HEADER, false);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_USERAGENT,'Storytlr/1.0');
		
		$response = curl_exec($curl);
		$http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		curl_close ($curl);
		
		if ($http_code != 200) {
			$this->_logger->log("Failed to get content for $target", Zend_Log::DEBUG);
			return;
		}
		
		$parser = new Parser($response);
		$output = $parser->parse();
		$this->_logger->log("Parsed output: " . var_export($output), Zend_Log::DEBUG);
	}
	
}