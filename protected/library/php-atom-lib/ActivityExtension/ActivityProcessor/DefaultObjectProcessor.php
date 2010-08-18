<?php

class DefaultObjectProcessor implements IActivityDefault {
	protected $_object;
	protected $_type;
	
	public function getType() {
		return $this->_type;
	}
	
	public function getContent() {
		return $this->_object->content->value;
	}
	
	public function setContent($value) {
		$this->_object->content			= $value;
		$this->_object->content->type	= AtomNS::TYPE_HTML;	
	}
	
	public function getTitle() {
		return $this->_object->title->value;
	}
	
	public function getId() {
		return $this->_object->id->value;
	}
	
	public function setTitle($value) {
		$this->_object->title		= $value;
		$this->_object->title->type	= AtomNS::TYPE_TEXT;
	}
	
	public function setId($value) {
		$this->_object->id		= $value;
	}
	
	public function getPermalink() {
		return $this->_getLink(AtomNS::REL_ALTERNATE, MediaNS::LINK_TYPE_TEXT_HTML)->href;
	}
	
	public function setPermalink($href) {
		$link = $this->_setLink(AtomNS::REL_ALTERNATE, MediaNS::LINK_TYPE_TEXT_HTML);
		$link->href	= $href;
	}
	
	public function __construct(ActivityObjectExtension $object, $type='default') {
		$this->_object	= $object;
		$this->_type	= $type;
	}
	
	public function __set($name, $value) {
		$method = 'set' . $name;
		$this->$method($value);
	}
	
	/**
	 * @return MediaLinkExtension|false
	 */
	protected function _getLink($rel, $type=null) {
		foreach($this->_object->link as $link) {
			if ($link->rel == $rel && ($link->type == $type || $type === null)) {
				return $link;
			}
		}
		return false;
	}
	
	/**
	 * @return MediaLinkExtension
	 */
	protected function _setLink($rel, $type=null) {
		$link = $this->_getLink($rel, $type);
		if (!$link) {
			$link = $this->_object->addLink();
			$link->rel = $rel;
			if ($type !== null) {
				$link->type = $type;
			}
		}
		return $link;
	}
}