<?php

require_once 'ThreadingInReplyToExtension.php';
require_once 'StructuredThreadingExtension.php';

class ThreadingEntryExtension extends StructuredThreadingExtension {
	
	protected $_inReplyTo;
	
	public function getInReplyTo() {	
		return $this->_inReplyTo;	
	}
	
	public function setInReplyTo($ref=null, $href=null, $source=null, $type=null) {
		if (!isset($this->_inReplyTo)) {
			$inReplyTo = $this->_addElement(ThreadingNS::NS, ThreadingNS::IN_REPLY_TO_ELEMENT);
			$this->_inReplyTo = new ThreadingInReplyToExtension($inReplyTo);
		}
		$this->_inReplyTo->ref = $ref;
		$this->_inReplyTo->href = $href;
		$this->_inReplyTo->source = $source;
		$this->_inReplyTo->type = $type;
	}
	
	public function __construct($data) {
		parent::__construct($data, AtomNS::ENTRY_ELEMENT);
		$this->_init();
	}
	
	protected function _init() {
		
		if (isset($this->_element[ThreadingNS::IN_REPLY_TO_ELEMENT][0])) {
			$this->_inReplyTo = new ThreadingInReplyToExtension($this->_element[ThreadingNS::IN_REPLY_TO_ELEMENT][0]);	
		}
	}
}