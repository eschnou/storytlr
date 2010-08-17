<?php

require_once 'SimpleAtomAdapter.php';

class AtomTextConstructAdapter extends SimpleAtomAdapter {
	
	public function getType() {
		return $this->_getAttribute(AtomNS::TYPE_ATTRIBUTE);
	}
	
	public function setType($value) {
		$this->_setAttribute(AtomNS::TYPE_ATTRIBUTE, $value);
	}
}