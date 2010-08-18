<?php

require_once 'AtomTextConstructAdapter.php';

class AtomContentAdapter extends AtomTextConstructAdapter {
	
	public function __construct($data, $data_is_url=false) {
		parent::__construct(AtomNS::CONTENT_ELEMENT, $data, $data_is_url);
	}
	
	public function getSrc() {
		return $this->_getAttribute(AtomNS::SRC_ATTRIBUTE);
	}
	
	public function setSrc($value) {
		$this->_setAttribute(AtomNS::SRC_ATTRIBUTE, $value);
	}
}