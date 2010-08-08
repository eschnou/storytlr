<?php
/*
 *  Copyright 2008-2009 Laurent Eschenauer and Alard Weisscher
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

class ShortUrl extends Stuffpress_Db_Table
{

	protected $_name = 'shortUrl';

	protected $_primary = 'token';

	public function getUrls($user_id) {
		$sql = "SELECT * FROM `shortUrl` WHERE user_id = :user_id";			
		$data = array(':user_id' => $user_id);
		$stmt = $this->_db->query($sql, $data);
		$result   = $stmt->fetchAll();
		return $result;
	}
	

	public function getUrl($token) {
		$rowset = $this->find($token);
		if (count($rowset)>0) {
			$result = $rowset->current();
		}
		else {
			$result = false;
		}
		return $result;
	}
	
	public function addUrl($token, $url, $internal=true) {
		
		$sql = "INSERT IGNORE INTO `shortUrl` (user_id, token, url, internal) VALUES (:user_id, :token, :url, :internal)";
		
		$data 		= array(":user_id" 	=> $this->_user,
							":token"	=> $token,
							":url"		=> $url,
							":internal"	=> $internal);
			
		$statement = $this->_db->query($sql, $data);
		
		return true;
	}

	public function deleteUrl($token) {
		$where		= array();
		$where[] 	= $this->getAdapter()->quoteInto('token = ?', $token);
		$this->delete($where);
	}

	public function deleteUser() {
		$where = $this->getAdapter()->quoteInto('user_id = ?', $this->_user);
		$this->delete($where);
	}
	
	public function shorten($url, $internal=true) {
		// Find a suitable token
		do {
			$token = Stuffpress_Token::create(4);
		} while($this->getUrl($token));
		
		// Save the url
		$this->addUrl($token, $url, $internal);

		// Return the token
		return $token;
	}
	
	public function expand($token) {
		if (!$url = $this->getUrl($token)) {
			return false;
		}
		
		if ($url['internal']) {
			$users 	= new Users();
			if (!$user 	= $users->getUser($url['user_id'])) {
				return false;
			}
			
			$domain = Stuffpress_Application::getDomain($user);
			return "http://" . $domain . "/entry/" . $url['url'];
		} else {
			return $url['url'];
		}
	}
}

