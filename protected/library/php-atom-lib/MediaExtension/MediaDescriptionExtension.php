<?php

class MediaDescriptionExtension extends AtomContentAdapter {

	public function __construct(SimpleXMLElement $data) {		
	
		$this->_atomNode = $data;
		
		if ($this->_atomNode->getName() != MediaNS::DESCRIPTION_ELEMENT) { //check whether $this->_atomNode is the appropriate XML Object, e.g. atom entry node for ActivityEntryExtension
			throw new ActivityExtensionException("Invalid XML Object");
		}
		
		$this->_prefix = $this->_getPrefix(MediaNS::NS);
		if ($this->_prefix === null) {
			$this->_prefix = MediaNS::PREFIX;
		}
	}
}