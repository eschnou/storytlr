<?php

require_once 'ImageLinkProcessor.php';

class PhotoObjectProcessor extends DefaultObjectProcessor implements IActivityPhoto {
	
	/* 
	 * @return IMediaImageLink
	 */
	public function getThumbnail() {
		return new ImageLinkProcessor($this->_getLink(MediaNS::LINK_REL_PREVIEW));
	}
	
	public function setThumbnail($href, $type, $width, $height) {
		$link = $this->_setLink(MediaNS::LINK_REL_PREVIEW);
		$link->href		= $href;
		$link->type		= $type;
		$link->width	= $width;
		$link->height	= $height;
	}
	
	/* 
	 * @return IMediaImageLink
	 */
	public function getLargerImage() {
		return new ImageLinkProcessor($this->_getLink(AtomNS::REL_ENCLOSURE));
	}
	
	public function setLargerImage($href, $type, $width, $height) {
		$link = $this->_setLink(AtomNS::REL_ENCLOSURE);
		$link->href		= $href;
		$link->type		= $type;
		$link->width	= $width;
		$link->height	= $height;
	}
	
	public function getImagePageUrl() {
		return $this->getPermalink();
	}
	
	public function setImagePageUrl($href) {
		$this->setPermalink($href);
	}
	
	public function getDescription() {
		return $this->_object->description->value;
	}
	
	public function setDescription($value) {
		$this->_object->description			= $value;
		$this->_object->description->type	= AtomNS::TYPE_HTML;
	}
	
}