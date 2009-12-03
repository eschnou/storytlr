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

class ErrorController extends Zend_Controller_Action
{
	public function init() {
		$this->_helper->layout->setlayout('error');
	}

	public function errorAction()
	{
		$errors = $this->_getParam('error_handler');

		// Is this a Zend not found exception ?
		switch ($errors->type) {
			case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_CONTROLLER:
				return $this->_forward('notfound', null, null, array('message' => 'No such controller'));
				break;
			case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_ACTION:
				return $this->_forward('notfound', null, null, array('message' => 'No such action'));
				break;
		}

		// Application error
		$exception 				= $errors->exception;
		
		// Is this a stuffpress not found exception ?
		if (get_class($exception) == 'Stuffpress_NotFoundException') {
			return $this->_forward('notfound', null, null, array('message' => $exception->getMessage()));
		}
		
		// Is this a stuffpress access denied exception ?
		if (get_class($exception) == 'Stuffpress_AccessDeniedException') {
			return $this->_forward('denied', null, null, array('message' => $exception->getMessage()));
		}

		// Log the error
		$root	= Zend_Registry::get("root");
		$uri	= Zend_Registry::isRegistered("uri") ? Zend_Registry::get("uri") : '';
		$host 	= Zend_Registry::isRegistered("host") ? Zend_Registry::get("host") : '';
		$log 	= new Zend_Log(new Zend_Log_Writer_Stream($root.'/logs/applicationException.log'));
		$log->debug("Exception for request http://$host/$uri\n" . $exception->getMessage() . "\n" .  $exception->getTraceAsString() . "\n----------------------------\n\n");

		// Are we in debug mode ?
		$config = Zend_Registry::get("configuration");
		$debug	= $config->debug;

		// Don't forget to clear the response body, just in case
		$this->getResponse()->clearBody();

		if($debug) {
			$this->view->message 	= $exception->getMessage();
			$this->view->trace 		= $exception->getTraceAsString();
			return $this->render('debug');
		}
	}

	public function notfoundAction() {
		// 404 error -- controller or action not found
		$this->getResponse()->setRawHeader('HTTP/1.1 404 Not Found');

		// Don't forget to clear the response body, just in case
		$this->getResponse()->clearBody();
		
		// Prepare the view 
		$config = Zend_Registry::get("configuration");
		if ($config->debug)	{
			$this->view->message 	= $this->_getParam('message');
		}
		
		if (Zend_Registry::isRegistered("user")) {
			$this->view->user		= Zend_Registry::get('user')->username; 
		}
	}

	public function deniedAction() {
		// 404 error -- controller or action not found
		$this->getResponse()->setRawHeader('HTTP/1.1 403 Forbidden');
		
		// Don't forget to clear the response body, just in case
		$this->getResponse()->clearBody();
		
		// Prepare the view 
		$config = Zend_Registry::get("configuration");
		if ($config->debug)	{
			$this->view->message 	= $this->_getParam('message');
		}
	}
}

