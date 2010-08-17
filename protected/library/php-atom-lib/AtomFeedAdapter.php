<?php
require_once 'AtomSourceAdapter.php';
require_once 'AtomEntryAdapter.php';
require_once 'AtomGeneratorAdapter.php';


class AtomFeedAdapter extends AtomSourceAdapter  {	
	protected $_entry;
	protected $_generator;
	
	/**
	 * @return AtomEntryAdapter;
	 */
	public function addEntry() {
		$newEntry = $this->_addElement(AtomNS::NS, AtomNS::ENTRY_ELEMENT);
		return $this->_entry[] = new AtomEntryAdapter($newEntry);
	}
	
	public function getEntry() {
		return $this->_entry;
	}
	
	public function getGenerator() {
		return $this->_generator;
	}
	
	public function setGenerator($value) {
		if (!isset($this->_generator)) {
			$generator 			= $this->_addElement(AtomNS::NS, AtomNS::GENERATOR_ELEMENT, $value);
			$this->_generator 	= new AtomGeneratorAdapter($generator);
			return;
		}
		$this->_generator->value = $value;
	}
	
	public function __construct($data=null, $data_is_url=false) {
		parent::__construct(AtomNS::FEED_ELEMENT, $data, $data_is_url);
		//$this->_init();
	}
	
	protected function _init() {
		parent::_init();
		$this->_entry = array();
		if (isset($this->_element[AtomNS::ENTRY_ELEMENT])){
			foreach ($this->_element[AtomNS::ENTRY_ELEMENT] as $entry) {
				$this->_entry[] = new AtomEntryAdapter($entry);
			}	
		}
		
		if (isset($this->_element[AtomNS::GENERATOR_ELEMENT][0])) {
			$this->_generator = new AtomGeneratorAdapter($this->_element[AtomNS::GENERATOR_ELEMENT][0]);	
		}
	}
}