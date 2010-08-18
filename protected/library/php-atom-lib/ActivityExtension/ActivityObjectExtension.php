<?php

require_once 'SimpleActivityExtension.php';

class ActivityObjectExtension extends AtomEntryAdapter {
	
	protected $_objectType;
	protected $_description;
	
	/**
	 * @return MediaLinkExtension
	 */
	public function addLink() {
		$newLink = $this->_addElement(AtomNS::NS, AtomNS::LINK_ELEMENT);
		return $this->_link[] = new MediaLinkExtension($newLink);
	}
	
	/**
	 * @param string $value
	 * @return SimpleActivityExtension
	 */
	public function addObjectType($value=null) {
		$newObjectType = $this->_addElement(ActivityNS::NS, ActivityNS::OBJECT_TYPE_ELEMENT, $value);
		return $this->_objectType[] = new SimpleActivityExtension($newObjectType, ActivityNS::OBJECT_TYPE_ELEMENT);
	}
	
	public function setDescription($value=null) {
		if (!isset($this->_description)) {
			$description = $this->_addElement(MediaNS::NS, MediaNS::DESCRIPTION_ELEMENT, $value);
			$this->_description = new MediaDescriptionExtension($description);
			return;
		}
		$this->_description->value = $value;
	}	
	
	/**
	 * @return SimpleActivityExtension
	 */
	public function getObjectType() {	
		return $this->_objectType;	
	}
	
	/**
	 * @return MediaDescriptionExtension
	 */
	public function getDescription() {
		return $this->_description;
	}
	
	public function __construct(SimpleXMLElement $data, $extensionType) {		
	
		$this->_atomNode = $data;
		
		if ($this->_atomNode->getName() != $extensionType) {
			throw new ActivityExtensionException("Invalid XML Object");
		}
		
		$this->_prefix = $this->_getPrefix(ActivityNS::NS);
		if ($this->_prefix === null) {
			$this->_prefix = ActivityNS::NS;
		}
		
		$this->_fetchChilds(AtomNS::NS);
		$this->_fetchChilds(ActivityNS::NS);
		$this->_fetchChilds(MediaNS::NS);
		
		$this->_init();
	}
	
	protected function _init() {
		parent::_init();
		
		$this->_objectType = array();
		if (isset($this->_element[ActivityNS::OBJECT_TYPE_ELEMENT])) {
			foreach ($this->_element[ActivityNS::OBJECT_TYPE_ELEMENT] as $objectType) {
				$this->_objectType[] = new SimpleActivityExtension($objectType, ActivityNS::OBJECT_TYPE_ELEMENT);
			}
		}
		
		if (isset($this->_element[MediaNS::DESCRIPTION_ELEMENT])) {
			$this->_description = new MediaDescriptionExtension($this->_element[MediaNS::DESCRIPTION_ELEMENT][0]);
		}
		
		$tempLink = $this->_link;
		//unset($this->_link);
		$this->_link = array();
		foreach ($tempLink as $link) {
			$this->_link[] = $link->getExtension(MediaNS::NS);
		}
	}
}