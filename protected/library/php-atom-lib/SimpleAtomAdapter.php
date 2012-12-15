<?php

require_once 'BaseAtomAdapter.php'; //may be the getter method of object of this class can directly go to getContent()

class SimpleAtomAdapter extends BaseAtomAdapter {

	public function getValue() { // I'll handle the HTML and XHTML type for text and content construct later
		return trim((string)$this->_atomNode);
	}
	
	public function setValue($value) { // I'll handle the HTML and XHTML type for text and content construct later
		$this->_atomNode[0] = $value;
	}
}