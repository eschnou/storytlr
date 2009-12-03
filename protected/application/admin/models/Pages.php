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

class Pages extends Stuffpress_Db_Table
{

	protected $_name = 'pages';

	protected $_primary = 'id';

	public static function getAvailablePages() {
		return array('dashboard', 'lifestream', 'stories', 'pictures', 'videos', 'map', 'custom', 'link'); 
	}
	
	public static function getSchemas() {
		$pages 	= array();
		$prefix = Pages::getAvailablePages();
		foreach($prefix as $p) {
			$pages[$p]= Pages::getSchema($p);
		}
		return $pages;
	}

	public static function getModel($prefix) {
		$class = ucfirst($prefix)."Page";
		$model = new $class();
		return $model;
	}
	
	public static function getSchema($prefix) {
		$class = ucfirst($prefix)."Page";
		$model = new $class();

		$page = array();
		$page['prefix']= $prefix;
		$page['name']  = $model->getName();
		$page['description']  = $model->getDescription();
		return $page;
	}

	public function getPage($id) {
		if (!$result = $this->cacheLoad('Pages_getPage_' . $id)) {
			$rowset = $this->find($id);
			if (count($rowset)>0) {
				$row	= $rowset->current()->toArray();
				$page   = $this->getSchema($row['prefix']);
				$result = array_merge($row, $page);
			}
			else {
				$result = false;
			}
			$this->cacheSave($result, 'Pages_getPage_' . $id);
		}
		return $result;
	}

	public function getPages() {
		if (!$result = $this->cacheLoad('Pages_getPages_' . $this->_user)) {
			$sql = "SELECT * FROM `pages` "
				 . "WHERE user_id = :user_id "
				 . "ORDER BY position ";
			
			$data 		= array("user_id" 	=> $this->_user);
	
	 		$stmt 	 = $this->_db->query($sql, $data);
	 		$rows 	 = $stmt->fetchAll();
			$result  = array();
			foreach($rows as $r) {
				$page = $this->getSchema($r['prefix']);
				$result[] = array_merge($r, $page);
			}	 		
			$this->cacheSave($result, 'Pages_getPages_' . $this->_user);
		}
		return $result;
	}
	

	public function addPage($page, $title='') {
		$this->insert(array('user_id' =>$this->_user, 'prefix' => $page, 'title' => $title));
		$id = $this->_db->lastInsertId();
		$this->cacheRemove('Pages_getPages_' . $this->_user);
		return $id;
	}

	public function deletePage($id) {
		$where = $this->getAdapter()->quoteInto('id = ?', $id);
		$this->delete($where);
		$properties = new PagesProperties(array(Properties::KEY => $id));
		$properties->deleteAllProperties();
		$this->cacheRemove('Pages_getPages_' . $this->_user);
	}

	public function setPosition($id, $position) {
		$where = $this->getAdapter()->quoteInto('id = ?', $id);
		$this->update(array('position' => $position), $where);
		$this->cacheRemove('Pages_getPages_' . $this->_user);
		$this->cacheRemove('Pages_getPage_' . $id);
	}
	
	public function setTitle($id, $title) {
		$where = $this->getAdapter()->quoteInto('id = ?', $id);
		$this->update(array('title' => $title), $where);
		$this->cacheRemove('Pages_getPages_' . $this->_user);
		$this->cacheRemove('Pages_getPage_' . $id);
	}
}