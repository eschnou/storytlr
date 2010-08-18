<?php

require_once 'ImageLinkProcessor.php';

class BookmarkObjectProcessor extends DefaultObjectProcessor implements IActivityBookmark {

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
	
	public function getTargetUrl() {
		return $this->_getLink(AtomNS::REL_RELATED)->href;
	}
	
	public function setTargetUrl($href) {
		$link = $this->_setLink(AtomNS::REL_RELATED);
		$link->href		= $href;
	}
	
	public function getTargetTitle() {
		return $this->_getLink(AtomNS::REL_RELATED)->title;
	}
	
	public function setTargetTitle($value) {
		$link = $this->_setLink(AtomNS::REL_RELATED);
		$link->title	= $value;
	}
	
	public function getBookmarkPageUrl() {
		return $this->getPermalink();
	}
	
	public function setBookmarkPageUrl($href) {
		$this->setPermalink($href);
	}
	
	public function getDescription() {
		return $this->_object->summary->value;
	}
	
	public function setDescription($value) {
		$this->_object->summary			= $value;
		$this->_object->summary->type	= AtomNS::TYPE_HTML;
	}
	
}