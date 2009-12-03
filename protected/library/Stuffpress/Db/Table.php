<?php

class Stuffpress_Db_Table extends Zend_Db_Table
{
	const USER          = 'user';
	
	protected $_cache;
	
	protected $_user;
	
    public function __construct($config = array()) {
		parent::__construct($config);
		if (isset($config[self::USER])) {
			$this->_user = $config[self::USER];
		} elseif (Zend_Registry::isRegistered("shard")) {
			$this->_user = Zend_Registry::get("shard");
		}	 
	} 
	
	public function setUser($user = 0) {
		$this->_user = $user;
	}
	
	public function init() {
		// Get the cache
		if (Zend_Registry::isRegistered("sql_cache")) {
			$this->_cache = Zend_Registry::get("sql_cache");
		} else {
			$this->_cache = false;
		}		
	}
	
	public function disableCache() {
		$this->_cache = false;
	}
	
	public function rowsetToArray($rowset, $key=false) {
		if (!$rowset || count($rowset) == 0) {
			return false;	
		}
		
		$result = array();
		$rows   = $rowset->toArray();
		for($i=0; $i<count($rows); $i++) {
			$row = $rows[$i];
			$index = $key ? $row[$key] : $i;
			$result[$index] = $row;
		}
		return $result;
	}
	
	protected function cacheLoad($key) {
		$key = $this->_user ? "User_{$this->_user}_$key" : $key;		
		if ($this->_cache) {
			return $this->_cache->load($key);
		}
	}
	
	protected function cacheSave($result, $key, $tags=array()) {
		$key = $this->_user ? "User_{$this->_user}_$key" : $key;
		if ($this->_cache) {
			return $this->_cache->save($result, $key, $tags);
		}
	}
	
	protected function cacheRemove($key) {
		$key = $this->_user ? "User_{$this->_user}_$key" : $key;
		if ($this->_cache) {
			$this->_cache->remove($key);
		}
	}
}