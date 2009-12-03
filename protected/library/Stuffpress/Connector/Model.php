<?php

abstract class Stuffpress_Connector_Model extends Stuffpress_Db_Table
{
	
	protected $_primary = 'id';
	
	protected $_prefix = 'none';
	
	protected $_id = 0;
	
	protected $_settings; 
	
	public static function createSource($id_user) {
		$table = new Sources();
		$id = $table->addSource($id_user, $this->_prefix);
		$source = $this->__construct($id);
		return $source;
	}
	
	abstract public function getName();
	
	abstract public function getServiceURL();
	
	abstract public function getAccount();
	
	abstract public function getDescription();
	
	abstract public function importData();
	
	abstract public function updateData();
	
	public function __construct($id=0) {
		// Call the parent in order to initialize all DB related funcions
		parent::__construct();
		
		// Fetch the settings from the database if id was provided
		$this->_id = $id;
		if ($id>0) $this->updateSettingsFromDatabase();
	}
	
	public function setSettings($settings) {
		$this->_settings = $settings;
	}
	
	public function getSettings() {
		return $this->_settings;
	}
	
	public function getID() {
		return $this->_id;
	}
	
	public function getPrefix() {
		return $this->_prefix;
	}
	
	public function saveSettings() {
		$this->commitSettingsToDatabase();
	}
	
	public function deleteSource() {
		$sources = new Sources();
		$sources->deleteSource($this->_id);
		
		$settings = new SourcesSettings();
		$settings->deleteSettings($this->_id);
		
		$this->deleteAllItems();
	}
		
	public function getItems($count=null, $offset=null) {
		$select = $this->select();
		$select->where('source_id = ?', $this->_id);
		$select->limit($count, $offset);
		$select->order('timestamp DESC');
		return $this->fetchAll($select);
	}
	
	public function getItemsByDate($from, $to) {
		$select = $this->select();
		$select->where('source_id = ?', $this->_id);
		$select->where('UNIX_TIMESTAMP(`timestamp`) > ? ', $from);
		$select->where('UNIX_TIMESTAMP(`timestamp`) < ?', $to);		
		$select->order('timestamp DESC');
		return $this->fetchAll($select);
	}
	
	public function deleteAllItems() {
		$where = $this->getAdapter()->quoteInto('source_id = ?', $this->_id);
		$this->delete($where);
	}
		
	private function updateSettingsFromDatabase() {
		$sourceSettings = new SourcesSettings();
		$this->_settings = $sourceSettings->getSettings($this->_id);
		return;
	}
	
	private function commitSettingsToDatabase() {
		$table = new SourcesSettings();
		$table->setSettings($this->_id, $this->_settings);
		return;
	}
}