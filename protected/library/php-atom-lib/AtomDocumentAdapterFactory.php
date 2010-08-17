<?php

require_once 'AtomFeedAdapter.php';

class DocumentAdapterFactoryException extends Exception { }

class AtomDocumentAdapterFactory {
	private static $_instance;
	protected $_adapterTable;
	
	/**
	 * 
	 * @return AtomDocumentAdapterFactory
	 */
	public static function getInstance() {
		if (!self::$_instance) {
			self::$_instance = new AtomDocumentAdapterFactory();
		}
		
		return self::$_instance;
	}
	
	public function adapt($data, $data_is_url=false) {
		$domObj = new SimpleXMLElement($data, null, $data_is_url);
		$documentAdapter = $this->_adapterTable[$domObj->getName()];
		if (isset($documentAdapter)) {
			return new $documentAdapter($domObj);
		}
		else {
			throw new DocumentAdapterFactoryException("No document adapter available for " . $domObj->getName() . " document!!");
		}
	}
	
	private function __construct() {
		$this->_adapterTable = array();
		
		$this->_adapterTable[AtomNS::FEED_ELEMENT]		= 'AtomFeedAdapter';
		$this->_adapterTable[AtomNS::ENTRY_ELEMENT]		= 'AtomEntryAdapter';
	}
}