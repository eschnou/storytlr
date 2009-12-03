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

class Stories extends Stuffpress_Db_Table
{

	protected $_name = 'stories';

	protected $_primary = 'id';

	public function getStory($id) {
		$rowset = $this->find($id);
		$result = $rowset->current();
		return $result;
	}

	public function getStories($count=10, $offset=50, $show_hidden=0) {
		$sql = "SELECT *, UNIX_TIMESTAMP(date_from) as 'date_from',  UNIX_TIMESTAMP(date_to) AS 'date_to', UNIX_TIMESTAMP(timestamp) AS 'timestamp' "
		. "FROM `stories` "
		. "WHERE user_id = :user_id "
		. ((!$show_hidden) ? "AND is_hidden = 0 " : " ")
		. "ORDER BY `date_from` DESC "
		. (($count>0) ? "LIMIT $count OFFSET $offset " : "");
			
		$data = array(':user_id' => $this->_user);
			
		$stmt = $this->_db->query($sql, $data);
		$result   = $stmt->fetchAll();

		return $result;
	}
	
	public function getAllStories($since=0, $show_hidden=0) {
		$sql = "SELECT u.username, s.*, UNIX_TIMESTAMP(s.date_from) as 'date_from',  UNIX_TIMESTAMP(s.date_to) AS 'date_to', UNIX_TIMESTAMP(s.timestamp) AS 'timestamp' "
			 . "FROM stories s LEFT JOIN users u ON s.user_id = u.id "
			 . "WHERE UNIX_TIMESTAMP(s.timestamp) > :since "
			 . ((!$show_hidden) ? "AND is_hidden = 0 " : " ")
			 . "ORDER BY `date_from` DESC ";

		$data = array(':since' => $since);
			 
 		$stmt 	= $this->_db->query($sql, $data);
 		$rows   = $stmt->fetchAll();
		return $rows;
	}
	
	public function isGeo($story_id) {
		$sql = "SELECT count(*) as `count` "
		. "FROM `story_items` i LEFT JOIN data d ON (i.source_id = d.source_id AND i.item_id = d.id) "	
		. "WHERE story_id = :story_id "
		. "AND d.latitude <> 0 AND d.longitude <> 0 AND i.is_hidden = 0 ";

		$data = array(':story_id' => $story_id);

		$stmt = $this->_db->query($sql, $data);
		$count = $stmt->fetchColumn(0);
		$result = ($count == 0) ? false : true;
		
		return $result;
	}

	public function addStory($from, $to, $title, $subtitle, $sources) {
		$sql = "INSERT INTO `stories` (user_id, title, subtitle, date_from, date_to, sources) "
		. "VALUES (:user_id, :title, :subtitle, FROM_UNIXTIME(:date_from), FROM_UNIXTIME(:date_to), :sources)";

		$data 		= array(":user_id" 	=> $this->_user,
							":date_from"=> $from,
							":date_to"	=> $to,
							":title"	=> $title,
							":subtitle"	=> $subtitle,
							":sources"	=> $sources);
			
		$statement = $this->_db->query($sql, $data);
		$id = $this->_db->lastInsertId();

		return $id;
	}


	public function deleteStory($id) {
		// Remove the pointer from the database
		$where = $this->getAdapter()->quoteInto('id = ?', $id);
		$this->delete($where);

		$items = new StoryItems();
		$items->deleteItems($id);
	}

	public function setHidden($story_id, $is_hidden=1) {
		$where = $this->getAdapter()->quoteInto('id = ?', $story_id);
		$this->update(array('is_hidden' => $is_hidden), $where);
	}

	public function setThumbnail($story_id, $key) {
		$where = $this->getAdapter()->quoteInto('id = ?', $story_id);
		$this->update(array('thumbnail' => $key), $where);
	}

	public function setTitle($story_id, $title) {
		$where = $this->getAdapter()->quoteInto('id = ?', $story_id);
		$this->update(array('title' => $title), $where);
	}

	public function setSubTitle($story_id, $subtitle) {
		$where = $this->getAdapter()->quoteInto('id = ?', $story_id);
		$this->update(array('subtitle' => $subtitle), $where);
	}
}