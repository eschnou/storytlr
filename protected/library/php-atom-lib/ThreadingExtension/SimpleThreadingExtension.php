<?php

class SimpleThreadingExtension extends SimpleAtomAdapter {

	public function __construct(SimpleXMLElement $data, $extensionType) {		
	
		$this->_atomNode = $data;
		
		if ($this->_atomNode->getName() != $extensionType) { //check whether $this->_atomNode is the appropriate XML Object, e.g. atom entry node for ActivityEntryExtension
			throw new ThreadingExtensionException("Invalid XML Object");
		}
		
		$this->_prefix = $this->_getPrefix(ThreadingNS::NS);
		if ($this->_prefix === null) {
			$this->_prefix = ThreadingNS::NS;
		}
	}
}