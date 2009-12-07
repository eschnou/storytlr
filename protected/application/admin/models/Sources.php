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

class Sources extends Stuffpress_Db_Table
{
	protected $_name = 'sources';

	protected $_primary = 'id';

	public static function getAvailableSources() {
		if (Zend_Registry::isRegistered("cache")) {
			$cache = Zend_Registry::get("cache");
		} else {
			$cache = false;
		}

		if ($cache && ($result = $cache->load('Sources_getAvailableSources'))) {
			return $result;
		}

		$root = Zend_Registry::get("root");
		$dir = $root . "/application/plugins/";
		$plugins = array();
		if (is_dir($dir)) {
			if ($dh = opendir($dir)) {
				while (($file = readdir($dh)) !== false) {
					if ($file != "." && $file != ".." && $file != "CVS" && $file != "SVN" && $file != '.svn') {
						$plugins[] = basename($file);
					}
				}
				closedir($dh);
			}
		}

		sort($plugins);

		if ($cache) {
			$cache->save($plugins, 'Sources_getAvailableSources');
		}

		return $plugins;
	}

	public function getSource($id) {
		$rowset = $this->find($id);
		$result = $rowset->current();
		return $result->toArray();
	}

	public function getSources() {
		$rowset = $this->fetchAll($this->select()->where('user_id = ?', $this->_user));
		$result = $this->rowsetToArray($rowset, 'id');
		return $result;
	}

	public function getAllSources($service=0) {
		if ($service)  {
			$rowset = $this->fetchAll($this->select()->where('service = ?', $service));
		} else {
			$rowset = $this->fetchAll();
		}
		//$result = $this->rowsetToArray($rowset, 'id');
		$result = $rowset->toArray();
		return $result;
	}

	public function addSource($service) {
		$this->insert(array('user_id' =>$this->_user, 'service' => $service));
		$id = $this->_db->lastInsertId();
		return $id;
	}

	public function deleteSource($id) {
		if (!$source = $this->getSource($id)) return;
		$where = $this->getAdapter()->quoteInto('id = ?', $id);
		$this->delete($where);
	}

	public function setImported($id, $value) {
		if (!$source = $this->getSource($id)) return;
		$where = $this->getAdapter()->quoteInto('id = ?', $id);
		$this->update(array("imported" => $value), $where);
	}
}