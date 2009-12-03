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

class Widgets extends Stuffpress_Db_Table
{

	protected $_name = 'widgets';

	protected $_primary = 'id';

	public static function getAvailableWidgets() {
		$list	 = array('archives', 'bio', 'custom', 'lastcomments', 'links', 'logo', 'rsslink', 'search', 'tags', 'music', 'membersgfc');
		$widgets = array();
		foreach($list as $item) {
			$widgets[$item]= Widgets::getModel($item);
		}
		return $widgets;
	}

	public static function getModel($prefix) {
		$class = ucfirst($prefix)."Widget";
		$model = new $class();

		$widget = array();
		$widget['prefix']= $prefix;
		$widget['name']  = $model->getName();
		$widget['edit']  = $model->canEdit();
		$widget['desc']  = $model->getDescription();
		return $widget;
	}

	public function getWidget($id) {
		if (!$result = $this->cacheLoad('Widgets_getWidget_' . $id)) {
			$rowset = $this->find($id);
			if (count($rowset)>0) {
				$row = $rowset->current()->toArray();
				$widget = Widgets::getModel($row['prefix']);
				$widget['id'] = $row['id'];
				$widget['user_id'] = $row['user_id'];
				$result = $widget;
			}
			else {
				$result = false;
			}
			$this->cacheSave($result, 'Widgets_getWidget_' . $id, array("Widgets_{$this->_user}"));
		}
		return $result;
	}

	public function getWidgets() {
		if (!$result = $this->cacheLoad('Widgets_getWidgets_' . $this->_user)) {
			$sql = "SELECT * FROM `widgets` "
				 . "WHERE user_id = :user_id "
				 . "ORDER BY position ";
			
			$data 		= array("user_id" 	=> $this->_user);
	
	 		$stmt 	 = $this->_db->query($sql, $data);
	 		$rows 	 = $stmt->fetchAll();
			$result = array();
					
			foreach($rows as $row) {
				$widget = Widgets::getModel($row['prefix']);
				$widget['id'] = $row['id'];
				$widget['user_id'] = $row['user_id'];
				$result[] = $widget;
			}
			$this->cacheSave($result, 'Widgets_getWidgets_' . $this->_user);
		}
		return $result;
	}
	

	public function addWidget($widget) {
		$this->insert(array('user_id' =>$this->_user, 'prefix' => $widget));
		$id = $this->_db->lastInsertId();
		$this->cacheRemove('Widgets_getWidgets_' . $this->_user);
		return $id;
	}

	public function deleteWidget($id) {
		if (!$widget = $this->getWidget($id)) return;
		$where = $this->getAdapter()->quoteInto('id = ?', $id);
		$this->delete($where);
		$properties = new WidgetsProperties(array(Properties::KEY => $id));
		$properties->deleteAllProperties();
		$this->cacheRemove('Widgets_getWidgets_' . $this->_user);
	}

	public function setPosition($id, $position) {
		if (!$widget = $this->getWidget($id)) return;
		$where = $this->getAdapter()->quoteInto('id = ?', $id);
		$this->update(array('position' => $position), $where);
		$this->cacheRemove('Widgets_getWidgets_' . $this->_user);
		$this->cacheRemove('Widgets_getWidget_' . $id);
	}
}