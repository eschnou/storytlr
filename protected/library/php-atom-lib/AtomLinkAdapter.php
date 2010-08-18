<?php

require_once 'SimpleAtomAdapter.php';

class AtomLinkAdapter extends SimpleAtomAdapter {
	
	public function getHref() {
		return $this->_getAttribute(AtomNS::HREF_ATTRIBUTE);
	}
	
	public function getHreflang() {
		return $this->_getAttribute(AtomNS::HREFLANG_ATTRIBUTE);
	}
	
	public function getLength() {
		return $this->_getAttribute(AtomNS::LENGTH_ATTRIBUTE);
	}
	
	public function getRel() {
		return $this->_getAttribute(AtomNS::REL_ATTRIBUTE);
	}
	
	public function getTitle() {
		return $this->_getAttribute(AtomNS::TITLE_ATTRIBUTE);
	}
	
	public function getType() {
		return $this->_getAttribute(AtomNS::TYPE_ATTRIBUTE);
	}
	
	public function setHref($value) {
		$this->_setAttribute(AtomNS::HREF_ATTRIBUTE, $value);
	}
	
	public function setHreflang($value) {
		$this->_setAttribute(AtomNS::HREFLANG_ATTRIBUTE, $value);
	}
	
	public function setLength($value) {
		$this->_setAttribute(AtomNS::LENGTH_ATTRIBUTE, $value);
	}
	
	public function setRel($value) {
		$this->_setAttribute(AtomNS::REL_ATTRIBUTE, $value);
	}
	
	public function setTitle($value) {
		$this->_setAttribute(AtomNS::TITLE_ATTRIBUTE, $value);
	}
	
	public function setType($value) {
		$this->_setAttribute(AtomNS::TYPE_ATTRIBUTE, $value);
	}
	
	public function __construct($data, $data_is_url=false) {
		parent::__construct(AtomNS::LINK_ELEMENT, $data, $data_is_url);
	}
}