<?php

require_once 'StructuredActivityExtension.php';

class ActivityActorExtension_obsoleted extends StructuredActivityExtension {
	
	protected $_objectType; 
	
	public function addObjectType() {
		$newObjectType = $this->_addElement(ActivityNS::NS, ActivityNS::OBJECT_TYPE_ELEMENT);
		return $this->_objectType[] = new ActivityObjectTypeExtension($newObjectType);
	}
	
	public function getObjectType() {	
		return $this->_objectType;	
	}
	
	public function __construct($data) {
		parent::__construct($data, AtomNS::AUTHOR_ELEMENT);
		
		$this->_init();
	}
	
	protected function _init() {
		$this->_objectType = array();
		if (isset($this->_element[ActivityNS::OBJECT_TYPE_ELEMENT])) {
			foreach ($this->_element[ActivityNS::OBJECT_TYPE_ELEMENT] as $objectType) {
				$this->_objectType[] = new SimpleActivityExtension($objectType, ActivityNS::OBJECT_TYPE_ELEMENT);
			}
		}
	}
}