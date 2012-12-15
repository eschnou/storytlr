<?php

class ArticleObjectProcessor extends DefaultObjectProcessor implements IActivityArticle {
	protected $_object;
	
	public function getSummary() {
		return $this->_object->summary->value;
	}
	
	public function setSummary($value) {
		$this->_object->summary			= $value;
		$this->_object->summary->type	= AtomNS::TYPE_HTML;
	}
	
}