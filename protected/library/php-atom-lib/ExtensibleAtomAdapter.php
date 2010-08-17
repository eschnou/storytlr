<?php 

require_once 'BaseAtomAdapter.php';
require_once 'AtomExtensionManager.php';

class ExtensibleAtomAdapter extends BaseAtomAdapter {
	
	protected $_element;
	
	public function __construct($adapterType, $data, $data_is_url=false) {
		parent::__construct($adapterType, $data, $data_is_url);
		
		$this->_fetchChilds(AtomNS::NS);
	}
	
	protected function _addElement($namespace, $tagName, $value=null) {
		$prefix = "";
		$ns 	= null;
		if ($this->_prefix != "") {
			$prefix = $this->_prefix.":";
			$ns 	= $namespace; 
		}
		return $this->_atomNode->addChild($prefix.$tagName, $value, $ns);
	}
	
	protected function _fetchChilds($namespace) {
		foreach($this->_atomNode->children($namespace) as $children) {
			$this->_element[$children->getName()][] = $children;
		}
	}
}