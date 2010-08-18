<?php

class StreamLinkProcessor implements IMediaStreamLink {
	protected $_link;
	
	public function getHref() {
		return $this->_link->href;
	}
	
	public function getDuration() {
		return $this->_link->duration;
	}
	
	public function __construct(MediaLinkExtension $link) {
		$this->_link = $link;
	}
}