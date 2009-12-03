<?php
require_once 'Stuffpress/Db/Table.php';

abstract class Stuffpress_Db_Properties extends Stuffpress_Db_Table
{
	const KEY = 'property_key';
	
	protected $_id = 0;
	
	protected $_properties;
		
	public function __construct($config = array()) {
		parent::__construct($config);
		if (isset($config[self::KEY])) {
			$this->_id = $config[self::KEY];
		}
	} 
	
	public function getProperty($key, $default=false) {
		if (!isset($this->_properties)) {
			$this->getPropertiesFromDB();
		}
		
		if (isset($this->_properties[$key])) {
			return $this->_properties[$key];
		}
		
		if ($default) {
			return $default;
		}
		
		return $this->getDefault($key);
	}
	
	public function getDefault($key) {
		return false;
	}
	
	public function getProperties($keys) {
		$values = array();
		foreach($keys as $key) {
			$values[$key] = $this->getProperty($key);
		}
		return $values;
	}
	
	public function setProperties($data) {
		foreach($data as $key => $value) {
			$this->setProperty($key, $value);
		}
	}
	
	public function getPropertiesArray() {
		if (!isset($this->_properties)) {
			$this->getPropertiesFromDB();
		}
		return $this->_properties;
	}
	
	public function setProperty($key, $value) {
		$sql = "INSERT INTO `{$this->_name}` (`{$this->_primary}`, `key`, `value`) "
			 . "VALUES (:primary, :key, :value) "
			 . "ON DUPLICATE KEY UPDATE `value` = :dupe";
		
		$data = array();
		$data[':primary'] = $this->_id;
		$data[':key'] = $key;
		$data[':value'] = $value;
		$data[':dupe'] = $value;
		
		$this->_db->query($sql, $data);
		$this->cacheRemove("Properties_get_{$this->_name}_{$this->_id}");		
		$this->getPropertiesFromDB();
	}
	
	public function deleteProperty($key) {
		$sql = "DELETE FROM `{$this->_name}` WHERE `{$this->_primary}` = :primary AND `key` = :key";
		
		$data[':primary'] = $this->_id;
		$data[':key'] = $key;
		
		$this->_db->query($sql, $data);
		$this->cacheRemove("Properties_get_{$this->_name}_{$this->_id}");		
		$this->getPropertiesFromDB();		
	}
	
	public function deleteAllProperties() {
		$sql = "DELETE FROM `{$this->_name}` WHERE `{$this->_primary}` = :primary";
		
		$data[':primary'] = $this->_id;
		
		$this->_db->query($sql, $data);
		$this->cacheRemove("Properties_get_{$this->_name}_{$this->_id}");		
		unset($this->_properties);
	}
	
	private function getPropertiesFromDB() {
		if (!$result = $this->cacheLoad("Properties_get_{$this->_name}_{$this->_id}")) {			
			$sql = "SELECT `key`, `value` FROM `{$this->_name}` WHERE `{$this->_primary}` = :primary";
			$data[':primary'] = $this->_id;
			$result	= $this->_db->fetchPairs($sql, $data);
			$this->cacheSave($result, "Properties_get_{$this->_name}_{$this->_id}");			
		}
		$this->_properties = $result;
	}
}