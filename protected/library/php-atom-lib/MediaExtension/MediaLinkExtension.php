<?php

require_once 'SimpleMediaExtension.php';

class MediaLinkExtension extends AtomLinkAdapter {
	protected $_width;
	protected $_height;
	protected $_duration;
	
	public function getWidth() {
		return $this->_getAttribute(MediaNS::WIDTH_ATTRIBUTE, MediaNS::NS);
	}
	
	public function getHeight() {
		return $this->_getAttribute(MediaNS::HEIGHT_ATTRIBUTE, MediaNS::NS);
	}
	
	public function getDuration() {
		return $this->_getAttribute(MediaNS::DURATION_ATTRIBUTE, MediaNS::NS);
	}
	
	public function setWidth($value) {
		$this->_setAttribute(MediaNS::WIDTH_ATTRIBUTE, $value, MediaNS::NS);
	}
	
	public function setHeight($value) {
		$this->_setAttribute(MediaNS::HEIGHT_ATTRIBUTE, $value, MediaNS::NS);
	}
	
	public function setDuration($value) {
		$this->_setAttribute(MediaNS::DURATION_ATTRIBUTE, $value, MediaNS::NS);
	}
	
	public function __construct($data) {
		$this->_atomNode = $data;
		
		if ($this->_atomNode->getName() != AtomNS::LINK_ELEMENT) { //check whether $this->_atomNode is the appropriate XML Object, e.g. atom entry node for ActivityEntryExtension
			throw new ActivityExtensionException("Invalid XML Object");
		}
		
		$this->_prefix = $this->_getPrefix(MediaNS::NS);
		if ($this->_prefix === null) {
			$this->_prefix = MediaNS::PREFIX;
		}
	}
}