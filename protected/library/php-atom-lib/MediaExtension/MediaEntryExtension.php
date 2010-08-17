<?php

require_once 'StructuredMediaExtension.php';
require_once 'MediaDescriptionExtension.php';

class MediaEntryExtension extends StructuredMediaExtension {
	
	protected $_description;
	
	public function getDescription() {
		return $this->_description;
	}
	
	public function setDescription($value) {
		if (!isset($this->_description)) {
			$description = $this->_addElement(MediaNS::NS, MediaNS::DESCRIPTION_ELEMENT, $value);
			$this->_description = new MediaDescriptionExtension($description);
			return;
		}
		$this->_description->value = $value;
	}
	
	public function __construct($data, $adapterType) {
		parent::__construct($data, $adapterType);
		$this->_init();
	}
	
	protected function _init() {
		
		if (isset($this->_element[MediaNS::DESCRIPTION_ELEMENT][0])) {
			$this->_description = new MediaDescriptionExtension($this->_element[MediaNS::DESCRIPTION_ELEMENT][0]);	
		}
	}
}