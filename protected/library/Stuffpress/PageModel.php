<?php

abstract class Stuffpress_PageModel
{	
	protected $_prefix;
	
	protected $_name;
	
	protected $_description;

	public function getName() {
		return $this->_name;
	}
	
	public function getDescription() {
		return $this->_description;
	}
	
	public function getDefaultValues() {
		$values = array();
		$values['title'] = 'My page';
		return $values;
	}
	
	public function getPageValues($page_id) {
		$properties = new PagesProperties(array(PagesProperties::KEY => $page_id));
		$values		= $properties->getPropertiesArray();
		return $values;
	}
	
	public function processForm($page_id, $values) {
		return;
	}
	
	public function getForm() {
		$form = new Stuffpress_PageForm();
			
		// Add the form element details
		$form->setMethod('post');
		$form->setName("form_page");
		$form->setTemplate('default.phtml');
		
		// Create and configure title element:
		$e = $form->createElement('text', 'title',  array('label' => 'Title:', 'class' => 'width1', 'decorators' => $form->noDecorators));
		$e->addFilter('StripTags');
		$form->addElement($e);
		
		// Add a hidden element with the page id
		$e = $form->createElement('hidden', 'id');
		$e->removeDecorator('HtmlTag');
		$form->addElement($e);
		
		// Add a hidden element with the  page type
		$e = $form->createElement('hidden', 'type');
		$e->setValue($this->_prefix);
		$e->removeDecorator('HtmlTag');
		$form->addElement($e);

		// Add a submit button
		$button = $form->createElement('submit', 'save', array('onClick' => 'onFormSubmit(); return false;' , 'decorators' => $form->noDecorators));
		$form->addElement($button);
		
		// Add a cancel button
		$element = $form->createElement('button', 'cancel', array('decorators' => array('ViewHelper')));
		$element->setAttrib('onClick', 'history.go(-1);return false;');
		$form->addElement($element);
		
		// Group elements
		$form->addDisplayGroup(array('save', 'cancel'), 'buttons', array('decorators' => $form->groupDecorators));							 
		
		return $form;
	}
}


