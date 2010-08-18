<?php

class ImageLinkProcessor implements IMediaImageLink {
	protected $_link;
	
	public function getHref() {
		return $this->_link->href;
	}
	
	public function getWidth() {
		return $this->_link->width;
	}
	
	public function getHeight() {
		return $this->_link->height;
	}
	
	public function __construct(MediaLinkExtension $link) {
		$this->_link = $link;
	}
}