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

class Data extends Stuffpress_Db_Table
{

	protected $_name = 'data';

	protected $_primary = 'id';
	
	private $_tags_table;
	
	public function setUser($user = 0) {
		$this->_user = $user;
		if (isset($this->_tags_table)) {
			$this->_tags_table->setUser($user);
		}
	}

	public function getItem($source, $id) {

		$sql = "SELECT d.id, d.source_id, d.service, UNIX_TIMESTAMP(d.timestamp) as timestamp, d.is_hidden, d.user_id, d.comment_count, d.mention_count, d.tag_count, d.slug, d.latitude, d.longitude, d.elevation, d.has_location, s.enabled, s.public, s.imported "
		. "FROM data d LEFT JOIN sources s ON (d.source_id = s.id) "
		. "WHERE d.id = :id AND d.source_id = :source "
		. "ORDER BY timestamp DESC "
		. "LIMIT 1";

		$data = array(':id' => $id, ':source' => $source);

		$stmt 	= $this->_db->query($sql, $data);
		$row    = $stmt->fetch();
		$result	= $this->newItem($row);

		return $result;
	}

	public function getLastItems($count=10, $offset=0, $show_hidden=0, $sources=false, $types=false, $location_only=false, $show_reply=0) {

		if (!$sources) {
			$sources = 	$this->getSources();
		}
		
		if (!$sources || count($sources) == 0) {
			return false;
		}
		
		$sources = implode(',', $sources);

		if ($types) {
			$t = array();
			foreach ($types as $type) {
				$t[] = '"'. mysql_escape_string($type) . '"';
			}
			$t = implode(',', $t);
		}

		$sql = "SELECT id, source_id, service, UNIX_TIMESTAMP(timestamp) as timestamp, is_hidden, user_id, comment_count, mention_count, tag_count, slug, latitude, longitude, elevation, has_location "
		. "FROM data d "
		. "WHERE d.user_id = :user_id AND source_id IN ($sources) "
		. ((!$show_hidden) ? "AND is_hidden = 0 " : " ")
		. ((!$show_reply) ? "AND is_reply = 0 " : " ")
		. (($types) ? "AND type IN ($t) " : " ")
		. (($location_only) ? "AND has_location = true " : " ")
		. "ORDER BY timestamp DESC "
		. "LIMIT $count OFFSET $offset ";

		$data = array(':user_id' => $this->_user);

		$stmt 	= $this->_db->query($sql, $data);
		$rows   = $stmt->fetchAll();
		$result	= $this->arrayToItems($rows);

		return $result;
	}

	public function getArchive($year, $month, $show_hidden=0, $count=50, $offset=0) {

		if (!$sources = $this->getSources()) {
			return false;
		}
		$sources = implode(',', $sources);

		$sql = "SELECT d.id, d.source_id, d.service, UNIX_TIMESTAMP(d.timestamp) as timestamp, d.is_hidden, d.user_id, d.comment_count, d.mention_count, d.tag_count, d.slug "
		. "FROM data d "
		. "WHERE d.user_id = :user_id AND source_id IN ($sources) "
		. ((!$show_hidden) ? "AND is_hidden = 0 " : " ")
		. "AND YEAR(d.timestamp) = :year AND MONTH(d.timestamp) = :month "
		. "ORDER BY timestamp "
		. "LIMIT $count OFFSET $offset ";

		$data = array(':user_id' => $this->_user, ':year' => $year, ':month' => $month);

		$stmt 	= $this->_db->query($sql, $data);
		$rows   = $stmt->fetchAll();
		$result	= $this->arrayToItems($rows);

		return $result;
	}

	public function getItemsByTag($tags, $sources=false, $count=10, $offset=0, $show_hidden=0) {
			
		// Prepare the tag filter
		if (!$tags || count($tags)<=0) {
			return;
		}
		$t = array();
		foreach($tags as $tag) {
			$t[] = "'" . mysql_escape_string($tag) . "'";
		}
		$tags = implode(',', $t);
		
		// Prepare the sources list
		if (!$sources) {
			$sources = 	$this->getSources();
		}
		if (!$sources) {
			return false;
		}
		$sources = implode(',', $sources);
		
		
		$sql = "SELECT d.id, d.source_id, d.service, UNIX_TIMESTAMP(d.timestamp) as timestamp, d.is_hidden, d.user_id, d.comment_count, d.mention_count, d.tag_count, d.slug, t.tag "
		. "FROM data d LEFT JOIN tags t ON d.source_id = t.source_id AND d.id = t.item_id "
		. "WHERE d.user_id = :user_id AND d.source_id IN ($sources) AND symbol IN ($tags) "
		. ((!$show_hidden) ? "AND is_hidden = 0 " : " ")
		. "GROUP BY id ORDER BY timestamp DESC "
		. "LIMIT $count OFFSET $offset ";

		$data = array(':user_id' => $this->_user);

		$stmt 	= $this->_db->query($sql, $data);
		$rows   = $stmt->fetchAll();
		$result	= $this->arrayToItems($rows);

		return $result;
		
	}
	
	public function getItemsPerMonth($show_hidden=0) {

		if (!$sources = $this->getSources()) {
			return false;
		}
		$sources = implode(',', $sources);

		$sql = "SELECT count(d.id) as c, year(d.timestamp) as year, month(d.timestamp) as month, d.comment_count, d.mention_count "
		. "FROM data d "
		. "WHERE d.user_id = :user_id AND source_id IN ($sources) "
		. (($show_hidden) ? "AND is_hidden = 0 " : " ")
		. "GROUP BY year, month "
		. "ORDER BY year DESC, month DESC ";

		$data = array(':user_id' => $this->_user);

		$stmt 	= $this->_db->query($sql, $data);
		$result = $stmt->fetchAll();

		return $result;
	}

	public function getItemsByDate($from, $to, $source_id=0, $show_hidden=0) {

		if (!$sources = $this->getSources()) {
			return false;
		}
		$sources = implode(',', $sources);

		$sql = "SELECT d.id, d.source_id, d.service, UNIX_TIMESTAMP(d.timestamp) as timestamp, d.is_hidden, d.user_id, d.comment_count, d.mention_count, d.tag_count, d.slug "
		. "FROM data d "
		. "WHERE d.user_id = :user_id AND source_id IN ($sources) "
		. ((!$show_hidden) ? "AND is_hidden = 0 " : " ")
		. (($source_id) ? "AND source_id = $source_id " : " ")
		. "HAVING timestamp>$from AND timestamp<$to "
		. "ORDER BY timestamp DESC ";

		$data = array(':user_id' => $this->_user);

		$stmt 	= $this->_db->query($sql,$data);
		if ($rows   = $stmt->fetchAll()) {
			return $this->arrayToItems($rows);
		}

		return false;
	}

	public function getItemsCount($source_id) {

		$sql = "SELECT user_id, count(*) AS c FROM data WHERE source_id = :source_id GROUP BY source_id";

		$data= array(':source_id' => $source_id);

		$stmt 	= $this->_db->query($sql, $data);
		if ($row	= $stmt->fetch()) {
			$user_id = $row['user_id'];
			$result	 = $row['c'];
		}

		return $count;
	}

	public function getAllItems($source_id) {

		$sql = "SELECT d.id, d.source_id, d.service, UNIX_TIMESTAMP(d.timestamp) as timestamp, d.is_hidden, d.user_id, d.comment_count, d.mention_count, d.tag_count, d.slug "
		. "FROM data d "
		. "WHERE d.source_id = $source_id "
		. "ORDER BY timestamp DESC ";

		$stmt 	= $this->_db->query($sql);
		if ($rows   = $stmt->fetchAll()) {
			$result	= $this->arrayToItems($rows);
			$user_id = $result[0]->getUserid();
		}

		return $result;
	}

	public function search($source_id, $service, $index, $term, $show_hidden=0) {

		$sql = "SELECT d.id, d.source_id, d.service, UNIX_TIMESTAMP(d.timestamp) as timestamp, d.is_hidden, d.user_id, d.comment_count, d.mention_count, d.tag_count, d.slug "
		. "FROM {$service}_data t LEFT JOIN data d ON (t.id = d.id AND t.source_id = d.source_id) "
		. "WHERE MATCH($index) AGAINST(:term) AND t.source_id= :source_id "
		. ((!$show_hidden) ? "AND is_hidden = 0 " : " ")
		. "ORDER BY timestamp DESC ";

		$data = array(":term" => $term, ":source_id" => $source_id);

		$stmt   = $this->_db->query($sql, $data);
		$rows   = $stmt->fetchAll();
		$result = $this->arrayToItems($rows);
		
		return $result;
	}

	public function addItem($id, $source_id, $user_id, $service, $type, $timestamp, $is_hidden=0, $is_reply=0) {
		
		$is_hidden = $is_hidden ? 1 : 0;
		
		$sql = "INSERT INTO `data` (id, source_id, user_id, service, type, timestamp, is_hidden, is_reply) "
		. "VALUES (:id, :source_id, :user_id, :service, :type, FROM_UNIXTIME(:timestamp), :is_hidden, :is_reply)";

		$data = array(":id" => $id,
					  ":source_id" 	=> $source_id,
					  ":user_id"	=> $user_id,
					  ":service" 	=> $service,
					  ":type" 		=> $type,
					  ":is_hidden"	=> $is_hidden,
				      ":is_reply"	=> $is_reply,
					  ":timestamp" 	=> $timestamp);

		$statement = $this->_db->query($sql, $data);
	}

	public function deleteItems($source_id) {
		$sources = new Sources();
		if (!$source  = $sources->getSource($source_id)) return;
		$where = $this->getAdapter()->quoteInto('source_id = ?', $source_id);
		$this->delete($where);
	}

	public function deleteItem($source_id, $item_id) {
		if (!$item = $this->getItem($source_id, $item_id)) return;
		$where		= array();
		$where[] 	= $this->getAdapter()->quoteInto('source_id = ?', $source_id);
		$where[] 	= $this->getAdapter()->quoteInto('id = ?', $item_id);
		$this->delete($where);
	}

	public function hideItem($source_id, $item_id) {
		$sql = "UPDATE `data` SET is_hidden = 1 "
		. "WHERE `source_id`=:source_id AND `id`=:item_id";


		$data = array(":source_id" 	=> $source_id,
					  ":item_id"	=> $item_id);

		$stmt 	= $this->_db->query($sql, $data);

		return;
	}

	public function showItem($source_id, $item_id) {
		$sql = "UPDATE `data` SET is_hidden = 0 "
		. "WHERE `source_id`=:source_id AND `id`=:item_id";


		$data = array(":source_id" 	=> $source_id,
					  ":item_id"	=> $item_id);

		$stmt 	= $this->_db->query($sql, $data);

		return;
	}

	public function setTimestamp($source_id, $item_id, $timestamp) {
		$sql = "UPDATE `data` SET timestamp = FROM_UNIXTIME(:timestamp) "
		. "WHERE `source_id`=:source_id AND `id`=:item_id";


		$data = array(":source_id" 	=> $source_id,
					  ":item_id"	=> $item_id,
					  ":timestamp"  => $timestamp);

		$stmt 	= $this->_db->query($sql, $data);

		return;
	}
	
	public function setLocation($source_id, $item_id, $latitude, $longitude, $elevation=0, $accuracy=0) {
		$sql = "UPDATE `data` SET latitude = :latitude, longitude = :longitude, elevation=:elevation, accuracy=:accuracy, has_location=true "
			 . "WHERE `source_id`=:source_id AND `id`=:item_id";


		$data = array(":source_id" 	=> $source_id,
					  ":item_id"	=> $item_id,
					  ":latitude"   => $latitude,
					  ":longitude"  => $longitude,
					  ":elevation"  => $elevation,
					  ":accuracy"   => $accuracy);
				
		$stmt 	= $this->_db->query($sql, $data);

		return;
	}
	
	public function clearLocation($source_id, $item_id) {
		$sql = "UPDATE `data` SET latitude = 0, longitude = 0, elavation= 0, has_location=false "
			 . "WHERE `source_id`=:source_id AND `id`=:item_id";


		$data = array(":source_id" 	=> $source_id,
					  ":item_id"	=> $item_id);
				
		$stmt 	= $this->_db->query($sql, $data);

		return;
	}
	
	public function setTags($source_id, $item_id, $tags) {
		$this->_db->beginTransaction();
		$tags_table = $this->getTagsTable();
		$tags_table->deleteTags($source_id, $item_id);
		$tag_count = 0;
		if ($tags && count($tags > 0)) {
			foreach($tags as $tag) {
				if ($tags_table->addTag($source_id, $item_id, $tag)) {
					$tag_count++;
				}
			}
		}
		$count	= $tags ? count($tags) : 0;
		$this->setTagCount($source_id, $item_id, $tag_count);
		$this->_db->commit();	
	}
	
	public function setTagCount($source_id, $item_id, $count) {
		$sql = "UPDATE `data` SET tag_count = :count "
		. "WHERE `source_id`=:source_id AND `id`=:item_id";


		$data = array(":source_id" 	=> $source_id,
					  ":item_id"	=> $item_id,
					  ":count"  	=> $count);

		$stmt 	= $this->_db->query($sql, $data);

		return;
	}
	
	public function setSlug($source_id, $item_id, $slug) {
		$sql = "UPDATE `data` SET slug = :slug "
		. "WHERE `source_id`=:source_id AND `id`=:item_id";


		$data = array(":source_id" 	=> $source_id,
					  ":item_id"	=> $item_id,
					  ":slug"  		=> $slug);

		$stmt 	= $this->_db->query($sql, $data);

		return;
	}

	public function increaseComments($source_id, $item_id) {
		$sql = "UPDATE `data` SET comment_count = comment_count + 1 "
		. "WHERE `source_id`=:source_id AND `id`=:item_id";


		$data = array(":source_id" 	=> $source_id,
					  ":item_id"	=> $item_id);

		$stmt 	= $this->_db->query($sql, $data);

		return;
	}

	public function decreaseComments($source_id, $item_id) {
		$sql = "UPDATE `data` SET comment_count = comment_count - 1 "
		. "WHERE `source_id`=:source_id AND `id`=:item_id";


		$data = array(":source_id" 	=> $source_id,
					  ":item_id"	=> $item_id);

		$stmt 	= $this->_db->query($sql, $data);

		return;
	}
	
	public function increaseMentions($source_id, $item_id) {
		$sql = "UPDATE `data` SET mention_count = mention_count + 1 "
				. "WHERE `source_id`=:source_id AND `id`=:item_id";
	
	
		$data = array(":source_id" 	=> $source_id,
				":item_id"	=> $item_id);
	
		$stmt 	= $this->_db->query($sql, $data);
	
		return;
	}
	
	public function decreaseMentions($source_id, $item_id) {
		$sql = "UPDATE `data` SET mention_count = mention_count - 1 "
				. "WHERE `source_id`=:source_id AND `id`=:item_id";
	
	
		$data = array(":source_id" 	=> $source_id,
				":item_id"	=> $item_id);
	
		$stmt 	= $this->_db->query($sql, $data);
	
		return;
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
		$id = $attributes['id'];
		$source = $attributes['source_id'];

		$db = $this->_db;
		$select = $db->select();
		$select->from($service."_data");
		$select->where('id = ?', $id);
		$row = $db->fetchRow($select);

		$class = ucfirst($service)."Item";
		$item = new $class($row, $attributes);
		return $item;
	}

	private function getSources($enabled=1, $imported=1, $public=1) {
		$sql     = "SELECT id FROM sources WHERE user_id={$this->_user} AND enabled=$enabled AND imported=$imported AND public=$public";
		$stmt    = $this->_db->query($sql);
		$sources = $stmt->fetchAll(Zend_Db::FETCH_COLUMN, 0);
		return $sources;
	}
	
	private function getTagsTable() {
		if (!$this->_tags_table) {
			$this->_tags_table = new Tags();
		}
		return $this->_tags_table;
	}
}