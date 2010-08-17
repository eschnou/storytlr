<?php

class StructuredActivityExtension extends ExtensibleAtomAdapter {

	public function __construct(SimpleXMLElement $data, $extensionType) {		
	
		$this->_atomNode = $data;
		
		if ($this->_atomNode->getName() != $extensionType) { //check whether $this->_atomNode is the appropriate XML Object, e.g. atom entry node for ActivityEntryExtension
			throw new ActivityExtensionException("Invalid XML Object");
		}
		
		$this->_prefix = $this->_getPrefix(ActivityNS::NS);
		if ($this->_prefix === null) {
			$this->_prefix = ActivityNS::NS;
		}
		
		//$this->_fetchChilds(AtomNS::NS);
		$this->_fetchChilds(ActivityNS::NS);
	}
}