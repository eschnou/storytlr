<?php

require_once 'SimpleActivityExtension.php';
require_once 'ActivityObjectExtension.php';
require_once 'StructuredActivityExtension.php';

class ActivityEntryExtension extends StructuredActivityExtension {
	
	protected $_generator;
	protected $_object;
	protected $_target;
	protected $_verb;
	
	/**
	 * @return ActivityObjectExtension
	 */
	public function addObject() {
		$newObject = $this->_addElement(ActivityNS::NS, ActivityNS::OBJECT_ELEMENT, null);
		return $this->_object[] = new ActivityObjectExtension($newObject, ActivityNS::OBJECT_ELEMENT);
	}
	
	public function addTarget() {
		$newTarget = $this->_addElement(ActivityNS::NS, ActivityNS::TARGET_ELEMENT, null);
		return $this->_target[] = new ActivityObjectExtension($newTarget, ActivityNS::TARGET_ELEMENT);
	}
	
	public function addVerb($verb=null) {
		$newVerb = $this->_addElement(ActivityNS::NS, ActivityNS::VERB_ELEMENT, $verb);
		return $this->_verb[] = new SimpleActivityExtension($newVerb, ActivityNS::VERB_ELEMENT);
	}
	
	public function getGenerator() {
		return $this->_generator;
	}
	
	public function getObject() {
		return $this->_object;
	}
	
	public function getTarget() {
		return $this->_target;
	}
	
	public function getVerb() {	
		return $this->_verb;	
	}
	
	public function setGenerator($value) {
		if (!isset($this->_generator)) {
			$generator = $this->_addElement(AtomNS::NS, AtomNS::GENERATOR_ELEMENT, $value);
			$this->_generator = new AtomGeneratorAdapter($generator);
			return;
		}
		$this->_generator->value = $value;
	}
	
	public function __construct($data) {
		parent::__construct($data, AtomNS::ENTRY_ELEMENT);
		$this->_init();
	}
	
	protected function _init() {
	
		$this->_object = array();
		if (isset($this->_element[ActivityNS::OBJECT_ELEMENT])) {
			foreach ($this->_element[ActivityNS::OBJECT_ELEMENT] as $object) {
				$this->_object[] = new ActivityObjectExtension($object, ActivityNS::OBJECT_ELEMENT);
			}
		}
		$this->_target = array();
		if (isset($this->_element[ActivityNS::TARGET_ELEMENT])) {
			foreach ($this->_element[ActivityNS::TARGET_ELEMENT] as $target) {
				$this->_target[] = new ActivityObjectExtension($target, ActivityNS::TARGET_ELEMENT);
			}
		}
		
		$this->_verb = array();
		if (isset($this->_element[ActivityNS::VERB_ELEMENT])) {
			foreach ($this->_element[ActivityNS::VERB_ELEMENT] as $verb) {
				$this->_verb[] = new SimpleActivityExtension($verb, ActivityNS::VERB_ELEMENT);
			}
		}
		
		if (isset($this->_element[AtomNS::GENERATOR_ELEMENT][0])) {
			$this->_generator = new AtomGeneratorAdapter($this->_element[AtomNS::GENERATOR_ELEMENT][0]);	
		}
	}
}