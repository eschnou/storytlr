<?php

require_once 'ImageLinkProcessor.php';
require_once 'StreamLinkProcessor.php';

class AudioObjectProcessor extends DefaultObjectProcessor implements IActivityAudio {
	
	/* 
	 * @return IMediaStreamLink
	 */
	public function getAudioStream() {
		return new StreamLinkProcessor($this->_getLink(AtomNS::REL_ENCLOSURE));
	}
	
	public function setAudioStream($href, $type, $duration) {
		$link = $this->_setLink(AtomNS::REL_ENCLOSURE);
		$link->href		= $href;
		$link->type		= $type;
		$link->duration	= $duration;
	}
	
	/* 
	 * @return IMediaImageLink
	 */
	public function getPlayerApplet() {
		return new ImageLinkProcessor($this->_getPlayerAppletLink());
	}
	
	public function setPlayerApplet($href, $type, $width, $height) {
		$link = $this->_setPlayerAppletLink();
		$link->href		= $href;
		$link->type		= $type;
		$link->width	= $width;
		$link->height	= $height;
	}
	
	public function getAudioPageUrl() {
		return $this->getPermalink();
	}
	
	public function getDescription() {
		return $this->_object->description->value;
	}
	
	public function setDescription($value) {
		$this->_object->description			= $value;
		$this->_object->description->type	= AtomNS::TYPE_HTML;
	}
	
	public function setAudioPageUrl($href) {
		$this->setPermalink($href);
	}

	/**
	 * @return MediaLinkExtension|false
	 */
	protected function _getPlayerAppletLink() {
		foreach($this->_object->link as $link) {
			if ($link->rel == AtomNS::REL_ALTERNATE && $link->type != MediaNS::LINK_TYPE_TEXT_HTML) {
				return $link;
			}
		}
		return false;
	}

	/**
	 * @return MediaLinkExtension
	 */
	protected function _setPlayerAppletLink() {
		$link = $this->_getPlayerAppletLink();
		if (!$link) {
			$link = $this->_object->addLink();
			$link->rel = AtomNS::REL_ALTERNATE;
		}
		return $link;
	}
	
}