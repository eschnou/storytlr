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

class Users extends Stuffpress_Db_Table
{

	protected $_name = 'users';

	protected $_primary = 'id';

	public function addUser($username, $password, $email) {
		$password 	= md5($password);
		$token		= Stuffpress_Token::create(32);
			
		$data 		= array("username" 	=> $username,
							"password"	=> $password,
							"email"		=> $email,
							"token"		=> $token);
			
		$this->insert($data);
		$id = $this->_db->lastInsertId();
		$user = $this->getUser($id);
		return $user;
	}

	public function getUser($id) {
		$rowset = $this->find($id);
		if (count($rowset)>0) {
			$result = $rowset->current();
		}
		else {
			$result = false;
		}
		return $result;
	}
	
	public function getAllUsers() {
		$sql = "SELECT * FROM `users`";			
		$stmt = $this->_db->query($sql);
		$result   = $stmt->fetchAll();
		return $result;		
	}
	
	public function getNextUpdate($lastseen) {
		$sql = "SELECT * FROM `users` WHERE last_login > FROM_UNIXTIME(:last_seen) ORDER BY update_start LIMIT 1";			
		$data = array(':last_seen' => $lastseen);
		$stmt = $this->_db->query($sql, $data);
		$result   = $stmt->fetchObject();
		return $result;		
	}

	public function getUserFromUsername($username) {
		$result  = $this->fetchRow($this->select()->where('username = ?', $username));
		return $result;
	}

	public function getUserFromDomain($domain) {
		$result = $this->fetchRow($this->select()->where('domain = ?', $domain));
		return $result;
	}

	public function getUserFromEmail($email) {
		$result = $this->fetchRow($this->select()->where('email = ?', $email));
		return $result;
	}

	public function getUserFromKey($key) {
		$result = $this->fetchRow($this->select()->where('token = ?', $key));
		return $result;
	}

	public function verifyUser($token) {
		$user   = $this->getUserFromKey($token);
		$where  = $this->getAdapter()->quoteInto('token = ?', $token);
		$result = $this->update(array('verified' => 1), $where);
		return $result;
	}

	public function deleteUser($id) {
		$where  = $this->getAdapter()->quoteInto('id = ?', $id);
		$result = $this->delete($where);
		return $result;
	}

	public function setPassword($id, $password) {
		$where  = $this->getAdapter()->quoteInto('id = ?', $id);
		$result = $this->update(array('password' => md5($password)), $where);
		return $result;
	}

	public function setDomain($id, $domain) {
		$where  = $this->getAdapter()->quoteInto('id = ?', $id);
		$result = $this->update(array('domain' => $domain), $where);
		return $result;
	}
	
	public function setSecret($id, $secret) {
		$where  = $this->getAdapter()->quoteInto('id = ?', $id);
		$result = $this->update(array('email_secret' => $secret), $where);
		return $result;
	}

	public function hitPage($id) {
		$sql = "UPDATE `users` SET last_seen = CURRENT_TIMESTAMP, hits = hits + 1 WHERE `id`=:user_id";
		$data = array(":user_id" 	=> $id);
		$stmt 	= $this->_db->query($sql, $data);
	}

	public function hitLogin($id) {
		$sql = "UPDATE `users` SET last_login = CURRENT_TIMESTAMP WHERE `id`=:user_id";
		$data = array(":user_id" 	=> $id);
		$stmt 	= $this->_db->query($sql, $data);
	}
	
	public function startUpdate($id) {
		$sql = "UPDATE `users` SET update_start = CURRENT_TIMESTAMP WHERE `id`=:user_id";
		$data = array(":user_id" 	=> $id);
		$stmt 	= $this->_db->query($sql, $data);
	}
	
	public function endUpdate($id) {
		$sql = "UPDATE `users` SET update_end = CURRENT_TIMESTAMP WHERE `id`=:user_id";
		$data = array(":user_id" 	=> $id);
		$stmt 	= $this->_db->query($sql, $data);
	}
	
	// Statistics functions
	public function getUserCount() {
		$sql = "SELECT count(*) FROM users";

		$stmt 	= $this->_db->query($sql);
		$count  = $stmt->fetchColumn(0);
		return  $count;
	}
	
	public function getLoginCount($since=0) {
		$sql = "SELECT count(*) FROM users WHERE last_login > FROM_UNIXTIME(:since)";

		$data = array(':since' => $since);
		
		$stmt 	= $this->_db->query($sql, $data);
		$count  = $stmt->fetchColumn(0);
		return  $count;
	}
}