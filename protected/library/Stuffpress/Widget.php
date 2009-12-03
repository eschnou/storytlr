<?php

abstract class Stuffpress_Widget
{	
	protected $_name;
	
	protected $_description;

	public function getName() {
		return $this->_name;
	}
	
	public function getDescription() {
		return $this->_description;
	}
	
	public function canEdit() {
		return true;
	}
}


