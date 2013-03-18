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

class Mentions extends Stuffpress_Db_Table
{
	protected $_name = 'mentions';

	protected $_primary = 'id';

	public function getMention($id) {
		$rowset = $this->find($id);
		$result = $rowset->current();
		return $result;
	}

	public function getMentions($source_id, $item_id) {
		$sql = "SELECT * FROM `mentions` "
		. "WHERE source_id = :source_id AND item_id = :item_id "
		. "ORDER BY timestamp ";

		$data 		= array("source_id" 	=> $source_id,
							"item_id"		=> $item_id);

		$stmt 	= $this->_db->query($sql, $data);
		$result = $stmt->fetchAll();
		return $result;
	}

	public function getLastMentions($show_hidden=0) {
		$sql = "SELECT c.* FROM `mentions` c JOIN `data` d ON (d.id = c.item_id AND d.source_id = c.source_id) "
		. "WHERE c.user_id = :user_id "
		. ((!$show_hidden) ? "AND d.is_hidden = 0 " : " ")
		. "ORDER BY c.timestamp DESC "
		. "LIMIT 5";

		$data 		= array("user_id" 	=> $this->_user);

		$stmt = $this->_db->query($sql, $data);
		$result = $stmt->fetchAll();

		return $result;
	}

	public function addMention($source_id, $item_id, $url, $entry, $author_name, $author_url, $author_bio, $author_avatar, $timestamp) {
		$sql = "INSERT INTO `mentions` (source_id, item_id, url, entry, author_name, author_url, author_bio, author_avatar, timestamp) "
		. "VALUES (:source_id, :item_id, :url, :entry, :author_name, :author_url, :author_bio, :author_avatar, FROM_UNIXTIME(:timestamp))";


		$data 		= array("source_id" 	=> $source_id,
							"item_id"		=> $item_id,
							"url"			=> $url,
							"entry"			=> $entry,
							"author_name"	=> $author_name,
							"author_url"	=> $author_url,
				      		"author_bio"	=> $author_bio,
							"author_avatar"	=> $author_avatar,
							"timestamp"		=> $timestamp);

		$statement = $this->_db->query($sql, $data);
		$id = $this->_db->lastInsertId();

//		$data = new Data();
//		$data->increaseMentions($source_id, $item_id);

		return $id;
	}


	public function deleteComment($id) {
		if (!($comment = $this->getComment($id))) return;

		// Decrease the counter
		$data    = new Data();
		$data->decreaseComments($comment->source_id, $comment->item_id);

		// Delete the comment
		$where = $this->getAdapter()->quoteInto('id = ?', $id);
		$this->delete($where);
	}

	public function deleteComments($source_id, $item_id=0) {
		$where = array();
		$where[] = $this->getAdapter()->quoteInto('source_id = ?', $source_id);
		if ($item_id) $where[] = $this->getAdapter()->quoteInto('item_id = ?', $item_id);
		$this->delete($where);
	}

	public function setNotify($comment_id, $email, $notify) {
		$w1 = $this->getAdapter()->quoteInto('comment_id = ?', $comment_id);
		$w2 = $this->getAdapter()->quoteInto('email = ?', $email);
		$this->update(array('notify' => $notify), array($w1, $w2));
	}
}