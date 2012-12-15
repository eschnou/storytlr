<?php

require_once 'ExtensibleAtomAdapter.php';
require_once 'SimpleAtomAdapter.php';

class AtomPersonConstructAdapter extends ExtensibleAtomAdapter {
	protected $_name;
	protected $_uri;
	protected $_email;
	
	public function getEmail() {
		return $this->_email->value;
	}
	
	public function getName() {
		return $this->_name->value;
	}
	
	public function getUri() {
		return $this->_uri->value;
	}
	
	public function setEmail($value) {
		if (!isset($this->_email)) {
			$email = $this->_addElement(AtomNS::NS, AtomNS::EMAIL_ELEMENT, $value);
			$this->_email = new SimpleAtomAdapter(AtomNS::EMAIL_ELEMENT, $email);
			return;
		}
		$this->_email->value = $value;
	}
	
	public function setName($value) {
		if (!isset($this->_name)) {
			$name = $this->_addElement(AtomNS::NS, AtomNS::NAME_ELEMENT, $value);
			$this->_name = new SimpleAtomAdapter(AtomNS::NAME_ELEMENT, $name);
			return;
		}
		$this->_name->value = $value;
	}
	
	public function setUri($value) {
		if (!isset($this->_uri)) {
			$uri = $this->_addElement(AtomNS::NS, AtomNS::URI_ELEMENT, $value);
			$this->_uri = new SimpleAtomAdapter(AtomNS::URI_ELEMENT, $uri);
			return;
		}
		$this->_uri->value = $value;
	}
	
	public function __construct($adapterType, $data, $data_is_url=false) {
		parent::__construct($adapterType, $data, $data_is_url);
		$this->_init();
	}
	
	protected function _init() {
		if (isset($this->_element[AtomNS::NAME_ELEMENT][0])) {
			$this->_name = new SimpleAtomAdapter(AtomNS::NAME_ELEMENT, $this->_element[AtomNS::NAME_ELEMENT][0]);	
		}
	
		if (isset($this->_element[AtomNS::URI_ELEMENT][0])) {
			$this->_uri = new SimpleAtomAdapter(AtomNS::URI_ELEMENT, $this->_element[AtomNS::URI_ELEMENT][0]);	
		}
	
		if (isset($this->_element[AtomNS::EMAIL_ELEMENT][0])) {
			$this->_email = new SimpleAtomAdapter(AtomNS::EMAIL_ELEMENT, $this->_element[AtomNS::EMAIL_ELEMENT][0]);	
		}
	}
}