<?php

require_once 'AtomNS.php';
require_once 'AtomAdapterBasic.php';

class AtomAdapterException extends Exception { }

abstract class BaseAtomAdapter {	
	protected $_atomNode;
	protected $_prefix;
	
	public function getBase() {
		return (string)$this->_atomNode->attributes("xml", true)->{AtomNS::BASE_ATTRIBUTE};
	}
	
	public function getLang() {
		return (string)$this->_atomNode->attributes("xml", true)->{AtomNS::LANG_ATTRIBUTE};
	}
	
	public function getXml() {
		return $this->_atomNode->asXML();
	}
	
	public function addNamespace($prefix, $namespace) {
		$this->_atomNode->addAttribute($prefix.':temp',null,$namespace);
		unset($this->_atomNode->attributes($namespace)->temp);
	}

	public function getExtension($namespace) {
		return AtomExtensionManager::getInstance()->getExtensionAdapter($this->_atomNode, $namespace);
	}
	
	public function getDocumentType() {
		return $this->_atomNode->getName();
	}
	
	public function __construct($adapterType,$data,$data_is_url=false) {
		if (is_string($data)) { 
			$this->_atomNode = new SimpleXMLElement($data,null,$data_is_url);
		}
		else if ($data instanceof SimpleXMLElement) { 
			$this->_atomNode = $data;
		}
		else if ($data === null) {
			$this->_atomNode = new SimpleXMLElement("<".$adapterType." xmlns='".AtomNS::NS."'></".$adapterType.">",null,$data_is_url);
		}
		else { 		
			throw new AtomAdapterException("Invalid Data Type");
		}
		
		if ($this->_atomNode->getName() != $adapterType) { //check whether $this->_atomNode is the appropriate XML Object, e.g. atom entry node for AtomEntryAdapter
			throw new AtomAdapterException("Invalid XML Object");
		}
		
		$this->_prefix = $this->_getPrefix(AtomNS::NS);
		if ($this->_prefix === null) {
			throw new AtomAdapterException("Invalid Atom Document");
		}
	}
	
	public function __get($name) {
        $method = 'get' . $name;
        return $this->$method();
	}
	
	public function __set($name, $value) {
		$method = 'set' . $name;
		$this->$method($value);
	}
	
	protected function _getPrefix($namespace) {
		foreach($this->_atomNode->getDocNamespaces(true) as $prefix => $ns) {
			if ($ns == $namespace) {
				return $prefix;
			}
		}
		return null;
	}

	protected function _getAttribute($attribute, $namespace=null) {
		return (string)$this->_atomNode->attributes($namespace)->$attribute;
	}
	
	protected function _setAttribute($attribute, $value, $namespace=null) {
		if ($value !== null)
		{
			if (!isset($this->_atomNode->attributes($namespace)->$attribute)) {
				if ($this->_prefix != "") {
					$attribute = $this->_prefix . ":" . $attribute;
				}
				$this->_atomNode->addAttribute($attribute, $value, $namespace);
				return;
			}
			
			$this->_atomNode->attributes($namespace)->$attribute = $value;
		}
	}
}