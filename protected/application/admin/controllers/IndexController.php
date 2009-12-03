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

class Admin_IndexController extends Stuffpress_Controller_Action 
{
	public function indexAction()
	{
		$application = Stuffpress_Application::getInstance();
		
		// If we are not logged in, we have a problem
		if ($application->role != 'guest') {
			
			$sources 	= new Sources(array(Stuffpress_Db_Table::USER => $application->user->id));
			$mysources 	= $sources->getSources();
			
			if (count($mysources)>1) {
				return $this->_forward('index', 'post', 'admin');	
			} else {
				return $this->_forward('index', 'services', 'admin');
			}
			
		}
		else {
			return $this->_forward('index', 'auth', 'admin');
		}
	}
}