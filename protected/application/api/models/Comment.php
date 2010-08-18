<?php

/**
 * A class model for a single comment
 * 
 * @author eko
 *
 */
class Comment {

	protected $_data;
	
	/**
	 * @param array $data
	 * @return void
	 */
	public function __construct($data) {
		if (is_array($data)) {
			$this->_data = $data;
		}
		else {
			throw new Exception('An array is required, the provided data is not an array!!');
		}
	}
	
	public function getCommentId() {
		return $this->_data['id'];
	}
	
	public function getItemId() {
		return $this->_data['item_id'];
	}
	
	public function getItemSourceId() {
		return $this->_data['source_id'];
	}
	
	public function getText() {
		return $this->_data['comment'];
	}
	
	public function getAuthorName() {
		return $this->_data['name'];
	}
	
	public function getAuthorEmail() {
		return $this->_data['email'];
	}
	
	public function getAuthorWebsite() {
		return $this->_data['website'];
	}
	
	public function getPublished() {
		return $this->_data['timestamp'];
	}
}