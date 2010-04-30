<?php
/*
 *  Copyright 2008-2009 Laurent Eschenauer and Alard Weisscher
 *  Copyright 2010 John Hobbs
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

class Tags extends Stuffpress_Db_Table
{

	protected $_name = 'tags';

	protected $_primary = 'user_id, source_id, item_id';

	public function getTags($source_id, $item_id) {
		$sql = "SELECT * FROM `tags` WHERE source_id = :source_id AND item_id = :item_id";			
		$data = array(':source_id' => $source_id, ':item_id' => $item_id);
		$stmt = $this->_db->query($sql, $data);
		$result   = $stmt->fetchAll();
		return $result;
	}
	
	public function getTopTags($limit=10) {
		$sql = "SELECT tag, symbol, count(*) as count FROM `tags` WHERE user_id = :user_id GROUP BY symbol ORDER BY count DESC LIMIT $limit";			
		$data = array(':user_id' => $this->_user);
		$stmt = $this->_db->query($sql, $data);
		$result   = $stmt->fetchAll();
		return $result;
	}

	public function addTag($source_id, $item_id, $tag) {
		$tag = strip_tags($tag);
		$tag = trim($tag);

		$symbol = preg_replace('/\W/', '', $tag);
		//! \todo This is an iffy fix, because if you search for something with a mix of UTF-8 and roman chars, the symbol will be wrong.
		if( empty( $symbol ) ) { $symbol = htmlspecialchars( $tag ); }

		if (strlen($tag) == 0) return false;
		
		$sql = "INSERT IGNORE INTO `tags` (user_id, source_id, item_id, tag, symbol) VALUES (:user_id, :source_id, :item_id, :tag, :symbol)";
		
		$data 		= array(":user_id" 	=> $this->_user,
							":source_id"=> $source_id,
							":item_id"	=> $item_id,
							":tag"		=> $tag,
							":symbol"	=> $symbol);
			
		$statement = $this->_db->query($sql, $data);
		
		return true;
	}

	public function deleteTags($source_id, $item_id) {
		$where		= array();
		$where[] 	= $this->getAdapter()->quoteInto('source_id = ?', $source_id);
		$where[] 	= $this->getAdapter()->quoteInto('item_id = ?', $item_id);
		$this->delete($where);
	}

	public function deleteUser() {
		$where = $this->getAdapter()->quoteInto('user_id = ?', $this->_user);
		$this->delete($where);
	}
	
	public function deleteSource($source_id) {
		$where = $this->getAdapter()->quoteInto('source_id = ?', $source_id);
		$this->delete($where);
	}
}

