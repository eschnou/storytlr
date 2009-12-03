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

class StoryItems extends Stuffpress_Db_Table
{

	protected $_name = 'story_items';

	public function getItems($story_id, $count=50, $offset=0, $show_hidden=0) {

		$sql = "SELECT i.story_id, i.source_id, i.item_id, i.service, i.is_hidden, UNIX_TIMESTAMP(i.timestamp) as timestamp, d.latitude, d.longitude, d.has_location "
		. "FROM `story_items` i LEFT JOIN data d ON (i.source_id = d.source_id AND i.item_id = d.id) "
		. "WHERE story_id = :story_id "
		. ((!$show_hidden) ? "AND i.is_hidden = 0 " : " ")
		. "ORDER BY i.timestamp "
		. (($count) ? "LIMIT $count " : " ")
		. (($offset) ? "OFFSET $offset" : " ");
			
		$data = array(':story_id' => $story_id);
			
		$stmt 	= $this->_db->query($sql, $data);
		$rows = $stmt->fetchAll();
		$result = $this->arrayToItems($rows);
		return $result;
	}

	public function getItemsCount($story_id, $show_hidden=0) {
		$sql = "SELECT count(*) as `count` "
		. "FROM `story_items` "
		. "WHERE story_id = :story_id "
		. ((!$show_hidden) ? "AND is_hidden = 0 " : " ");

		$data = array(':story_id' => $story_id);

		$stmt = $this->_db->query($sql, $data);
		$result = $stmt->fetchColumn(0);

		return $result;
	}

	public function addItem($story_id, $source_id, $item_id, $service, $timestamp, $is_hidden) {
		$sql = "INSERT INTO `story_items` (story_id, source_id, item_id, service, timestamp, is_hidden) "
		. "VALUES (:story_id, :source_id, :item_id, :service, FROM_UNIXTIME(:timestamp), :is_hidden)";

		$data 		= array(":story_id" 	=> $story_id,
							":source_id"	=> $source_id,
							":item_id"		=> $item_id,
							":service"		=> $service,
							":timestamp"	=> $timestamp,
							":is_hidden"	=> $is_hidden);
			
		$statement = $this->_db->query($sql, $data);
		$id = $this->_db->lastInsertId();

		return $id;
	}

	public function hideItem($story_id, $source_id, $item_id) {
		$sql = "UPDATE `story_items` SET is_hidden = 1 "
		. "WHERE `story_id`=:story_id  AND `source_id`=:source_id AND `item_id`=:item_id";


		$data = array(":story_id"	=> $story_id,
					  ":source_id" 	=> $source_id,
					  ":item_id"	=> $item_id);
			
		$stmt 	= $this->_db->query($sql, $data);
	}

	public function showItem($story_id, $source_id, $item_id) {
		$sql = "UPDATE `story_items` SET is_hidden = 0 "
		. "WHERE  `story_id`=:story_id  AND `source_id`=:source_id AND `item_id`=:item_id";


		$data = array(":story_id"	=> $story_id,
					  ":source_id" 	=> $source_id,
					  ":item_id"	=> $item_id);
			
		$stmt 	= $this->_db->query($sql, $data);
	}

	public function deleteItems($story_id) {
		$where = $this->getAdapter()->quoteInto('story_id = ?', $story_id);
		$this->delete($where);
	}

	private function arrayToItems($rowset) {
		$result = array();

		if (!$rowset || count($rowset) == 0) {
			return $result;
		}

		foreach($rowset as $row) {
			$result[] = $this->newItem($row);
		}

		return $result;
	}

	private function newItem($attributes) {

		if (!$attributes) return;

		$service = $attributes['service'];
		$id = $attributes['item_id'];
		$source = $attributes['source_id'];

		$db = $this->_db;
		$select = $db->select();
		$select->from($service . "_data");
		$select->where('id = ?', $id);
		$row = $db->fetchRow($select);

		$class = ucfirst($service)."Item";
		$item = new $class($row, $attributes);

		return $item;
	}

}