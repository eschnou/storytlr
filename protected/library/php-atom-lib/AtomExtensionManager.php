<?php

class ExtensionManagerException extends Exception { }

class AtomExtensionManager {
	private static $_instance;
	protected $_adapterTable;
	
	/**
	 * 
	 * @return AtomExtensionManager
	 */
	public static function getInstance() {
		if (!self::$_instance) {
			self::$_instance = new AtomExtensionManager();
		}
		
		return self::$_instance;
	}
	
	public function getExtensionAdapter(SimpleXMLElement $atomNode, $namespace) {
		if (isset($this->_adapterTable[$namespace])) {
			return $this->_adapterTable[$namespace]->adapt($atomNode);
		}
		else {
			throw new ExtensionManagerException('a'.print_r($this->_adapterTable,true).'No Extension Adaptor Available!');
		}
	}
	
	public function registerExtensionAdapter(IAtomExtensionFactory $adapter) {
		$this->_adapterTable[$adapter->getNamespace()] = $adapter;
	}
	
	private function __construct() {}
}