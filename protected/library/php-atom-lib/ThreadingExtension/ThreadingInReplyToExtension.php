<?php

require_once 'SimpleThreadingExtension.php';

class ThreadingInReplyToExtension extends SimpleThreadingExtension {

	public function getHref() {
		return $this->_getAttribute(ThreadingNS::HREF_ATTRIBUTE);
	}
	
	public function getRef() {
		return $this->_getAttribute(ThreadingNS::REF_ATTRIBUTE);
	}
	
	public function getSource() {
		return $this->_getAttribute(ThreadingNS::SOURCE_ATTRIBUTE);
	}
	
	public function getType() {
		return $this->_getAttribute(ThreadingNS::TYPE_ATTRIBUTE);
	}
	
	public function setRef($value) {
		$this->_setAttribute(ThreadingNS::REF_ATTRIBUTE, $value);
	}
	
	public function setHref($value) {
		$this->_setAttribute(ThreadingNS::HREF_ATTRIBUTE, $value);
	}
	
	public function setSource($value) {
		$this->_setAttribute(ThreadingNS::SOURCE_ATTRIBUTE, $value);
	}
	
	public function setType($value) {
		$this->_setAttribute(ThreadingNS::TYPE_ATTRIBUTE, $value);
	}
	
	public function __construct(SimpleXMLElement $data) {
		parent::__construct($data, ThreadingNS::IN_REPLY_TO_ELEMENT);
	}
}